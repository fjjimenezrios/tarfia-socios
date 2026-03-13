<script setup lang="ts">
definePageMeta({ middleware: "auth" })

const { apiFetch } = useApi()
const toast = useToast()

const page = ref(1)
const search = ref("")
const estadoFilter = ref("")
const loading = ref(false)

interface Socio {
  id: number
  nombre: string
  apellidos: string
  nivel_nombre: string
  familia_apellidos: string
  estado: string
  cuota_pagada: boolean
  fecha_alta: string
}

interface PaginatedResponse {
  count: number
  results: Socio[]
}

const { data, refresh, pending } = await useAsyncData(
  "socios",
  () => apiFetch<PaginatedResponse>(
    `/socios/?page=${page.value}&search=${search.value}&estado=${estadoFilter.value}`
  ),
  { watch: [page, search, estadoFilter] }
)

const columns = [
  { key: "apellidos", label: "Apellidos" },
  { key: "nombre", label: "Nombre" },
  { key: "nivel_nombre", label: "Nivel" },
  { key: "familia_apellidos", label: "Familia" },
  { key: "estado", label: "Estado" },
  { key: "cuota_pagada", label: "Cuota" },
  { key: "actions", label: "" },
]

const estadoOptions = [
  { label: "Todos", value: "" },
  { label: "Activo", value: "activo" },
  { label: "Baja", value: "baja" },
  { label: "Pendiente", value: "pendiente" },
]

const estadoColors: Record<string, string> = {
  activo: "green",
  baja: "red",
  pendiente: "yellow",
}

async function deleteSocio(id: number) {
  if (!confirm("¿Seguro que quieres dar de baja a este socio?")) return
  await apiFetch(`/socios/${id}/`, { method: "DELETE" })
  toast.add({ title: "Socio eliminado", color: "green", icon: "i-heroicons-check-circle" })
  refresh()
}
</script>

<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Socios</h1>
        <p class="text-sm text-gray-500">{{ data?.count ?? 0 }} socios en total</p>
      </div>
      <UButton icon="i-heroicons-plus" to="/socios/nuevo">
        Nuevo socio
      </UButton>
    </div>

    <!-- Filters -->
    <div class="flex gap-3 flex-wrap">
      <UInput
        v-model="search"
        placeholder="Buscar por nombre..."
        icon="i-heroicons-magnifying-glass"
        class="w-64"
      />
      <USelect
        v-model="estadoFilter"
        :options="estadoOptions"
        option-attribute="label"
        value-attribute="value"
        class="w-40"
      />
    </div>

    <!-- Table -->
    <UCard :ui="{ body: { padding: '' } }">
      <UTable
        :rows="data?.results ?? []"
        :columns="columns"
        :loading="pending"
      >
        <template #estado-data="{ row }">
          <UBadge :color="estadoColors[row.estado]" variant="subtle" size="sm">
            {{ row.estado }}
          </UBadge>
        </template>

        <template #cuota_pagada-data="{ row }">
          <UIcon
            :name="row.cuota_pagada ? 'i-heroicons-check-circle' : 'i-heroicons-x-circle'"
            :class="row.cuota_pagada ? 'text-green-500' : 'text-gray-300'"
            class="w-5 h-5"
          />
        </template>

        <template #actions-data="{ row }">
          <div class="flex gap-1 justify-end">
            <UButton
              :to="`/socios/${row.id}`"
              icon="i-heroicons-eye"
              color="gray"
              variant="ghost"
              size="xs"
            />
            <UButton
              :to="`/socios/${row.id}/editar`"
              icon="i-heroicons-pencil"
              color="gray"
              variant="ghost"
              size="xs"
            />
            <UButton
              icon="i-heroicons-trash"
              color="red"
              variant="ghost"
              size="xs"
              @click="deleteSocio(row.id)"
            />
          </div>
        </template>
      </UTable>

      <!-- Pagination -->
      <div class="flex justify-center py-4 border-t border-gray-200 dark:border-gray-800">
        <UPagination
          v-model="page"
          :total="data?.count ?? 0"
          :page-count="25"
        />
      </div>
    </UCard>
  </div>
</template>
