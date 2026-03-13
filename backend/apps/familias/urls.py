from rest_framework.routers import DefaultRouter
from .views import FamiliaViewSet

router = DefaultRouter()
router.register("", FamiliaViewSet, basename="familia")

urlpatterns = router.urls
