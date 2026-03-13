export function useApi() {
  const config = useRuntimeConfig()
  const auth = useAuthStore()

  async function apiFetch<T>(path: string, options: Parameters<typeof $fetch>[1] = {}): Promise<T> {
    try {
      return await $fetch<T>(`${config.public.apiBase}${path}`, {
        ...options,
        headers: {
          ...(options.headers as Record<string, string>),
          ...(auth.accessToken ? { Authorization: `Bearer ${auth.accessToken}` } : {}),
        },
      })
    } catch (err: any) {
      if (err?.response?.status === 401) {
        // Intenta renovar el token
        await auth.refresh()
        return $fetch<T>(`${config.public.apiBase}${path}`, {
          ...options,
          headers: {
            ...(options.headers as Record<string, string>),
            Authorization: `Bearer ${auth.accessToken}`,
          },
        })
      }
      throw err
    }
  }

  return { apiFetch }
}
