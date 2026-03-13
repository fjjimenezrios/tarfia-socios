"""
importar_mdb.py — Importa datos desde el SQLite generado por mdb_to_sqlite.py
Uso: python manage.py importar_mdb /ruta/socios-tarfia.db
"""

import sqlite3
from datetime import datetime

from django.core.management.base import BaseCommand, CommandError
from django.db import transaction

from apps.familias.models import Familia
from apps.socios.models import NivelCurso, Socio


def parse_date(val):
    if not val:
        return None
    for fmt in ("%Y-%m-%d %H:%M:%S", "%Y-%m-%d"):
        try:
            return datetime.strptime(val.strip(), fmt).date()
        except (ValueError, AttributeError):
            continue
    return None


def clean(val):
    return (val or "").strip()


class Command(BaseCommand):
    help = "Importa familias y socios desde el SQLite exportado del .mdb"

    def add_arguments(self, parser):
        parser.add_argument("sqlite_path", help="Ruta al archivo socios-tarfia.db")

    def handle(self, *args, **options):
        db_path = options["sqlite_path"]
        try:
            conn = sqlite3.connect(db_path)
            conn.row_factory = sqlite3.Row
        except Exception as e:
            raise CommandError(f"No se puede abrir {db_path}: {e}")

        with transaction.atomic():
            self._importar_niveles(conn)
            familia_map, apellidos_map = self._importar_familias(conn)
            self._importar_socios(conn, familia_map, apellidos_map)

        conn.close()
        self.stdout.write(self.style.SUCCESS("✓ Importación completada."))

    def _importar_niveles(self, conn):
        self.stdout.write("Importando niveles/cursos...")
        NivelCurso.objects.all().delete()
        rows = conn.execute("SELECT Nivel, Curso FROM Niveles_Cursos ORDER BY Nivel").fetchall()
        niveles = [
            NivelCurso(nombre=clean(r["Curso"]), orden=int(clean(r["Nivel"]) or 0))
            for r in rows
        ]
        NivelCurso.objects.bulk_create(niveles)
        self.stdout.write(f"  {len(niveles)} niveles importados")

    def _importar_familias(self, conn):
        self.stdout.write("Importando familias...")
        Familia.objects.all().delete()
        rows = conn.execute("SELECT * FROM Familias_Socios").fetchall()

        familia_map = {}   # mdb_id (str) → Familia.pk
        apellidos_map = {} # mdb_id (str) → apellidos string

        familias = []
        ids = []

        for r in rows:
            mdb_id = clean(r["Id"])
            apellidos = clean(r["Apellidos"]) or "—"

            nombre_t1 = clean(r["Nombre_padre"])
            ape_t1 = clean(r["Apellidos_padre"])
            tutor1 = f"{nombre_t1} {ape_t1}".strip() or "—"

            nombre_t2 = clean(r["Nombre_madre"])
            ape_t2 = clean(r["Apellidos_madre"])
            tutor2 = f"{nombre_t2} {ape_t2}".strip()

            # Primer teléfono disponible como principal
            tel_fijo = clean(r["Telefono"])
            tel_padre = clean(r["Movil_Padre"])
            tel_madre = clean(r["Movil_Madre"])
            telefono = tel_fijo or tel_padre or tel_madre
            # Segundo teléfono: el siguiente disponible que sea distinto
            telefono2 = next(
                (t for t in [tel_padre, tel_madre, tel_fijo] if t and t != telefono),
                ""
            )

            familias.append(Familia(
                apellidos=apellidos,
                nombre_tutor1=tutor1,
                nombre_tutor2=tutor2,
                email=clean(r["e_mail"]),
                telefono=telefono,
                telefono2=telefono2,
                direccion=clean(r["Direccion"]),
                localidad=clean(r["Localidad"]),
                cp=clean(r["CP"]),
                notas=clean(r["Observaciones"]),
            ))
            ids.append(mdb_id)
            apellidos_map[mdb_id] = apellidos

        created = Familia.objects.bulk_create(familias)
        for mdb_id, familia in zip(ids, created):
            familia_map[mdb_id] = familia.pk

        self.stdout.write(f"  {len(created)} familias importadas")
        return familia_map, apellidos_map

    def _importar_socios(self, conn, familia_map, apellidos_map):
        self.stdout.write("Importando socios...")
        Socio.objects.all().delete()

        # Mapa de nivel por orden numérico (el campo Nivel en MDB es el Id)
        nivel_by_orden = {n.orden: n for n in NivelCurso.objects.all()}
        nivel_by_nombre = {n.nombre: n for n in NivelCurso.objects.all()}

        rows = conn.execute("SELECT * FROM Socios").fetchall()
        socios = []
        sin_familia = 0

        for r in rows:
            mdb_familia_id = clean(r["IdFamilia"])
            familia_pk = familia_map.get(mdb_familia_id)
            if not familia_pk:
                sin_familia += 1

            apellidos = apellidos_map.get(mdb_familia_id, "")

            # Nivel: el campo es el Id numérico del nivel
            nivel = None
            nivel_raw = clean(r["Nivel"])
            if nivel_raw:
                try:
                    nivel = nivel_by_orden.get(int(nivel_raw))
                except ValueError:
                    nivel = nivel_by_nombre.get(nivel_raw)

            # Estado: campo booleano "Socio" (1=activo, 0=baja)
            fecha_baja = parse_date(r["Fecha_de_Baja"])
            es_socio = clean(r["Socio"]) == "1"
            estado = Socio.Estado.ACTIVO if es_socio else Socio.Estado.BAJA

            cuota_raw = clean(r["Cuota"])
            try:
                cuota = float(cuota_raw) if cuota_raw else None
            except ValueError:
                cuota = None

            socios.append(Socio(
                familia_id=familia_pk,
                nombre=clean(r["Nombre"]),
                apellidos=apellidos,
                nivel=nivel,
                fecha_nacimiento=parse_date(r["Fecha_de_Nacimiento"]),
                fecha_baja=fecha_baja,
                estado=estado,
                cuota=cuota,
                notas=clean(r["Observaciones"]),
            ))

        created = Socio.objects.bulk_create(socios)
        if sin_familia:
            self.stdout.write(self.style.WARNING(f"  ⚠ {sin_familia} socios sin familia encontrada"))
        self.stdout.write(f"  {len(created)} socios importados")
