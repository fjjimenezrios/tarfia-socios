<script setup lang="ts">
definePageMeta({ middleware: "auth" })

const { apiFetch } = useApi()
const toast = useToast()
const router = useRouter()
const route = useRoute()

interface Familia {
  id: number; apellidos: string; nombre_tutor1: string; nombre_tutor2: string
  email: string; telefono: string; telefono2: string; direccion: string
  localidad: string; cp: string; notas: string; activa: boolean; fecha_alta: string
  num_socios: number
}
interface Socio { id: number; nombre: string; apellidos: string; nivel_nombre: string; estado: string; cuota: number | null }

const { data: familia, error } = await useAsyncData<Familia>(
  `familia-${route.params.id}`,
  () => apiFetch(`/familias/${route.params.id}/`)
)
if (error.value) throw createError({ statusCode: 404 })

const { data: socios } = await useAsyncData(
  `familia-socios-${route.params.id}`,
  () => apiFetch<{ results: Socio[] }>(`/socios/?familia=${route.params.id}&page_size=50`)
)

async function deleteFamilia() {
  if (!confirm("¿Eliminar esta familia y todos sus datos?")) return
  await apiFetch(`/familias/${route.params.id}/`, { method: "DELETE" })
  toast.add({ title: "Familia eliminada", color: "green", icon: "i-heroicons-check-circle" })
  router.push("/familias")
}

function formatDate(d: string | null) {
  if (!d) return "—"
  return new Date(d).toLocaleDateString("es-ES", { day: "2-digit", month: "2-digit", year: "numeric" })
}
</script>

<template>
  <div v-if="familia" class="max-w-3xl space-y-6">
    <!-- Header -->
    <div class="flex items-start justify-between">
      <div class="flex items-center gap-3">
        <UButton to="/familias" variant="ghost" icon="i-heroicons-arrow-left" color="gray" size="sm" />
        <div>
          <h1 class="text-xl font-bold text-gray-900 dark:text-white">Familia {{ familia.apellidos }}</h1>
          <p class="text-sm text-gray-500 dark:text-slate-400">{{ familia.num_socios }} socio(s) vinculado(s)</p>
        </div>
      </div>
      <div class="flex gap-2">
        <UButton :to="`/familias/${familia.id}/editar`" icon="i-heroicons-pencil" variant="outline" color="gray" size="sm">Editar</UButton>
        <UButton icon="i-heroicons-trash" variant="outline" color="red" size="sm" @click="deleteFamilia">Eliminar</UButton>
      </div>
    </div>

    <!-- Datos -->
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-6">
      <h2 class="font-semibold text-gray-900 dark:text-white mb-4">Datos de la familia</h2>
      <dl class="grid grid-cols-2 gap-x-8 gap-y-4 text-sm">
        <div>
          <dt class="text-gray-500 dark:text-slate-500">Tutor 1</dt>
          <dd class="font-medium text-gray-900 dark:text-white mt-0.5">{{ familia.nombre_tutor1 || "—" }}</dd>
        </div>
        <div>
          <dt class="text-gray-500 dark:text-slate-500">Tutor 2</dt>
          <dd class="font-medium text-gray-900 dark:text-white mt-0.5">{{ familia.nombre_tutor2 || "—" }}</dd>
        </div>
        <div>
          <dt class="text-gray-500 dark:text-slate-500">Email</dt>
          <dd class="font-medium text-gray-900 dark:text-white mt-0.5">{{ familia.email || "—" }}</dd>
        </div>
        <div>
          <dt class="text-gray-500 dark:text-slate-500">Teléfono</dt>
          <dd class="font-medium text-gray-900 dark:text-white mt-0.5">{{ familia.telefono || "—" }}</dd>
        </div>
        <div v-if="familia.telefono2">
          <dt class="text-gray-500 dark:text-slate-500">Teléfono 2</dt>
          <dd class="font-medium text-gray-900 dark:text-white mt-0.5">{{ familia.telefono2 }}</dd>
        </div>
        <div>
          <dt class="text-gray-500 dark:text-slate-500">Dirección</dt>
          <dd class="font-medium text-gray-900 dark:text-white mt-0.5">
            {{ [familia.direccion, familia.localidad, familia.cp].filter(Boolean).join(", ") || "—" }}
          </dd>
        </div>
        <div>
          <dt class="text-gray-500 dark:text-slate-500">Alta</dt>
          <dd class="font-medium text-gray-900 dark:text-white mt-0.5">{{ formatDate(familia.fecha_alta) }}</dd>
        </div>
        <div>
          <dt class="text-gray-500 dark:text-slate-500">Estado</dt>
          <dd class="mt-0.5">
            <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="familia.activa ? 'bg-green-500/20 text-green-600 dark:text-green-400' : 'bg-gray-200 dark:bg-slate-700 text-gray-500 dark:text-slate-400'">
              {{ familia.activa ? "Activa" : "Inactiva" }}
            </span>
          </dd>
        </div>
      </dl>
      <div v-if="familia.notas" class="mt-4 pt-4 border-t border-gray-100 dark:border-slate-800">
        <dt class="text-sm text-gray-500 dark:text-slate-500 mb-1">Observaciones</dt>
        <dd class="text-sm text-gray-900 dark:text-white whitespace-pre-line">{{ familia.notas }}</dd>
      </div>
    </div>

    <!-- Socios vinculados -->
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-6">
      <h2 class="font-semibold text-gray-900 dark:text-white mb-4">Socios</h2>
      <div v-if="!socios?.results?.length" class="text-sm text-gray-400 dark:text-slate-500">
        No hay socios vinculados a esta familia.
      </div>
      <table v-else class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-100 dark:border-slate-800">
            <th class="pb-2 text-left font-medium text-gray-500 dark:text-slate-500">Nombre</th>
            <th class="pb-2 text-left font-medium text-gray-500 dark:text-slate-500">Nivel</th>
            <th class="pb-2 text-left font-medium text-gray-500 dark:text-slate-500">Estado</th>
            <th class="pb-2 text-left font-medium text-gray-500 dark:text-slate-500">Cuota</th>
            <th />
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in socios.results" :key="s.id" class="border-b border-gray-50 dark:border-slate-800/50 last:border-0">
            <td class="py-2 font-medium text-gray-900 dark:text-white">{{ s.nombre }} {{ s.apellidos }}</td>
            <td class="py-2 text-gray-500 dark:text-slate-400">{{ s.nivel_nombre || "—" }}</td>
            <td class="py-2">
              <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="s.estado === 'activo' ? 'bg-green-500/20 text-green-600 dark:text-green-400' : 'bg-red-500/20 text-red-500'">
                {{ s.estado }}
              </span>
            </td>
            <td class="py-2 text-gray-500 dark:text-slate-400">{{ s.cuota ? `${Number(s.cuota).toFixed(2)} €` : "—" }}</td>
            <td class="py-2 text-right">
              <UButton :to="`/socios/${s.id}`" variant="ghost" size="xs" color="gray" label="Ver" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
