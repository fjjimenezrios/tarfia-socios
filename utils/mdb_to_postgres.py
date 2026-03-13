#!/usr/bin/env python3
"""
mdb_to_postgres.py — Migra un archivo .mdb (Access) a PostgreSQL usando mdbtools.

Uso:
    python3 utils/mdb_to_postgres.py archivo.mdb
    python3 utils/mdb_to_postgres.py archivo.mdb --host localhost --port 5432 \
        --db tarfia_socios --user tarfia --password secreto

Si no se pasan parámetros de conexión, los lee del .env del proyecto.

Requisitos:
    mdbtools instalado (apt install mdbtools / brew install mdbtools)
    psycopg instalado   (pip install psycopg)
"""

import argparse
import csv
import io
import os
import subprocess
import sys

csv.field_size_limit(10 * 1024 * 1024)  # 10 MB — para memos e imágenes en base64

# ── Helpers ───────────────────────────────────────────────────────────────────

def sanitize(name: str) -> str:
    """Normaliza nombres de tabla/columna para PostgreSQL."""
    replacements = {
        "/": "_", " ": "_", "-": "_",
        "ó": "o", "é": "e", "í": "i", "á": "a", "ú": "u",
        "Ó": "O", "É": "E", "Í": "I", "Á": "A", "Ú": "U",
        "ñ": "n", "Ñ": "N",
        "º": "num", "ª": "fem",
        "(": "", ")": "", ".": "_",
    }
    for src, dst in replacements.items():
        name = name.replace(src, dst)
    # Asegurar que no empieza por dígito
    if name and name[0].isdigit():
        name = "_" + name
    return name.lower()


def run(cmd: list[str], **kwargs) -> subprocess.CompletedProcess:
    return subprocess.run(cmd, capture_output=True, text=True, errors="replace", **kwargs)


def check_mdbtools(mdb_file: str) -> None:
    result = run(["which", "mdb-tables"])
    if result.returncode != 0:
        print("✗  mdbtools no encontrado.")
        print("   Linux:  sudo apt install mdbtools")
        print("   macOS:  brew install mdbtools")
        sys.exit(1)
    if not os.path.exists(mdb_file):
        print(f"✗  Archivo no encontrado: {mdb_file}")
        sys.exit(1)


def get_tables(mdb_file: str) -> list[str]:
    result = run(["mdb-tables", "-1", mdb_file])
    return [t.strip() for t in result.stdout.strip().split("\n") if t.strip()]


def export_csv(mdb_file: str, table: str) -> str:
    """Devuelve el CSV de una tabla como string."""
    result = run([
        "mdb-export",
        "-b", "strip",           # elimina campos OLE/binarios
        "-D", "%Y-%m-%d",        # fechas en ISO 8601
        "-T", "%Y-%m-%d %H:%M:%S",
        mdb_file, table,
    ])
    return result.stdout


def read_headers(csv_text: str) -> list[str]:
    reader = csv.reader(io.StringIO(csv_text))
    return next(reader, [])


# ── Conexión ──────────────────────────────────────────────────────────────────

def load_env_defaults() -> dict:
    """Lee DB_* del .env del proyecto si existe."""
    env_path = os.path.join(os.path.dirname(__file__), "..", ".env")
    defaults = {}
    if os.path.exists(env_path):
        with open(env_path) as f:
            for line in f:
                line = line.strip()
                if line.startswith("#") or "=" not in line:
                    continue
                key, _, val = line.partition("=")
                defaults[key.strip()] = val.strip()
    return defaults


def get_connection(args, env: dict):
    try:
        import psycopg
    except ImportError:
        print("✗  psycopg no instalado. Ejecuta: pip install psycopg")
        sys.exit(1)

    host     = args.host     or env.get("DB_HOST", "localhost")
    port     = args.port     or env.get("DB_PORT", "5432")
    dbname   = args.db       or env.get("DB_NAME", "tarfia_socios")
    user     = args.user     or env.get("DB_USER", "tarfia")
    password = args.password or env.get("DB_PASS", "")

    print(f"  Conectando a PostgreSQL → {user}@{host}:{port}/{dbname}")
    return psycopg.connect(
        host=host, port=int(port), dbname=dbname,
        user=user, password=password,
        autocommit=False,
    )


# ── Migración de tabla ────────────────────────────────────────────────────────

def migrate_table(mdb_file: str, table: str, conn) -> int:
    csv_text = export_csv(mdb_file, table)
    if not csv_text.strip():
        print("    ⚠  Sin datos, saltando.")
        return 0

    headers = read_headers(csv_text)
    if not headers:
        print("    ⚠  Sin columnas, saltando.")
        return 0

    safe_table   = sanitize(table)
    safe_columns = [sanitize(h) for h in headers]

    with conn.cursor() as cur:
        # Crear tabla (todas TEXT — Django/DRF hacen el tipado)
        cols_def = ", ".join(f'"{c}" TEXT' for c in safe_columns)
        cur.execute(f'DROP TABLE IF EXISTS "{safe_table}" CASCADE')
        cur.execute(f'CREATE TABLE "{safe_table}" ({cols_def})')

        # Insertar filas en batches
        reader = csv.reader(io.StringIO(csv_text))
        next(reader)  # saltar cabecera

        placeholders = ", ".join(["%s"] * len(safe_columns))
        insert_sql = f'INSERT INTO "{safe_table}" VALUES ({placeholders})'

        batch: list[list[str]] = []
        count = 0

        for row in reader:
            # Normalizar longitud
            while len(row) < len(safe_columns):
                row.append("")
            row = row[:len(safe_columns)]

            # Limpiar valores no UTF-8
            clean: list[str] = []
            for val in row:
                try:
                    val.encode("utf-8")
                    clean.append(val if val != "" else None)
                except Exception:
                    clean.append(None)

            batch.append(clean)
            count += 1

            if len(batch) >= 500:
                cur.executemany(insert_sql, batch)
                batch = []

        if batch:
            cur.executemany(insert_sql, batch)

    conn.commit()
    return count


# ── Main ──────────────────────────────────────────────────────────────────────

def main() -> None:
    parser = argparse.ArgumentParser(
        description="Migra un .mdb (Access) a PostgreSQL"
    )
    parser.add_argument("mdb_file", help="Ruta al archivo .mdb")
    parser.add_argument("--host",     default=None)
    parser.add_argument("--port",     default=None)
    parser.add_argument("--db",       default=None, help="Nombre de la base de datos")
    parser.add_argument("--user",     default=None)
    parser.add_argument("--password", default=None)
    parser.add_argument(
        "--schema", default="mdb_import",
        help="Schema de PostgreSQL donde importar (default: mdb_import)",
    )
    args = parser.parse_args()

    check_mdbtools(args.mdb_file)

    env = load_env_defaults()
    tables = get_tables(args.mdb_file)

    print()
    print("=" * 55)
    print("  MDB → PostgreSQL")
    print(f"  Origen:  {args.mdb_file}")
    print(f"  Schema:  {args.schema}")
    print("=" * 55)
    print(f"\nTablas encontradas ({len(tables)}):")
    for t in tables:
        print(f"  • {t}")
    print()

    conn = get_connection(args, env)

    # Crear schema de importación para no mezclar con las tablas de Django
    with conn.cursor() as cur:
        cur.execute(f'CREATE SCHEMA IF NOT EXISTS "{args.schema}"')
        cur.execute(f'SET search_path TO "{args.schema}"')
    conn.commit()
    print(f"  Schema '{args.schema}' listo.\n")

    total_rows = 0
    errors = []

    for i, table in enumerate(tables, 1):
        print(f"[{i}/{len(tables)}] {table}")
        try:
            # Cambiar search_path por tabla
            with conn.cursor() as cur:
                cur.execute(f'SET search_path TO "{args.schema}"')
            rows = migrate_table(args.mdb_file, table, conn)
            print(f"    ✓ {rows} filas importadas")
            total_rows += rows
        except Exception as exc:
            print(f"    ✗ Error: {exc}")
            errors.append((table, str(exc)))
            conn.rollback()

    conn.close()

    print()
    print("=" * 55)
    print(f"✓ Completado: {total_rows} filas en {len(tables)} tablas")
    if errors:
        print(f"✗ Errores en {len(errors)} tabla(s):")
        for name, err in errors:
            print(f"   • {name}: {err}")
    print()
    print("Próximos pasos:")
    print(f"  1. Conecta con psql y revisa el schema '{args.schema}'")
    print(f"  2. Adapta los modelos Django a las columnas importadas")
    print(f"  3. Escribe un script de transformación para mover datos")
    print(f"     al schema 'public' con los tipos correctos")
    print("=" * 55)


if __name__ == "__main__":
    main()
