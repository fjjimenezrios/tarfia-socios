<script setup lang="ts">
definePageMeta({ middleware: "auth" })

const { apiFetch } = useApi()

const { data: stats, pending } = await useAsyncData("stats", () =>
  apiFetch<{
    total: number
    activos: number
    bajas: number
    cuotas_pagadas: number
    cuotas_pendientes: number
    por_nivel: { nivel__nombre: string; total: number }[]
  }>("/socios/estadisticas/")
)

const cards = computed(() => [
  {
    label: "Socios activos",
    value: stats.value?.activos ?? 0,
    icon: "i-heroicons-users",
    color: "text-primary-500",
    bg: "bg-primary-50 dark:bg-primary-950",
  },
  {
    label: "Total socios",
    value: stats.value?.total ?? 0,
    icon: "i-heroicons-user-group",
    color: "text-gray-500",
    bg: "bg-gray-50 dark:bg-gray-900",
  },
  {
    label: "Cuotas pagadas",
    value: stats.value?.cuotas_pagadas ?? 0,
    icon: "i-heroicons-banknotes",
    color: "text-green-500",
    bg: "bg-green-50 dark:bg-green-950",
  },
  {
    label: "Cuotas pendientes",
    value: stats.value?.cuotas_pendientes ?? 0,
    icon: "i-heroicons-clock",
    color: "text-amber-500",
    bg: "bg-amber-50 dark:bg-amber-950",
  },
])
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Inicio</h1>
      <p class="text-gray-500 text-sm mt-1">Resumen general del curso actual</p>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <UCard
        v-for="card in cards"
        :key="card.label"
        :ui="{ body: { padding: 'p-5' } }"
      >
        <div class="flex items-center gap-4">
          <div :class="['w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0', card.bg]">
            <UIcon :name="card.icon" :class="['w-6 h-6', card.color]" />
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
              <USkeleton v-if="pending" class="h-7 w-10" />
              <span v-else>{{ card.value }}</span>
            </p>
            <p class="text-sm text-gray-500">{{ card.label }}</p>
          </div>
        </div>
      </UCard>
    </div>

    <!-- Por nivel -->
    <UCard>
      <template #header>
        <h2 class="font-semibold text-gray-900 dark:text-white">Socios por nivel/curso</h2>
      </template>

      <div v-if="pending" class="space-y-2">
        <USkeleton v-for="i in 5" :key="i" class="h-8 w-full" />
      </div>
      <div v-else class="space-y-2">
        <div
          v-for="nivel in stats?.por_nivel"
          :key="nivel.nivel__nombre"
          class="flex items-center gap-3"
        >
          <span class="text-sm text-gray-600 dark:text-gray-400 w-40 truncate">
            {{ nivel.nivel__nombre ?? "Sin nivel" }}
          </span>
          <UProgress
            :value="nivel.total"
            :max="stats?.activos || 1"
            class="flex-1"
            size="sm"
          />
          <span class="text-sm font-medium text-gray-900 dark:text-white w-8 text-right">
            {{ nivel.total }}
          </span>
        </div>
      </div>
    </UCard>
  </div>
</template>
