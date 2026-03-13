from rest_framework import serializers
from .models import Familia


class FamiliaSerializer(serializers.ModelSerializer):
    num_socios = serializers.IntegerField(read_only=True)

    class Meta:
        model = Familia
        fields = "__all__"


class FamiliaListSerializer(serializers.ModelSerializer):
    """Serializer ligero para listados."""
    num_socios = serializers.IntegerField(read_only=True)

    class Meta:
        model = Familia
        fields = ["id", "apellidos", "nombre_tutor1", "email", "telefono", "localidad", "activa", "num_socios"]
