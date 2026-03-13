from importlib import import_module

from django.conf import settings
from django.contrib.auth import get_user_model
from rest_framework.authentication import BaseAuthentication
from rest_framework.exceptions import AuthenticationFailed


class AllauthSessionTokenAuthentication(BaseAuthentication):
    """DRF authenticator that validates allauth headless session tokens.

    Reads the X-Session-Token header, loads the Django session behind it,
    and returns the authenticated user — exactly what allauth's
    SessionTokenStrategy writes on login.
    """

    def authenticate(self, request):
        token = request.META.get("HTTP_X_SESSION_TOKEN")
        if not token:
            return None

        SessionStore = import_module(settings.SESSION_ENGINE).SessionStore
        session = SessionStore(session_key=token)
        user_id = session.get("_auth_user_id")
        if not user_id:
            return None

        User = get_user_model()
        try:
            user = User.objects.get(pk=user_id)
        except User.DoesNotExist:
            raise AuthenticationFailed("User not found.")
        if not user.is_active:
            raise AuthenticationFailed("User is inactive.")
        return (user, None)

    def authenticate_header(self, request):
        return "X-Session-Token"
