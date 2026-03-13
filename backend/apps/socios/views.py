from rest_framework import viewsets
from rest_framework.decorators import action
from rest_framework.response import Response
from django_filters.rest_framework import DjangoFilterBackend
from rest_framework.filters import SearchFilter, OrderingFilter
from django.db.models import Count
from .models import Socio, NivelCurso
from .serializers import SocioSerializer, SocioListSerializer, NivelCursoSerializer


class SocioViewSet(viewsets.ModelViewSet):
    queryset = Socio.objects.select_related("familia", "nivel").order_by("apellidos", "nombre")
    filter_backends = [DjangoFilterBackend, SearchFilter, OrderingFilter]
    filterset_fields = ["estado", "nivel", "cuota_pagada", "familia"]
    search_fields = ["nombre", "apellidos"]
    ordering_fields = ["apellidos", "nombre", "fecha_alta", "estado"]

    def get_serializer_class(self):
        if self.action == "list":
            return SocioListSerializer
        return SocioSerializer

    @action(detail=False, methods=["get"])
    def estadisticas(self, request):
        total = Socio.objects.count()
        activos = Socio.objects.filter(estado="activo").count()
        por_nivel = (
            Socio.objects
            .filter(estado="activo")
            .values("nivel__nombre")
            .annotate(total=Count("id"))
            .order_by("nivel__orden")
        )
        cuotas_pagadas = Socio.objects.filter(estado="activo", cuota_pagada=True).count()
        return Response({
            "total": total,
            "activos": activos,
            "bajas": total - activos,
            "cuotas_pagadas": cuotas_pagadas,
            "cuotas_pendientes": activos - cuotas_pagadas,
            "por_nivel": list(por_nivel),
        })


class NivelCursoViewSet(viewsets.ReadOnlyModelViewSet):
    queryset = NivelCurso.objects.all()
    serializer_class = NivelCursoSerializer
