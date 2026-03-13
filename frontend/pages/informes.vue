<script setup lang="ts">
definePageMeta({ middleware: "auth" })

const { apiFetch } = useApi()
const toast = useToast()

// ── Data ────────────────────────────────────────────────────────────────────
interface NivelStat { nivel__nombre: string | null; nivel__orden: number; total: number }
interface Stats {
  total: number; activos: number; bajas: number
  cuotas_pagadas: number; cuotas_pendientes: number
  por_nivel: NivelStat[]
}

const { data: stats, pending: pendingStats } = await useAsyncData("informes-stats", () =>
  apiFetch<Stats>("/socios/estadisticas/")
)

const COLORS = [
  "#f59e0b","#14b8a6","#6366f1","#ec4899","#22c55e",
  "#f97316","#8b5cf6","#06b6d4","#84cc16","#ef4444","#a855f7",
]

// ── CSV Helpers ──────────────────────────────────────────────────────────────
function csvRow(row: (string | number | null | undefined)[]) {
  return row.map(v => {
    const s = String(v ?? "")
    return s.includes(",") || s.includes('"') || s.includes("\n")
      ? `"${s.replace(/"/g, '""')}"`
      : s
  }).join(",")
}

function downloadCsv(filename: string, rows: string[]) {
  const blob = new Blob(["\uFEFF" + rows.join("\n")], { type: "text/csv;charset=utf-8;" })
  const url = URL.createObjectURL(blob)
  const a = document.createElement("a")
  a.href = url
  a.download = filename
  a.click()
  URL.revokeObjectURL(url)
}

// ── Export Socios ────────────────────────────────────────────────────────────
const exportingS = ref(false)
async function exportSocios() {
  exportingS.value = true
  try {
    let all: any[] = []
    let next: string | null = "/socios/?page_size=500&ordering=apellidos"
    while (next) {
      const page: any = await apiFetch(next.replace(/^.*\/api/, ""))
      all = all.concat(page.results)
      next = page.next
    }
    const header = csvRow(["ID","Nombre","Apellidos","Familia","Nivel","Estado","Cuota €","Cuota Pagada","Fecha Alta"])
    const rows = [header, ...all.map((s: any) => csvRow([
      s.id, s.nombre, s.apellidos,
      s.familia_apellidos, s.nivel_nombre,
      s.estado, s.cuota ?? "", s.cuota_pagada ? "Sí" : "No",
      s.fecha_alta,
    ]))]
    downloadCsv("socios.csv", rows)
    toast.add({ title: `${all.length} socios exportados`, color: "green", icon: "i-heroicons-check-circle" })
  } catch {
    toast.add({ title: "Error al exportar socios", color: "red", icon: "i-heroicons-x-circle" })
  } finally {
    exportingS.value = false
  }
}

// ── Export Familias ──────────────────────────────────────────────────────────
const exportingF = ref(false)
async function exportFamilias() {
  exportingF.value = true
  try {
    let all: any[] = []
    let next: string | null = "/familias/?page_size=500&ordering=apellidos"
    while (next) {
      const page: any = await apiFetch(next.replace(/^.*\/api/, ""))
      all = all.concat(page.results)
      next = page.next
    }
    const header = csvRow(["ID","Apellidos","Tutor 1","Tutor 2","Email","Teléfono","Dirección","Localidad","CP","Socios","Alta"])
    const rows = [header, ...all.map((f: any) => csvRow([
      f.id, f.apellidos, f.nombre_tutor1, f.nombre_tutor2,
      f.email, f.telefono,
      f.direccion, f.localidad, f.cp,
      f.num_socios, f.fecha_alta,
    ]))]
    downloadCsv("familias.csv", rows)
    toast.add({ title: `${all.length} familias exportadas`, color: "green", icon: "i-heroicons-check-circle" })
  } catch {
    toast.add({ title: "Error al exportar familias", color: "red", icon: "i-heroicons-x-circle" })
  } finally {
    exportingF.value = false
  }
}

// ── Print ────────────────────────────────────────────────────────────────────
function printPage() {
  window.print()
}

function fmt(n: number) {
  return n.toLocaleString("es-ES", { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold text-gray-900 dark:text-white">Informes</h1>
      <UButton
        icon="i-heroicons-printer"
        variant="outline"
        color="gray"
        size="sm"
        @click="printPage"
      >
        Imprimir
      </UButton>
    </div>

    <!-- Resumen general -->
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-5">
      <h2 class="font-semibold text-gray-900 dark:text-white mb-4">Resumen general</h2>
      <div v-if="pendingStats" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div v-for="i in 4" :key="i" class="h-16 bg-gray-100 dark:bg-slate-800 rounded animate-pulse" />
      </div>
      <div v-else class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="text-center p-4 rounded-xl bg-gray-50 dark:bg-slate-800">
          <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats?.total ?? 0 }}</p>
          <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Total socios</p>
        </div>
        <div class="text-center p-4 rounded-xl bg-green-50 dark:bg-green-900/10">
          <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ stats?.activos ?? 0 }}</p>
          <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Activos</p>
        </div>
        <div class="text-center p-4 rounded-xl bg-gray-50 dark:bg-slate-800">
          <p class="text-3xl font-bold text-gray-400 dark:text-slate-500">{{ stats?.bajas ?? 0 }}</p>
          <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Bajas</p>
        </div>
        <div class="text-center p-4 rounded-xl bg-amber-50 dark:bg-amber-900/10">
          <p class="text-3xl font-bold text-amber-600 dark:text-amber-500">{{ stats?.cuotas_pagadas ?? 0 }}</p>
          <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Cuotas pagadas</p>
        </div>
      </div>
    </div>

    <!-- Socios por curso -->
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-5">
      <h2 class="font-semibold text-gray-900 dark:text-white mb-4">Socios por curso</h2>
      <div v-if="pendingStats" class="space-y-2">
        <div v-for="i in 8" :key="i" class="h-8 bg-gray-100 dark:bg-slate-800 rounded animate-pulse" />
      </div>
      <div v-else>
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 dark:border-slate-800">
              <th class="pb-2 text-left font-medium text-gray-500 dark:text-slate-500">Nivel / Curso</th>
              <th class="pb-2 text-right font-medium text-gray-500 dark:text-slate-500">Socios activos</th>
              <th class="pb-2 text-right font-medium text-gray-500 dark:text-slate-500">% del total</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-slate-800">
            <tr
              v-for="(nivel, i) in stats?.por_nivel"
              :key="nivel.nivel__nombre ?? i"
            >
              <td class="py-2.5">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-sm flex-shrink-0" :style="{ background: COLORS[i % COLORS.length] }" />
                  <span class="text-gray-700 dark:text-slate-300">{{ nivel.nivel__nombre ?? 'Sin nivel' }}</span>
                </div>
              </td>
              <td class="py-2.5 text-right font-semibold" :style="{ color: COLORS[i % COLORS.length] }">
                {{ nivel.total }}
              </td>
              <td class="py-2.5 text-right text-gray-400 dark:text-slate-500">
                {{ stats?.activos ? ((nivel.total / stats.activos) * 100).toFixed(1) : '0' }}%
              </td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="border-t border-gray-200 dark:border-slate-700">
              <td class="pt-2 font-semibold text-gray-900 dark:text-white">Total activos</td>
              <td class="pt-2 text-right font-bold text-gray-900 dark:text-white">{{ stats?.activos }}</td>
              <td class="pt-2 text-right text-gray-400 dark:text-slate-500">100%</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Exportar datos -->
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-5">
      <h2 class="font-semibold text-gray-900 dark:text-white mb-1">Exportar datos</h2>
      <p class="text-sm text-gray-500 dark:text-slate-400 mb-5">Descarga los datos en formato CSV para usar en Excel u otras aplicaciones.</p>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Socios CSV -->
        <div class="border border-gray-200 dark:border-slate-700 rounded-xl p-5 flex flex-col gap-3">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-amber-50 dark:bg-amber-900/20">
              <UIcon name="i-heroicons-users" class="w-5 h-5 text-amber-600 dark:text-amber-500" />
            </div>
            <div>
              <p class="font-medium text-gray-900 dark:text-white text-sm">Listado de socios</p>
              <p class="text-xs text-gray-400 dark:text-slate-500">Todos los socios con datos completos</p>
            </div>
          </div>
          <p class="text-xs text-gray-400 dark:text-slate-500">
            Incluye: nombre, apellidos, familia, nivel, estado, cuota, fecha de alta
          </p>
          <UButton
            icon="i-heroicons-arrow-down-tray"
            color="amber"
            variant="outline"
            size="sm"
            :loading="exportingS"
            @click="exportSocios"
          >
            Descargar CSV
          </UButton>
        </div>

        <!-- Familias CSV -->
        <div class="border border-gray-200 dark:border-slate-700 rounded-xl p-5 flex flex-col gap-3">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-teal-50 dark:bg-teal-900/20">
              <UIcon name="i-heroicons-home-modern" class="w-5 h-5 text-teal-600 dark:text-teal-400" />
            </div>
            <div>
              <p class="font-medium text-gray-900 dark:text-white text-sm">Listado de familias</p>
              <p class="text-xs text-gray-400 dark:text-slate-500">Todas las familias con datos de contacto</p>
            </div>
          </div>
          <p class="text-xs text-gray-400 dark:text-slate-500">
            Incluye: apellidos, tutores, email, teléfono, dirección, número de socios
          </p>
          <UButton
            icon="i-heroicons-arrow-down-tray"
            color="teal"
            variant="outline"
            size="sm"
            :loading="exportingF"
            @click="exportFamilias"
          >
            Descargar CSV
          </UButton>
        </div>
      </div>
    </div>
  </div>
</template>
