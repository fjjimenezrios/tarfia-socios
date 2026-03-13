<script setup lang="ts">
definePageMeta({ middleware: "auth" })

const { apiFetch } = useApi()
const toast = useToast()
const router = useRouter()
const route = useRoute()

interface Familia {
  id: number; apellidos: string; nombre_tutor1: string; nombre_tutor2: string
  email: string; telefono: string; telefono2: string; direccion: string
  localidad: string; cp: string; notas: string; activa: boolean
}

const { data: familia, error } = await useAsyncData<Familia>(
  `familia-edit-${route.params.id}`,
  () => apiFetch(`/familias/${route.params.id}/`)
)
if (error.value) throw createError({ statusCode: 404 })

const form = reactive({
  apellidos: familia.value?.apellidos ?? "",
  nombre_tutor1: familia.value?.nombre_tutor1 ?? "",
  nombre_tutor2: familia.value?.nombre_tutor2 ?? "",
  email: familia.value?.email ?? "",
  telefono: familia.value?.telefono ?? "",
  telefono2: familia.value?.telefono2 ?? "",
  direccion: familia.value?.direccion ?? "",
  localidad: familia.value?.localidad ?? "",
  cp: familia.value?.cp ?? "",
  notas: familia.value?.notas ?? "",
  activa: familia.value?.activa ?? true,
})

const saving = ref(false)

async function guardar() {
  if (!form.apellidos.trim() || !form.nombre_tutor1.trim()) {
    toast.add({ title: "Apellidos y tutor 1 son obligatorios", color: "red", icon: "i-heroicons-x-circle" })
    return
  }
  saving.value = true
  try {
    await apiFetch(`/familias/${route.params.id}/`, { method: "PATCH", body: form })
    toast.add({ title: "Familia actualizada", color: "green", icon: "i-heroicons-check-circle" })
    router.push(`/familias/${route.params.id}`)
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
      <UButton :to="`/familias/${route.params.id}`" variant="ghost" icon="i-heroicons-arrow-left" color="gray" size="sm" />
      <h1 class="text-xl font-bold text-gray-900 dark:text-white">Editar familia</h1>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-6 space-y-4">
      <UFormGroup label="Apellidos *">
        <UInput v-model="form.apellidos" placeholder="Apellidos de la familia" autofocus />
      </UFormGroup>

      <div class="grid grid-cols-2 gap-4">
        <UFormGroup label="Tutor 1 *">
          <UInput v-model="form.nombre_tutor1" placeholder="Nombre completo" />
        </UFormGroup>
        <UFormGroup label="Tutor 2">
          <UInput v-model="form.nombre_tutor2" placeholder="Nombre completo" />
        </UFormGroup>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <UFormGroup label="Email">
          <UInput v-model="form.email" type="email" placeholder="correo@example.com" />
        </UFormGroup>
        <UFormGroup label="Teléfono">
          <UInput v-model="form.telefono" placeholder="954 000 000" />
        </UFormGroup>
      </div>

      <UFormGroup label="Teléfono 2 / Móvil">
        <UInput v-model="form.telefono2" placeholder="600 000 000" />
      </UFormGroup>

      <UFormGroup label="Dirección">
        <UInput v-model="form.direccion" placeholder="Calle, número, piso..." />
      </UFormGroup>

      <div class="grid grid-cols-2 gap-4">
        <UFormGroup label="Localidad">
          <UInput v-model="form.localidad" placeholder="Sevilla" />
        </UFormGroup>
        <UFormGroup label="C.P.">
          <UInput v-model="form.cp" placeholder="41000" maxlength="10" />
        </UFormGroup>
      </div>

      <UFormGroup label="Observaciones">
        <UTextarea v-model="form.notas" placeholder="Notas adicionales..." :rows="3" />
      </UFormGroup>

      <UFormGroup label="Estado">
        <UToggle v-model="form.activa" />
        <span class="ml-2 text-sm text-gray-600 dark:text-slate-300">{{ form.activa ? "Activa" : "Inactiva" }}</span>
      </UFormGroup>
    </div>

    <div class="flex justify-end gap-3">
      <UButton :to="`/familias/${route.params.id}`" variant="outline" color="gray">Cancelar</UButton>
      <UButton color="amber" :loading="saving" icon="i-heroicons-check" @click="guardar">Guardar cambios</UButton>
    </div>
  </div>
</template>
