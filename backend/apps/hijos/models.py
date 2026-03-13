from django.db import models


class NivelCurso(models.Model):
    club = models.ForeignKey(
        "clubs.Club",
        on_delete=models.CASCADE,
        related_name="niveles",
    )
    nombre = models.CharField(max_length=50)
    orden = models.PositiveSmallIntegerField(default=0)

    class Meta:
        verbose_name = "nivel/curso"
        verbose_name_plural = "niveles/cursos"
        ordering = ["club", "orden"]
        unique_together = [("club", "nombre")]

    def __str__(self):
        return f"{self.nombre} ({self.club.name})"


class Hijo(models.Model):
    """
    Hijo/a de una familia. Los hijos son los miembros individuales
    (alumnos) que pertenecen a una familia socio de un club.
    """
    class Estado(models.TextChoices):
        ACTIVO = "activo", "Activo"
        BAJA = "baja", "Baja"
        PENDIENTE = "pendiente", "Pendiente"

    familia = models.ForeignKey(
        "familias.Familia",
        on_delete=models.CASCADE,
        related_name="hijos",
    )
    nivel = models.ForeignKey(
        NivelCurso,
        on_delete=models.SET_NULL,
        null=True,
        blank=True,
        related_name="hijos",
    )
    nombre = models.CharField(max_length=100)
    apellidos = models.CharField(max_length=100)
    fecha_nacimiento = models.DateField(null=True, blank=True)
    fecha_alta = models.DateField(auto_now_add=True)
    fecha_baja = models.DateField(null=True, blank=True)
    estado = models.CharField(max_length=20, choices=Estado.choices, default=Estado.ACTIVO)
    notas = models.TextField(blank=True)

    class Meta:
        verbose_name = "hijo"
        verbose_name_plural = "hijos"
        ordering = ["apellidos", "nombre"]

    def __str__(self):
        return f"{self.apellidos}, {self.nombre}"

    @property
    def club(self):
        return self.familia.club
