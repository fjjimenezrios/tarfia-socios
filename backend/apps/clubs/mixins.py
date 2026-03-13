from .permissions import get_membership


class ClubScopedMixin:
    """
    Filtra automáticamente el queryset al club del usuario autenticado.
    El superadmin ve todos los registros.
    Requiere que el modelo tenga un campo `club` (FK a Club).
    """

    def get_queryset(self):
        qs = super().get_queryset()
        user = self.request.user
        if user.is_superuser:
            return qs
        m = get_membership(user)
        if m is None:
            return qs.none()
        return qs.filter(club=m.club)

    def perform_create(self, serializer):
        """Asigna el club automáticamente al crear un objeto."""
        user = self.request.user
        if user.is_superuser:
            # El superadmin debe pasar club explícitamente
            serializer.save()
        else:
            m = get_membership(user)
            serializer.save(club=m.club)
