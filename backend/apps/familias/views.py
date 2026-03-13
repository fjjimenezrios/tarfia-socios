from django.db.models import Count
from rest_framework import viewsets
from django_filters.rest_framework import DjangoFilterBackend
from rest_framework.filters import SearchFilter, OrderingFilter
from apps.clubs.mixins import ClubScopedMixin
from apps.clubs.permissions import IsClubMember
from .models import Familia
from .serializers import FamiliaSerializer, FamiliaListSerializer


class FamiliaViewSet(ClubScopedMixin, viewsets.ModelViewSet):
    queryset = Familia.objects.annotate(num_hijos=Count("hijos")).order_by("apellidos")
    permission_classes = [IsClubMember]
    filter_backends = [DjangoFilterBackend, SearchFilter, OrderingFilter]
    filterset_fields = ["activa", "localidad", "cuota_pagada"]
    search_fields = ["apellidos", "nombre_tutor1", "email", "localidad"]
    ordering_fields = ["apellidos", "fecha_alta"]

    def get_serializer_class(self):
        if self.action == "list":
            return FamiliaListSerializer
        return FamiliaSerializer
