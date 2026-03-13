from rest_framework.routers import DefaultRouter
from .views import ClubViewSet, ClubMembershipViewSet

router = DefaultRouter()
router.register("memberships", ClubMembershipViewSet, basename="membership")
router.register("", ClubViewSet, basename="club")

urlpatterns = router.urls
