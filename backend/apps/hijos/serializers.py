from rest_framework import serializers
from .models import Hijo, NivelCurso


class NivelCursoSerializer(serializers.ModelSerializer):
    class Meta:
        model = NivelCurso
        fields = ["id", "nombre", "orden"]


class HijoSerializer(serializers.ModelSerializer):
    nivel_nombre = serializers.CharField(source="nivel.nombre", read_only=True)
    familia_apellidos = serializers.CharField(source="familia.apellidos", read_only=True)
    club_name = serializers.CharField(source="familia.club.name", read_only=True)

    class Meta:
        model = Hijo
        fields = "__all__"


class HijoListSerializer(serializers.ModelSerializer):
    nivel_nombre = serializers.CharField(source="nivel.nombre", read_only=True)
    familia_apellidos = serializers.CharField(source="familia.apellidos", read_only=True)

    class Meta:
        model = Hijo
        fields = [
            "id", "nombre", "apellidos", "nivel_nombre",
            "familia_apellidos", "estado", "fecha_alta",
        ]
