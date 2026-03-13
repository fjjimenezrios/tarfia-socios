from rest_framework import serializers
from .models import Socio, NivelCurso


class NivelCursoSerializer(serializers.ModelSerializer):
    class Meta:
        model = NivelCurso
        fields = ["id", "nombre", "orden"]


class SocioSerializer(serializers.ModelSerializer):
    nombre_completo = serializers.CharField(read_only=True)
    nivel_nombre = serializers.CharField(source="nivel.nombre", read_only=True)
    familia_apellidos = serializers.CharField(source="familia.apellidos", read_only=True)

    class Meta:
        model = Socio
        fields = "__all__"


class SocioListSerializer(serializers.ModelSerializer):
    """Serializer ligero para listados."""
    nivel_nombre = serializers.CharField(source="nivel.nombre", read_only=True)
    familia_apellidos = serializers.CharField(source="familia.apellidos", read_only=True)

    class Meta:
        model = Socio
        fields = [
            "id", "nombre", "apellidos", "nivel_nombre",
            "familia_apellidos", "estado", "cuota_pagada", "fecha_alta",
        ]
