from django.db import models


class Familia(models.Model):
    """
    La familia es la unidad de socio: quien se inscribe y paga la cuota.
    Pertenece a un único club.
    """
    club = models.ForeignKey(
        "clubs.Club",
        on_delete=models.CASCADE,
        related_name="familias",
    )
    apellidos = models.CharField("apellidos", max_length=100)
    nombre_tutor1 = models.CharField("tutor 1", max_length=100)
    nombre_tutor2 = models.CharField("tutor 2", max_length=100, blank=True)
    email = models.EmailField(blank=True)
    telefono = models.CharField(max_length=20, blank=True)
    telefono2 = models.CharField(max_length=20, blank=True)
    direccion = models.CharField(max_length=200, blank=True)
    localidad = models.CharField(max_length=100, blank=True)
    cp = models.CharField("C.P.", max_length=10, blank=True)
    cuota_pagada = models.BooleanField("cuota pagada", default=False)
    activa = models.BooleanField(default=True)
    notas = models.TextField(blank=True)
    fecha_alta = models.DateField(auto_now_add=True)

    class Meta:
        verbose_name = "familia"
        verbose_name_plural = "familias"
        ordering = ["apellidos"]

    def __str__(self):
        return f"{self.apellidos} — {self.nombre_tutor1} ({self.club.name})"
