from rest_framework.permissions import BasePermission


def get_membership(user):
    return getattr(user, "club_membership", None)


class IsSuperAdmin(BasePermission):
    """Solo el superadmin de la plataforma (is_superuser)."""

    def has_permission(self, request, view):
        return request.user.is_authenticated and request.user.is_superuser


class IsClubAdmin(BasePermission):
    """Superadmin o administrador del club."""

    def has_permission(self, request, view):
        if not request.user.is_authenticated:
            return False
        if request.user.is_superuser:
            return True
        m = get_membership(request.user)
        return m is not None and m.role == "admin"


class IsClubGerente(BasePermission):
    """Superadmin, admin o gerente del club."""

    def has_permission(self, request, view):
        if not request.user.is_authenticated:
            return False
        if request.user.is_superuser:
            return True
        m = get_membership(request.user)
        return m is not None and m.role in ("admin", "gerente")


class IsClubMember(BasePermission):
    """Cualquier usuario con membresía activa en un club (o superadmin)."""

    def has_permission(self, request, view):
        if not request.user.is_authenticated:
            return False
        if request.user.is_superuser:
            return True
        return get_membership(request.user) is not None
