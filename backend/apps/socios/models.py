from django.db import models
from apps.familias.models import Familia


class NivelCurso(models.Model):
    nombre = models.CharField(max_length=50, unique=True)
    orden = models.PositiveSmallIntegerField(default=0)

    class Meta:
        verbose_name = "nivel/curso"
        verbose_name_plural = "niveles/cursos"
        ordering = ["orden"]

    def __str__(self):
        return self.nombre


class Socio(models.Model):
    class Estado(models.TextChoices):
        ACTIVO = "activo", "Activo"
        BAJA = "baja", "Baja"
        PENDIENTE = "pendiente", "Pendiente"

    familia = models.ForeignKey(
        Familia,
        on_delete=models.SET_NULL,
        null=True,
        blank=True,
        related_name="socios",
    )
    nombre = models.CharField(max_length=100)
    apellidos = models.CharField(max_length=100)
    nivel = models.ForeignKey(
        NivelCurso,
        on_delete=models.SET_NULL,
        null=True,
        blank=True,
        related_name="socios",
    )
    fecha_nacimiento = models.DateField(null=True, blank=True)
    fecha_alta = models.DateField(auto_now_add=True)
    fecha_baja = models.DateField(null=True, blank=True)
    estado = models.CharField(max_length=20, choices=Estado.choices, default=Estado.ACTIVO)
    cuota_pagada = models.BooleanField(default=False)
    notas = models.TextField(blank=True)

    class Meta:
        verbose_name = "socio"
        verbose_name_plural = "socios"
        ordering = ["apellidos", "nombre"]

    def __str__(self):
        return f"{self.apellidos}, {self.nombre}"

    @property
    def nombre_completo(self):
        return f"{self.nombre} {self.apellidos}"
