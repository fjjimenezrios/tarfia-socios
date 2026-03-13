from django.contrib import admin
from .models import Familia


@admin.register(Familia)
class FamiliaAdmin(admin.ModelAdmin):
    list_display = ["apellidos", "nombre_tutor1", "club", "email", "telefono", "localidad", "activa", "cuota_pagada"]
    list_filter = ["activa", "cuota_pagada", "club"]
    search_fields = ["apellidos", "nombre_tutor1", "email"]
    ordering = ["club", "apellidos"]
    autocomplete_fields = ["club"]
