from django.db import models
from django.utils.text import slugify


class Club(models.Model):
    name = models.CharField("nombre", max_length=100)
    slug = models.SlugField(unique=True, blank=True)
    logo = models.ImageField(upload_to="clubs/logos/", blank=True, null=True)
    description = models.TextField("descripción", blank=True)
    address = models.CharField("dirección", max_length=200, blank=True)
    phone = models.CharField("teléfono", max_length=20, blank=True)
    email = models.EmailField(blank=True)
    is_active = models.BooleanField("activo", default=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = "club"
        verbose_name_plural = "clubes"
        ordering = ["name"]

    def __str__(self):
        return self.name

    def save(self, *args, **kwargs):
        if not self.slug:
            self.slug = slugify(self.name)
        super().save(*args, **kwargs)


class ClubMembership(models.Model):
    class Role(models.TextChoices):
        ADMIN = "admin", "Administrador"
        PRECEPTOR = "preceptor", "Preceptor"
        GERENTE = "gerente", "Gerente"

    # Un usuario pertenece a UN solo club con UN solo rol
    user = models.OneToOneField(
        "accounts.User",
        on_delete=models.CASCADE,
        related_name="club_membership",
    )
    club = models.ForeignKey(
        Club,
        on_delete=models.CASCADE,
        related_name="memberships",
    )
    role = models.CharField(max_length=20, choices=Role.choices)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = "miembro del club"
        verbose_name_plural = "miembros del club"

    def __str__(self):
        return f"{self.user.email} — {self.get_role_display()} @ {self.club.name}"
