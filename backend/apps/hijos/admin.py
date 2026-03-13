from django.contrib import admin
from .models import Hijo, NivelCurso


class HijoInline(admin.TabularInline):
    model = Hijo
    extra = 0
    fields = ["nombre", "apellidos", "nivel", "estado", "fecha_alta"]
    readonly_fields = ["fecha_alta"]


@admin.register(NivelCurso)
class NivelCursoAdmin(admin.ModelAdmin):
    list_display = ["nombre", "club", "orden"]
    list_filter = ["club"]
    ordering = ["club", "orden"]


@admin.register(Hijo)
class HijoAdmin(admin.ModelAdmin):
    list_display = ["apellidos", "nombre", "nivel", "get_club", "familia", "estado", "fecha_alta"]
    list_filter = ["estado", "nivel", "familia__club"]
    search_fields = ["nombre", "apellidos", "familia__apellidos"]
    ordering = ["apellidos", "nombre"]
    autocomplete_fields = ["familia", "nivel"]

    @admin.display(description="Club", ordering="familia__club__name")
    def get_club(self, obj):
        return obj.familia.club.name
