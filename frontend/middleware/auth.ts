export default defineNuxtRouteMiddleware((to) => {
  const auth = useAuthStore()
  if (!auth.isAuthenticated && !to.path.startsWith("/auth")) {
    return navigateTo("/auth/login")
  }
})
