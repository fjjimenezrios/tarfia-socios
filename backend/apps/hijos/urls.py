from rest_framework.routers import DefaultRouter
from .views import HijoViewSet, NivelCursoViewSet

router = DefaultRouter()
router.register("niveles", NivelCursoViewSet, basename="nivel")
router.register("", HijoViewSet, basename="hijo")

urlpatterns = router.urls
