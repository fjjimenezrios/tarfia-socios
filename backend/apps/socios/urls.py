from rest_framework.routers import DefaultRouter
from .views import SocioViewSet, NivelCursoViewSet

router = DefaultRouter()
router.register("niveles", NivelCursoViewSet, basename="nivel")
router.register("", SocioViewSet, basename="socio")

urlpatterns = router.urls
