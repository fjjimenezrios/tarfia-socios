export default defineNuxtPlugin(async () => {
  const auth = useAuthStore()
  const storedToken = localStorage.getItem("session_token")
  if (storedToken && !auth.isAuthenticated) {
    auth.sessionToken = storedToken
    try {
      await auth.fetchMe()
    } catch {
      auth.sessionToken = null
      localStorage.removeItem("session_token")
    }
  }
})
