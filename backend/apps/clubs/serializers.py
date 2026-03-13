from rest_framework import serializers
from .models import Club, ClubMembership


class ClubSerializer(serializers.ModelSerializer):
    num_familias = serializers.IntegerField(read_only=True)
    num_miembros = serializers.IntegerField(read_only=True)

    class Meta:
        model = Club
        fields = [
            "id", "name", "slug", "logo", "description",
            "address", "phone", "email", "is_active",
            "created_at", "num_familias", "num_miembros",
        ]
        read_only_fields = ["slug", "created_at"]


class ClubMembershipSerializer(serializers.ModelSerializer):
    user_email = serializers.EmailField(source="user.email", read_only=True)
    user_name = serializers.SerializerMethodField()
    club_name = serializers.CharField(source="club.name", read_only=True)

    class Meta:
        model = ClubMembership
        fields = ["id", "user", "user_email", "user_name", "club", "club_name", "role", "created_at"]
        read_only_fields = ["created_at"]

    def get_user_name(self, obj):
        u = obj.user
        full = f"{u.first_name} {u.last_name}".strip()
        return full or u.email
