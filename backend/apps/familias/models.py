from django.db import models


class Familia(models.Model):
    apellidos = models.CharField("apellidos", max_length=100)
    nombre_tutor1 = models.CharField("tutor 1", max_length=100)
    nombre_tutor2 = models.CharField("tutor 2", max_length=100, blank=True)
    email = models.EmailField(blank=True)
    telefono = models.CharField(max_length=20, blank=True)
    telefono2 = models.CharField(max_length=20, blank=True)
    direccion = models.CharField(max_length=200, blank=True)
    localidad = models.CharField(max_length=100, blank=True)
    cp = models.CharField("C.P.", max_length=10, blank=True)
    notas = models.TextField(blank=True)
    fecha_alta = models.DateField(auto_now_add=True)
    activa = models.BooleanField(default=True)

    class Meta:
        verbose_name = "familia"
        verbose_name_plural = "familias"
        ordering = ["apellidos"]

    def __str__(self):
        return f"{self.apellidos} — {self.nombre_tutor1}"
