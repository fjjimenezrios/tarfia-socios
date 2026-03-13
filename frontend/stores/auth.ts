import { defineStore } from "pinia"

interface User {
  id: number
  email: string
  username: string
  first_name: string
  last_name: string
  avatar: string | null
}

export const useAuthStore = defineStore("auth", {
  state: () => ({
    user: null as User | null,
    accessToken: null as string | null,
    refreshToken: null as string | null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.accessToken,
  },

  actions: {
    async login(email: string, password: string) {
      const config = useRuntimeConfig()
      const data = await $fetch<{ access: string; refresh: string }>(
        `${config.public.apiBase}/auth/token/`,
        { method: "POST", body: { email, password } }
      )
      this.accessToken = data.access
      this.refreshToken = data.refresh
      if (import.meta.client) {
        localStorage.setItem("refresh_token", data.refresh)
      }
      await this.fetchMe()
    },

    async fetchMe() {
      const config = useRuntimeConfig()
      this.user = await $fetch<User>(`${config.public.apiBase}/auth/token/me/`, {
        headers: { Authorization: `Bearer ${this.accessToken}` },
      })
    },

    async refresh() {
      const config = useRuntimeConfig()
      const storedRefresh = import.meta.client ? localStorage.getItem("refresh_token") : null
      if (!storedRefresh) throw new Error("No refresh token")
      const data = await $fetch<{ access: string; refresh: string }>(
        `${config.public.apiBase}/auth/token/refresh/`,
        { method: "POST", body: { refresh: storedRefresh } }
      )
      this.accessToken = data.access
      this.refreshToken = data.refresh
      if (import.meta.client) {
        localStorage.setItem("refresh_token", data.refresh)
      }
    },

    async logout() {
      const config = useRuntimeConfig()
      if (this.refreshToken) {
        await $fetch(`${config.public.apiBase}/auth/token/logout/`, {
          method: "POST",
          body: { refresh: this.refreshToken },
          headers: { Authorization: `Bearer ${this.accessToken}` },
        }).catch(() => {})
      }
      this.user = null
      this.accessToken = null
      this.refreshToken = null
      if (import.meta.client) localStorage.removeItem("refresh_token")
      navigateTo("/auth/login")
    },
  },
})
