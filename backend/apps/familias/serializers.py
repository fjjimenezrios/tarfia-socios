from rest_framework import serializers
from .models import Familia


class FamiliaSerializer(serializers.ModelSerializer):
    num_hijos = serializers.IntegerField(read_only=True)

    class Meta:
        model = Familia
        exclude = ["club"]  # el club se asigna automáticamente por el mixin


class FamiliaListSerializer(serializers.ModelSerializer):
    num_hijos = serializers.IntegerField(read_only=True)

    class Meta:
        model = Familia
        fields = [
            "id", "apellidos", "nombre_tutor1", "email",
            "telefono", "localidad", "activa", "cuota_pagada", "num_hijos",
        ]
