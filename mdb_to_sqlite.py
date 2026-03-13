#!/usr/bin/env python3
"""
mdb_to_sqlite.py — Convierte un archivo .mdb a SQLite usando mdbtools
Uso: python3 mdb_to_sqlite.py socios-tarfia.mdb socios-tarfia.db
"""

import subprocess
import sqlite3
import csv
import sys
import io
import os

# Aumentar límite de campo CSV para tablas con campos grandes (imágenes, memos, etc.)
csv.field_size_limit(10 * 1024 * 1024)  # 10 MB


def get_tables(mdb_file):
    result = subprocess.run(
        ["mdb-tables", "-1", mdb_file],
        capture_output=True, text=True
    )
    tables = [t.strip() for t in result.stdout.strip().split("\n") if t.strip()]
    return tables


def get_columns(mdb_file, table):
    result = subprocess.run(
        ["mdb-export", "-b", "strip", "-D", "%Y-%m-%d", "-T", "%Y-%m-%d %H:%M:%S", mdb_file, table],
        capture_output=True, text=True, errors="replace"
    )
    if not result.stdout:
        return []
    reader = csv.reader(io.StringIO(result.stdout))
    headers = next(reader, [])
    return headers


def sanitize_name(name):
    """Convierte nombre de tabla/columna a algo seguro para SQLite."""
    return name.replace("/", "_").replace(" ", "_").replace("-", "_").replace("ó", "o").replace("é", "e").replace("í", "i").replace("á", "a").replace("ú", "u").replace("Ñ", "N").replace("ñ", "n").replace("º", "num").replace("Ú", "U")


def export_table_to_sqlite(mdb_file, table, conn):
    print(f"  → Exportando columnas...")
    columns = get_columns(mdb_file, table)
    if not columns:
        print(f"  ⚠ Sin columnas, saltando.")
        return 0

    safe_table = sanitize_name(table)
    safe_columns = [sanitize_name(c) for c in columns]

    # Crear tabla (todas las columnas como TEXT, PHP lo maneja bien)
    cols_def = ", ".join([f'"{c}" TEXT' for c in safe_columns])
    conn.execute(f'DROP TABLE IF EXISTS "{safe_table}"')
    conn.execute(f'CREATE TABLE "{safe_table}" ({cols_def})')

    # Exportar datos como CSV (strip elimina campos OLE/binarios, fechas en ISO 8601)
    result = subprocess.run(
        ["mdb-export", "-b", "strip", "-D", "%Y-%m-%d", "-T", "%Y-%m-%d %H:%M:%S", mdb_file, table],
        capture_output=True, text=True, errors="replace"
    )
    if not result.stdout:
        print(f"  ⚠ Sin datos.")
        return 0

    reader = csv.reader(io.StringIO(result.stdout))
    next(reader)  # saltar cabecera

    placeholders = ", ".join(["?" for _ in safe_columns])
    insert_sql = f'INSERT INTO "{safe_table}" VALUES ({placeholders})'

    count = 0
    batch = []
    for row in reader:
        # Normalizar longitud de fila
        while len(row) < len(safe_columns):
            row.append("")
        row = row[:len(safe_columns)]

        # Limpiar campos binarios (contienen bytes raros)
        clean_row = []
        for val in row:
            try:
                val.encode("utf-8")
                clean_row.append(val)
            except Exception:
                clean_row.append("")
        batch.append(clean_row)
        count += 1

        if len(batch) >= 500:
            conn.executemany(insert_sql, batch)
            batch = []

    if batch:
        conn.executemany(insert_sql, batch)

    conn.commit()
    return count


def main():
    if len(sys.argv) < 3:
        print("Uso: python3 mdb_to_sqlite.py archivo.mdb salida.db")
        sys.exit(1)

    mdb_file = sys.argv[1]
    sqlite_file = sys.argv[2]

    if not os.path.exists(mdb_file):
        print(f"Error: no se encuentra {mdb_file}")
        sys.exit(1)

    # Verificar mdbtools instalado
    try:
        subprocess.run(["mdb-tables", "--version"], capture_output=True)
    except FileNotFoundError:
        print("Error: mdbtools no está instalado. Ejecuta: brew install mdbtools")
        sys.exit(1)

    print(f"\n{'='*50}")
    print(f"  MDB → SQLite")
    print(f"  Origen:  {mdb_file}")
    print(f"  Destino: {sqlite_file}")
    print(f"{'='*50}\n")

    tables = get_tables(mdb_file)
    print(f"Tablas encontradas ({len(tables)}):")
    for t in tables:
        print(f"  • {t}")
    print()

    # Borrar db anterior si existe
    if os.path.exists(sqlite_file):
        os.remove(sqlite_file)
        print(f"⚠ Se sobreescribe {sqlite_file}\n")

    conn = sqlite3.connect(sqlite_file)

    total_rows = 0
    for i, table in enumerate(tables, 1):
        print(f"[{i}/{len(tables)}] {table}")
        try:
            rows = export_table_to_sqlite(mdb_file, table, conn)
            print(f"  ✓ {rows} filas importadas\n")
            total_rows += rows
        except Exception as e:
            print(f"  ✗ Error: {e}\n")

    conn.close()

    size = os.path.getsize(sqlite_file) / 1024
    print(f"{'='*50}")
    print(f"✓ Completado: {total_rows} filas en {len(tables)} tablas")
    print(f"✓ Archivo:    {sqlite_file} ({size:.1f} KB)")
    print(f"{'='*50}\n")

    # Mostrar resumen de tablas
    conn = sqlite3.connect(sqlite_file)
    print("Resumen final:")
    for table in tables:
        safe = sanitize_name(table)
        try:
            cur = conn.execute(f'SELECT COUNT(*) FROM "{safe}"')
            count = cur.fetchone()[0]
            print(f"  {safe:<40} {count:>6} filas")
        except Exception:
            print(f"  {safe:<40}  ERROR")
    conn.close()


if __name__ == "__main__":
    main()
