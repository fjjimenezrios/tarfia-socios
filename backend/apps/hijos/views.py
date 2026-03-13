from rest_framework import viewsets
from rest_framework.decorators import action
from rest_framework.response import Response
from django.db.models import Count
from django_filters.rest_framework import DjangoFilterBackend
from rest_framework.filters import SearchFilter, OrderingFilter
from apps.clubs.permissions import IsClubMember
from apps.clubs.permissions import get_membership
from .models import Hijo, NivelCurso
from .serializers import HijoSerializer, HijoListSerializer, NivelCursoSerializer


class HijoViewSet(viewsets.ModelViewSet):
    filter_backends = [DjangoFilterBackend, SearchFilter, OrderingFilter]
    filterset_fields = ["estado", "nivel", "familia"]
    search_fields = ["nombre", "apellidos"]
    ordering_fields = ["apellidos", "nombre", "fecha_alta", "estado"]
    permission_classes = [IsClubMember]

    def get_queryset(self):
        qs = Hijo.objects.select_related("familia__club", "nivel").order_by("apellidos", "nombre")
        user = self.request.user
        if user.is_superuser:
            return qs
        m = get_membership(user)
        if m is None:
            return qs.none()
        return qs.filter(familia__club=m.club)

    def get_serializer_class(self):
        if self.action == "list":
            return HijoListSerializer
        return HijoSerializer

    @action(detail=False, methods=["get"])
    def estadisticas(self, request):
        qs = self.get_queryset()
        total = qs.count()
        activos = qs.filter(estado="activo").count()
        por_nivel = (
            qs.filter(estado="activo")
            .values("nivel__nombre")
            .annotate(total=Count("id"))
            .order_by("nivel__orden")
        )
        # Familias con cuota pagada
        user = request.user
        if user.is_superuser:
            from apps.familias.models import Familia
            familias_qs = Familia.objects.all()
        else:
            m = get_membership(user)
            from apps.familias.models import Familia
            familias_qs = Familia.objects.filter(club=m.club) if m else Familia.objects.none()

        cuotas_pagadas = familias_qs.filter(activa=True, cuota_pagada=True).count()
        cuotas_pendientes = familias_qs.filter(activa=True, cuota_pagada=False).count()

        return Response({
            "total_hijos": total,
            "activos": activos,
            "bajas": total - activos,
            "cuotas_pagadas": cuotas_pagadas,
            "cuotas_pendientes": cuotas_pendientes,
            "por_nivel": list(por_nivel),
        })


class NivelCursoViewSet(viewsets.ModelViewSet):
    serializer_class = NivelCursoSerializer
    permission_classes = [IsClubMember]

    def get_queryset(self):
        qs = NivelCurso.objects.all()
        user = self.request.user
        if user.is_superuser:
            return qs
        m = get_membership(user)
        if m is None:
            return qs.none()
        return qs.filter(club=m.club)

    def perform_create(self, serializer):
        user = self.request.user
        if user.is_superuser:
            serializer.save()
        else:
            m = get_membership(user)
            serializer.save(club=m.club)
