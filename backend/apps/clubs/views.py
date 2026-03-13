from django.db.models import Count
from rest_framework import viewsets, status
from rest_framework.decorators import action
from rest_framework.response import Response
from .models import Club, ClubMembership
from .serializers import ClubSerializer, ClubMembershipSerializer
from .permissions import IsSuperAdmin, IsClubAdmin, IsClubMember
from .permissions import get_membership


class ClubViewSet(viewsets.ModelViewSet):
    """
    Solo el superadmin puede crear/listar/editar/eliminar clubes.
    Los miembros del club pueden ver únicamente su propio club (GET).
    """
    serializer_class = ClubSerializer

    def get_permissions(self):
        if self.action in ("create", "destroy"):
            return [IsSuperAdmin()]
        if self.action in ("update", "partial_update"):
            return [IsClubAdmin()]
        return [IsClubMember()]

    def get_queryset(self):
        qs = Club.objects.annotate(
            num_familias=Count("familias", distinct=True),
            num_miembros=Count("memberships", distinct=True),
        )
        user = self.request.user
        if user.is_superuser:
            return qs
        m = get_membership(user)
        if m is None:
            return qs.none()
        return qs.filter(pk=m.club_id)

    @action(detail=True, methods=["get"], permission_classes=[IsClubAdmin])
    def members(self, request, pk=None):
        club = self.get_object()
        memberships = ClubMembership.objects.filter(club=club).select_related("user")
        serializer = ClubMembershipSerializer(memberships, many=True)
        return Response(serializer.data)


class ClubMembershipViewSet(viewsets.ModelViewSet):
    """
    Superadmin: gestiona membresías de cualquier club.
    Club admin: solo puede crear/editar/eliminar membresías de su propio club.
    """
    serializer_class = ClubMembershipSerializer

    def get_permissions(self):
        if self.action in ("create", "update", "partial_update", "destroy"):
            return [IsClubAdmin()]
        return [IsClubMember()]

    def get_queryset(self):
        user = self.request.user
        qs = ClubMembership.objects.select_related("user", "club")
        if user.is_superuser:
            return qs
        m = get_membership(user)
        if m is None:
            return qs.none()
        return qs.filter(club=m.club)

    def perform_create(self, serializer):
        user = self.request.user
        if not user.is_superuser:
            m = get_membership(user)
            # El admin solo puede crear membresías en su propio club
            serializer.save(club=m.club)
        else:
            serializer.save()
