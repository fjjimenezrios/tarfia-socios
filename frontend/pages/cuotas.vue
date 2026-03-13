<script setup lang="ts">
definePageMeta({ middleware: "auth" })

const { apiFetch } = useApi()

interface NivelStat {
  nivel__nombre: string | null
  nivel__orden: number
  n_familias: number
  total_cuota: number
}
interface FamiliaCuota {
  familia_id: number | null
  apellidos: string
  socios_nombres: string[]
  niveles: string[]
  cuota: number
}
interface CuotasStats {
  num_familias: number
  ingresos_mensuales: number
  ingresos_anuales: number
  cuota_media: number
  por_nivel: NivelStat[]
  familias: FamiliaCuota[]
}

const { data: stats, pending } = await useAsyncData("cuotas-stats", () =>
  apiFetch<CuotasStats>("/socios/cuotas_stats/")
)

const search = ref("")

const familiasFiltradas = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return stats.value?.familias ?? []
  return (stats.value?.familias ?? []).filter(f =>
    f.apellidos.toLowerCase().includes(q) ||
    f.socios_nombres.some(n => n.toLowerCase().includes(q)) ||
    f.niveles.some(n => n.toLowerCase().includes(q))
  )
})

function fmt(n: number) {
  return n.toLocaleString("es-ES", { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<template>
  <div class="space-y-6">
    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Cuotas</h1>

    <!-- Stat cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-5">
        <p class="text-gray-500 dark:text-slate-400 text-sm">Familias que pagan</p>
        <p class="text-4xl font-bold text-gray-900 dark:text-white mt-1">
          {{ pending ? '—' : (stats?.num_familias ?? 0) }}
        </p>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-2">con cuota activa</p>
      </div>

      <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-5">
        <p class="text-gray-500 dark:text-slate-400 text-sm">Ingresos mensuales</p>
        <p class="text-4xl font-bold text-amber-600 dark:text-amber-500 mt-1">
          {{ pending ? '—' : fmt(stats?.ingresos_mensuales ?? 0) }} €
        </p>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-2">
          media {{ pending ? '—' : fmt(stats?.cuota_media ?? 0) }} €/familia
        </p>
      </div>

      <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-5">
        <p class="text-gray-500 dark:text-slate-400 text-sm">Ingresos anuales</p>
        <p class="text-4xl font-bold text-teal-600 dark:text-teal-400 mt-1">
          {{ pending ? '—' : fmt(stats?.ingresos_anuales ?? 0) }} €
        </p>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-2">estimación × 12 meses</p>
      </div>
    </div>

    <!-- Desglose por nivel + familias -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <!-- Desglose por nivel -->
      <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-5">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-4">Desglose por nivel</h2>

        <div v-if="pending" class="space-y-2">
          <div v-for="i in 6" :key="i" class="h-6 bg-gray-100 dark:bg-slate-800 rounded animate-pulse" />
        </div>

        <div v-else>
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-100 dark:border-slate-800">
                <th class="pb-2 text-left font-medium text-gray-500 dark:text-slate-500">Nivel</th>
                <th class="pb-2 text-right font-medium text-gray-500 dark:text-slate-500">Familias</th>
                <th class="pb-2 text-right font-medium text-gray-500 dark:text-slate-500">Total/mes</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-800">
              <tr v-for="nivel in stats?.por_nivel" :key="nivel.nivel__nombre ?? nivel.nivel__orden">
                <td class="py-2 text-gray-700 dark:text-slate-300">{{ nivel.nivel__nombre ?? '—' }}</td>
                <td class="py-2 text-right text-gray-500 dark:text-slate-400">{{ nivel.n_familias }}</td>
                <td class="py-2 text-right font-medium text-amber-600 dark:text-amber-500">{{ fmt(nivel.total_cuota) }} €</td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="border-t border-gray-200 dark:border-slate-700">
                <td class="pt-2 font-semibold text-gray-900 dark:text-white">Total</td>
                <td class="pt-2 text-right font-semibold text-gray-900 dark:text-white">{{ stats?.num_familias }}</td>
                <td class="pt-2 text-right font-semibold text-amber-600 dark:text-amber-500">{{ fmt(stats?.ingresos_mensuales ?? 0) }} €</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Familias que pagan cuota -->
      <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-5">
        <div class="flex items-center justify-between mb-4 gap-3 flex-wrap">
          <h2 class="font-semibold text-gray-900 dark:text-white">Familias que pagan cuota</h2>
          <UInput
            v-model="search"
            icon="i-heroicons-magnifying-glass"
            placeholder="Buscar familia..."
            size="sm"
            class="w-52"
          />
        </div>

        <div v-if="pending" class="space-y-2">
          <div v-for="i in 8" :key="i" class="h-8 bg-gray-100 dark:bg-slate-800 rounded animate-pulse" />
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-100 dark:border-slate-800">
                <th class="pb-2 text-left font-medium text-gray-500 dark:text-slate-500">Familia</th>
                <th class="pb-2 text-left font-medium text-gray-500 dark:text-slate-500 hidden sm:table-cell">Socios</th>
                <th class="pb-2 text-left font-medium text-gray-500 dark:text-slate-500 hidden md:table-cell">Niveles</th>
                <th class="pb-2 text-right font-medium text-gray-500 dark:text-slate-500">Cuota/mes</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="f in familiasFiltradas"
                :key="f.familia_id ?? f.apellidos"
                class="border-b border-gray-50 dark:border-slate-800/40 last:border-0 hover:bg-gray-50 dark:hover:bg-slate-800/30 transition-colors"
              >
                <td class="py-2 pr-4 font-medium text-gray-900 dark:text-white">
                  <NuxtLink
                    v-if="f.familia_id"
                    :to="`/familias/${f.familia_id}`"
                    class="hover:text-amber-600 dark:hover:text-amber-400 transition-colors"
                  >
                    {{ f.apellidos }}
                  </NuxtLink>
                  <span v-else>{{ f.apellidos }}</span>
                </td>
                <td class="py-2 pr-4 text-gray-500 dark:text-slate-400 hidden sm:table-cell">
                  {{ f.socios_nombres.join(", ") }}
                </td>
                <td class="py-2 pr-4 hidden md:table-cell">
                  <span
                    v-for="nivel in f.niveles"
                    :key="nivel"
                    class="inline-block mr-1 px-1.5 py-0.5 rounded text-xs bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400"
                  >
                    {{ nivel }}
                  </span>
                </td>
                <td class="py-2 text-right font-semibold text-gray-900 dark:text-white">
                  {{ fmt(f.cuota) }} €
                </td>
              </tr>
              <tr v-if="familiasFiltradas.length === 0">
                <td colspan="4" class="py-8 text-center text-gray-400 dark:text-slate-500">
                  No se encontraron familias
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <p class="mt-3 text-xs text-gray-400 dark:text-slate-500">
          {{ familiasFiltradas.length }} de {{ stats?.num_familias ?? 0 }} familias
        </p>
      </div>
    </div>
  </div>
</template>
