from django.contrib import admin
from .models import Club, ClubMembership


class ClubMembershipInline(admin.TabularInline):
    model = ClubMembership
    extra = 0
    fields = ["user", "role", "created_at"]
    readonly_fields = ["created_at"]
    autocomplete_fields = ["user"]


@admin.register(Club)
class ClubAdmin(admin.ModelAdmin):
    list_display = ["name", "slug", "email", "is_active", "created_at"]
    list_filter = ["is_active"]
    search_fields = ["name", "email"]
    prepopulated_fields = {"slug": ("name",)}
    inlines = [ClubMembershipInline]


@admin.register(ClubMembership)
class ClubMembershipAdmin(admin.ModelAdmin):
    list_display = ["user", "club", "role", "created_at"]
    list_filter = ["role", "club"]
    search_fields = ["user__email", "club__name"]
    autocomplete_fields = ["user", "club"]
