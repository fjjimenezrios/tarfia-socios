from rest_framework import serializers
from .models import User


class ClubMembershipBriefSerializer(serializers.Serializer):
    club_id = serializers.IntegerField(source="club.id")
    club_name = serializers.CharField(source="club.name")
    club_slug = serializers.CharField(source="club.slug")
    role = serializers.CharField()


class UserSerializer(serializers.ModelSerializer):
    membership = serializers.SerializerMethodField()
    is_superadmin = serializers.BooleanField(source="is_superuser", read_only=True)

    class Meta:
        model = User
        fields = ["id", "email", "username", "first_name", "last_name", "avatar", "is_superadmin", "membership"]
        read_only_fields = ["id", "email", "is_superadmin", "membership"]

    def get_membership(self, obj):
        m = getattr(obj, "club_membership", None)
        if m is None:
            return None
        return ClubMembershipBriefSerializer(m).data
