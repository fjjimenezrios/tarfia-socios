export default defineNuxtConfig({
  compatibilityDate: "2024-11-01",
  devtools: { enabled: true },

  modules: ["@nuxt/ui", "@pinia/nuxt"],

  ui: {
    // Nuxt UI usa Tailwind + colores configurables
  },

  // Llama a la API de Django
  runtimeConfig: {
    public: {
      apiBase: process.env.API_BASE || "http://localhost:8000/api",
    },
  },

  // SSR desactivado: SPA estática servida por Nginx
  ssr: false,

  app: {
    head: {
      title: "TarfíaDB",
      meta: [
        { charset: "utf-8" },
        { name: "viewport", content: "width=device-width, initial-scale=1" },
        { name: "description", content: "Gestión de socios y familias — AMPA Tarfía" },
      ],
      link: [
        { rel: "icon", type: "image/x-icon", href: "/favicon.ico" },
      ],
    },
  },
})
