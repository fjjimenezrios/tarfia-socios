from django.contrib import admin
from .models import Socio, NivelCurso


@admin.register(NivelCurso)
class NivelCursoAdmin(admin.ModelAdmin):
    list_display = ["nombre", "orden"]
    ordering = ["orden"]


@admin.register(Socio)
class SocioAdmin(admin.ModelAdmin):
    list_display = ["apellidos", "nombre", "nivel", "familia", "estado", "cuota_pagada", "fecha_alta"]
    list_filter = ["estado", "nivel", "cuota_pagada"]
    search_fields = ["nombre", "apellidos"]
    ordering = ["apellidos", "nombre"]
    autocomplete_fields = ["familia"]
