<script setup lang="ts">
definePageMeta({ middleware: "auth" })

const { apiFetch } = useApi()
const toast = useToast()
const router = useRouter()
const route = useRoute()

interface Nivel { id: number; nombre: string }
interface Familia { id: number; apellidos: string; nombre_tutor1: string }

const [{ data: socio }, { data: niveles }, { data: familias }] = await Promise.all([
  useAsyncData(`socio-edit-${route.params.id}`, () => apiFetch<any>(`/socios/${route.params.id}/`)),
  useAsyncData("niveles-edit", () => apiFetch<{ results: Nivel[] }>("/socios/niveles/?page_size=100")),
  useAsyncData("familias-edit", () => apiFetch<{ results: Familia[] }>("/familias/?page_size=500&ordering=apellidos")),
])

if (!socio.value) throw createError({ statusCode: 404 })

const form = reactive({
  nombre: socio.value.nombre ?? "",
  apellidos: socio.value.apellidos ?? "",
  familia: socio.value.familia ?? null,
  nivel: socio.value.nivel ?? null,
  fecha_nacimiento: socio.value.fecha_nacimiento ?? "",
  fecha_baja: socio.value.fecha_baja ?? "",
  estado: socio.value.estado ?? "activo",
  cuota: socio.value.cuota ?? "",
  notas: socio.value.notas ?? "",
})
const saving = ref(false)

const estadoOpts = [
  { label: "Activo", value: "activo" },
  { label: "Baja", value: "baja" },
  { label: "Pendiente", value: "pendiente" },
]
const nivelOpts = computed(() => [
  { label: "Sin nivel", value: null },
  ...(niveles.value?.results ?? []).map(n => ({ label: n.nombre, value: n.id })),
])
const familiaOpts = computed(() => [
  { label: "Sin familia", value: null },
  ...(familias.value?.results ?? []).map(f => ({ label: `${f.apellidos} — ${f.nombre_tutor1}`, value: f.id })),
])

async function guardar() {
  saving.value = true
  try {
    const body = {
      ...form,
      cuota: form.cuota !== "" ? Number(form.cuota) : null,
      fecha_nacimiento: form.fecha_nacimiento || null,
      fecha_baja: form.fecha_baja || null,
    }
    await apiFetch(`/socios/${route.params.id}/`, { method: "PATCH", body })
    toast.add({ title: "Socio actualizado", color: "green", icon: "i-heroicons-check-circle" })
    router.push(`/socios/${route.params.id}`)
  } catch (e: any) {
    const msg = Object.values(e?.data ?? {}).flat().join(", ") || "Error al guardar"
    toast.add({ title: "Error", description: String(msg), color: "red", icon: "i-heroicons-x-circle" })
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="max-w-2xl space-y-6">
    <div class="flex items-center gap-3">
      <UButton :to="`/socios/${route.params.id}`" variant="ghost" icon="i-heroicons-arrow-left" color="gray" size="sm" />
      <h1 class="text-xl font-bold text-gray-900 dark:text-white">
        Editar — {{ form.nombre }} {{ form.apellidos }}
      </h1>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-6 space-y-4">
      <div class="grid grid-cols-2 gap-4">
        <UFormGroup label="Nombre">
          <UInput v-model="form.nombre" placeholder="Nombre del socio" />
        </UFormGroup>
        <UFormGroup label="Apellidos">
          <UInput v-model="form.apellidos" placeholder="Apellidos" />
        </UFormGroup>
      </div>

      <UFormGroup label="Familia">
        <USelect v-model="form.familia" :options="familiaOpts" option-attribute="label" value-attribute="value" />
      </UFormGroup>

      <div class="grid grid-cols-2 gap-4">
        <UFormGroup label="Nivel/Curso">
          <USelect v-model="form.nivel" :options="nivelOpts" option-attribute="label" value-attribute="value" />
        </UFormGroup>
        <UFormGroup label="Estado">
          <USelect v-model="form.estado" :options="estadoOpts" option-attribute="label" value-attribute="value" />
        </UFormGroup>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <UFormGroup label="Fecha de nacimiento">
          <UInput v-model="form.fecha_nacimiento" type="date" />
        </UFormGroup>
        <UFormGroup label="Cuota (€)">
          <UInput v-model="form.cuota" type="number" placeholder="35.00" step="0.01" />
        </UFormGroup>
      </div>

      <UFormGroup label="Fecha de baja">
        <UInput v-model="form.fecha_baja" type="date" />
      </UFormGroup>

      <UFormGroup label="Observaciones">
        <UTextarea v-model="form.notas" placeholder="Notas adicionales..." :rows="3" />
      </UFormGroup>
    </div>

    <div class="flex justify-end gap-3">
      <UButton :to="`/socios/${route.params.id}`" variant="outline" color="gray">Cancelar</UButton>
      <UButton color="amber" :loading="saving" icon="i-heroicons-check" @click="guardar">Guardar cambios</UButton>
    </div>
  </div>
</template>
