from django.contrib import admin
from django.urls import path, include
from django.conf import settings
from django.conf.urls.static import static

urlpatterns = [
    path("admin/", admin.site.urls),
    # allauth headless REST API (auth + MFA + social)
    path("api/auth/", include("allauth.headless.urls")),
    # JWT token endpoints
    path("api/auth/token/", include("apps.accounts.urls")),
    # App APIs
    path("api/clubs/", include("apps.clubs.urls")),
    path("api/familias/", include("apps.familias.urls")),
    path("api/hijos/", include("apps.hijos.urls")),
]

if settings.DEBUG:
    import debug_toolbar
    urlpatterns = [path("__debug__/", include(debug_toolbar.urls))] + urlpatterns
    urlpatterns += static(settings.MEDIA_URL, document_root=settings.MEDIA_ROOT)
