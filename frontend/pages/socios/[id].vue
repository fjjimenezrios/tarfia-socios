<script setup lang="ts">
definePageMeta({ middleware: "auth" })

const route = useRoute()
const router = useRouter()
const { apiFetch } = useApi()
const toast = useToast()

interface Socio {
  id: number
  nombre: string
  apellidos: string
  nombre_completo: string
  nivel: number | null
  nivel_nombre: string
  familia: number | null
  familia_apellidos: string
  fecha_nacimiento: string | null
  fecha_alta: string
  fecha_baja: string | null
  estado: string
  cuota_pagada: boolean
  notas: string
}

const { data: socio, pending, error } = await useAsyncData<Socio>(
  `socio-${route.params.id}`,
  () => apiFetch(`/socios/${route.params.id}/`)
)

if (error.value) {
  throw createError({ statusCode: 404, message: "Socio no encontrado" })
}

const estadoColors: Record<string, string> = {
  activo: "green",
  baja: "red",
  pendiente: "yellow",
}

async function deleteSocio() {
  if (!confirm("¿Seguro que quieres eliminar este socio?")) return
  await apiFetch(`/socios/${route.params.id}/`, { method: "DELETE" })
  toast.add({ title: "Socio eliminado", color: "green", icon: "i-heroicons-check-circle" })
  router.push("/socios")
}

function formatDate(d: string | null) {
  if (!d) return "—"
  return new Date(d).toLocaleDateString("es-ES", { day: "2-digit", month: "2-digit", year: "numeric" })
}
</script>

<template>
  <div v-if="pending" class="flex justify-center py-20">
    <UIcon name="i-heroicons-arrow-path" class="w-8 h-8 animate-spin text-gray-400" />
  </div>

  <div v-else-if="socio" class="space-y-6 max-w-3xl">
    <!-- Header -->
    <div class="flex items-start justify-between">
      <div>
        <div class="flex items-center gap-3">
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ socio.apellidos }}, {{ socio.nombre }}
          </h1>
          <UBadge :color="estadoColors[socio.estado]" variant="subtle">
            {{ socio.estado }}
          </UBadge>
        </div>
        <p v-if="socio.familia_apellidos" class="text-sm text-gray-500 mt-1">
          Familia {{ socio.familia_apellidos }}
        </p>
      </div>
      <div class="flex gap-2">
        <UButton
          :to="`/socios/${socio.id}/editar`"
          icon="i-heroicons-pencil"
          color="gray"
          variant="outline"
        >
          Editar
        </UButton>
        <UButton
          icon="i-heroicons-trash"
          color="red"
          variant="outline"
          @click="deleteSocio"
        >
          Eliminar
        </UButton>
      </div>
    </div>

    <!-- Datos -->
    <UCard>
      <template #header>
        <h2 class="font-semibold text-gray-900 dark:text-white">Datos del socio</h2>
      </template>

      <dl class="grid grid-cols-2 gap-x-8 gap-y-4 text-sm">
        <div>
          <dt class="text-gray-500">Nivel/Curso</dt>
          <dd class="font-medium text-gray-900 dark:text-white mt-0.5">
            {{ socio.nivel_nombre || "—" }}
          </dd>
        </div>
        <div>
          <dt class="text-gray-500">Cuota pagada</dt>
          <dd class="mt-0.5">
            <UIcon
              :name="socio.cuota_pagada ? 'i-heroicons-check-circle' : 'i-heroicons-x-circle'"
              :class="socio.cuota_pagada ? 'text-green-500' : 'text-red-400'"
              class="w-5 h-5"
            />
          </dd>
        </div>
        <div>
          <dt class="text-gray-500">Fecha de nacimiento</dt>
          <dd class="font-medium text-gray-900 dark:text-white mt-0.5">
            {{ formatDate(socio.fecha_nacimiento) }}
          </dd>
        </div>
        <div>
          <dt class="text-gray-500">Fecha de alta</dt>
          <dd class="font-medium text-gray-900 dark:text-white mt-0.5">
            {{ formatDate(socio.fecha_alta) }}
          </dd>
        </div>
        <div v-if="socio.fecha_baja">
          <dt class="text-gray-500">Fecha de baja</dt>
          <dd class="font-medium text-gray-900 dark:text-white mt-0.5">
            {{ formatDate(socio.fecha_baja) }}
          </dd>
        </div>
        <div v-if="socio.familia">
          <dt class="text-gray-500">Familia</dt>
          <dd class="mt-0.5">
            <UButton
              :to="`/familias/${socio.familia}`"
              variant="link"
              color="primary"
              class="p-0 h-auto"
            >
              {{ socio.familia_apellidos }}
            </UButton>
          </dd>
        </div>
      </dl>

      <div v-if="socio.notas" class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-800">
        <dt class="text-sm text-gray-500 mb-1">Observaciones</dt>
        <dd class="text-sm text-gray-900 dark:text-white whitespace-pre-line">{{ socio.notas }}</dd>
      </div>
    </UCard>

    <!-- Back -->
    <UButton to="/socios" variant="ghost" icon="i-heroicons-arrow-left" color="gray">
      Volver a socios
    </UButton>
  </div>
</template>
