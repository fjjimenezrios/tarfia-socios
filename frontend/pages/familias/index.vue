<script setup lang="ts">
definePageMeta({ middleware: "auth" })

const { apiFetch } = useApi()
const toast = useToast()

const page = ref(1)
const search = ref("")

interface Familia {
  id: number
  apellidos: string
  nombre_tutor1: string
  email: string
  telefono: string
  localidad: string
  activa: boolean
  num_socios: number
}

interface PaginatedResponse {
  count: number
  results: Familia[]
}

const { data, refresh, pending } = await useAsyncData(
  "familias",
  () => apiFetch<PaginatedResponse>(`/familias/?page=${page.value}&search=${search.value}`),
  { watch: [page, search] }
)

const columns = [
  { key: "apellidos", label: "Apellidos" },
  { key: "nombre_tutor1", label: "Tutor" },
  { key: "email", label: "Email" },
  { key: "telefono", label: "Teléfono" },
  { key: "localidad", label: "Localidad" },
  { key: "num_socios", label: "Socios" },
  { key: "activa", label: "Estado" },
  { key: "actions", label: "" },
]

async function deleteFamilia(id: number) {
  if (!confirm("¿Eliminar esta familia?")) return
  await apiFetch(`/familias/${id}/`, { method: "DELETE" })
  toast.add({ title: "Familia eliminada", color: "green", icon: "i-heroicons-check-circle" })
  refresh()
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Familias</h1>
        <p class="text-sm text-gray-500">{{ data?.count ?? 0 }} familias registradas</p>
      </div>
      <UButton icon="i-heroicons-plus" to="/familias/nueva">
        Nueva familia
      </UButton>
    </div>

    <UInput
      v-model="search"
      placeholder="Buscar familia..."
      icon="i-heroicons-magnifying-glass"
      class="w-64"
    />

    <UCard :ui="{ body: { padding: '' } }">
      <UTable :rows="data?.results ?? []" :columns="columns" :loading="pending">
        <template #activa-data="{ row }">
          <UBadge :color="row.activa ? 'green' : 'gray'" variant="subtle" size="sm">
            {{ row.activa ? "Activa" : "Inactiva" }}
          </UBadge>
        </template>

        <template #num_socios-data="{ row }">
          <UBadge color="primary" variant="subtle" size="sm">{{ row.num_socios }}</UBadge>
        </template>

        <template #actions-data="{ row }">
          <div class="flex gap-1 justify-end">
            <UButton :to="`/familias/${row.id}`" icon="i-heroicons-eye" color="gray" variant="ghost" size="xs" />
            <UButton :to="`/familias/${row.id}/editar`" icon="i-heroicons-pencil" color="gray" variant="ghost" size="xs" />
            <UButton icon="i-heroicons-trash" color="red" variant="ghost" size="xs" @click="deleteFamilia(row.id)" />
          </div>
        </template>
      </UTable>

      <div class="flex justify-center py-4 border-t border-gray-200 dark:border-gray-800">
        <UPagination v-model="page" :total="data?.count ?? 0" :page-count="25" />
      </div>
    </UCard>
  </div>
</template>
