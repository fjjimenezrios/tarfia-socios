from django.contrib import admin
from .models import Familia


@admin.register(Familia)
class FamiliaAdmin(admin.ModelAdmin):
    list_display = ["apellidos", "nombre_tutor1", "email", "telefono", "localidad", "activa"]
    list_filter = ["activa", "localidad"]
    search_fields = ["apellidos", "nombre_tutor1", "email"]
    ordering = ["apellidos"]
