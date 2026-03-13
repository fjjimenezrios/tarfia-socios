<script setup lang="ts">
definePageMeta({ middleware: "auth" })

const { apiFetch } = useApi()
const auth = useAuthStore()
const toast = useToast()

// Redirigir si no es admin
if (!auth.user?.is_staff && !auth.user?.is_superuser) {
  await navigateTo("/")
}

interface User {
  id: number; email: string; username: string
  first_name: string; last_name: string
  is_active: boolean; is_staff: boolean; is_superuser: boolean
}

const { data: users, refresh, pending } = await useAsyncData("users", () =>
  apiFetch<{ results: User[] }>("/auth/token/users/?page_size=100")
)

const showModal = ref(false)
const editingUser = ref<User | null>(null)
const form = reactive({
  email: "", username: "", first_name: "", last_name: "",
  is_staff: false, is_superuser: false, is_active: true, password: "",
})
const saving = ref(false)

function openCreate() {
  editingUser.value = null
  Object.assign(form, { email: "", username: "", first_name: "", last_name: "", is_staff: false, is_superuser: false, is_active: true, password: "" })
  showModal.value = true
}

function openEdit(user: User) {
  editingUser.value = user
  Object.assign(form, { ...user, password: "" })
  showModal.value = true
}

async function save() {
  saving.value = true
  try {
    const body: Record<string, unknown> = { ...form }
    if (!body.password) delete body.password
    if (editingUser.value) {
      await apiFetch(`/auth/token/users/${editingUser.value.id}/`, { method: "PATCH", body })
      toast.add({ title: "Usuario actualizado", color: "green", icon: "i-heroicons-check-circle" })
    } else {
      await apiFetch("/auth/token/users/", { method: "POST", body })
      toast.add({ title: "Usuario creado", color: "green", icon: "i-heroicons-check-circle" })
    }
    showModal.value = false
    refresh()
  } catch (e: any) {
    toast.add({ title: "Error al guardar", description: e?.data?.detail ?? "Verifica los datos", color: "red", icon: "i-heroicons-x-circle" })
  } finally {
    saving.value = false
  }
}

async function toggleActive(user: User) {
  await apiFetch(`/auth/token/users/${user.id}/`, { method: "PATCH", body: { is_active: !user.is_active } })
  toast.add({ title: user.is_active ? "Usuario desactivado" : "Usuario activado", color: "green", icon: "i-heroicons-check-circle" })
  refresh()
}

async function deleteUser(user: User) {
  if (!confirm(`¿Seguro que quieres eliminar a ${user.email}?`)) return
  await apiFetch(`/auth/token/users/${user.id}/`, { method: "DELETE" })
  toast.add({ title: "Usuario eliminado", color: "green", icon: "i-heroicons-check-circle" })
  refresh()
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-white">Usuarios</h1>
        <p class="text-sm text-slate-400 mt-0.5">Gestión de acceso al sistema</p>
      </div>
      <UButton icon="i-heroicons-plus" color="amber" size="sm" @click="openCreate">
        Nuevo usuario
      </UButton>
    </div>

    <!-- Users table -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-slate-800">
            <th class="px-4 py-3 text-left font-medium text-slate-500">Usuario</th>
            <th class="px-4 py-3 text-left font-medium text-slate-500">Email</th>
            <th class="px-4 py-3 text-left font-medium text-slate-500">Rol</th>
            <th class="px-4 py-3 text-left font-medium text-slate-500">Estado</th>
            <th class="px-4 py-3 text-right font-medium text-slate-500">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="pending">
            <td colspan="5" class="text-center py-10 text-slate-500">
              <UIcon name="i-heroicons-arrow-path" class="w-6 h-6 animate-spin inline" />
            </td>
          </tr>
          <tr
            v-else
            v-for="user in users?.results"
            :key="user.id"
            class="border-b border-slate-800/50 last:border-0 hover:bg-slate-800/30 transition-colors"
          >
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center text-xs font-bold flex-shrink-0">
                  {{ (user.first_name || user.email)[0].toUpperCase() }}
                </div>
                <div>
                  <p class="font-medium text-white">{{ user.first_name }} {{ user.last_name }}</p>
                  <p class="text-xs text-slate-500">{{ user.username }}</p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 text-slate-300">{{ user.email }}</td>
            <td class="px-4 py-3">
              <span
                class="px-2 py-0.5 rounded-full text-xs font-medium"
                :class="user.is_superuser
                  ? 'bg-amber-500/20 text-amber-400'
                  : user.is_staff
                    ? 'bg-blue-500/20 text-blue-400'
                    : 'bg-slate-700 text-slate-400'"
              >
                {{ user.is_superuser ? 'Superadmin' : user.is_staff ? 'Admin' : 'Usuario' }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span
                class="px-2 py-0.5 rounded-full text-xs font-medium"
                :class="user.is_active ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'"
              >
                {{ user.is_active ? 'Activo' : 'Inactivo' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex gap-1 justify-end">
                <button
                  class="w-7 h-7 rounded flex items-center justify-center bg-amber-500/20 hover:bg-amber-500/40 text-amber-400 transition-colors"
                  @click="openEdit(user)"
                >
                  <UIcon name="i-heroicons-pencil" class="w-3.5 h-3.5" />
                </button>
                <button
                  class="w-7 h-7 rounded flex items-center justify-center transition-colors"
                  :class="user.is_active
                    ? 'bg-slate-700 hover:bg-slate-600 text-slate-400'
                    : 'bg-green-500/20 hover:bg-green-500/40 text-green-400'"
                  :title="user.is_active ? 'Desactivar' : 'Activar'"
                  @click="toggleActive(user)"
                >
                  <UIcon :name="user.is_active ? 'i-heroicons-pause' : 'i-heroicons-play'" class="w-3.5 h-3.5" />
                </button>
                <button
                  v-if="user.id !== auth.user?.id"
                  class="w-7 h-7 rounded flex items-center justify-center bg-red-500/20 hover:bg-red-500/40 text-red-400 transition-colors"
                  @click="deleteUser(user)"
                >
                  <UIcon name="i-heroicons-trash" class="w-3.5 h-3.5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal crear/editar -->
    <UModal v-model="showModal">
      <UCard :ui="{ ring: '', divide: 'divide-y divide-slate-800', base: 'bg-slate-900' }">
        <template #header>
          <h3 class="font-semibold text-white">{{ editingUser ? 'Editar usuario' : 'Nuevo usuario' }}</h3>
        </template>

        <div class="space-y-4 p-1">
          <div class="grid grid-cols-2 gap-3">
            <UFormGroup label="Nombre">
              <UInput v-model="form.first_name" placeholder="Nombre" />
            </UFormGroup>
            <UFormGroup label="Apellidos">
              <UInput v-model="form.last_name" placeholder="Apellidos" />
            </UFormGroup>
          </div>
          <UFormGroup label="Email">
            <UInput v-model="form.email" type="email" placeholder="correo@example.com" />
          </UFormGroup>
          <UFormGroup label="Username">
            <UInput v-model="form.username" placeholder="nombre_usuario" />
          </UFormGroup>
          <UFormGroup :label="editingUser ? 'Nueva contraseña (dejar vacío para no cambiar)' : 'Contraseña'">
            <UInput v-model="form.password" type="password" placeholder="••••••••" />
          </UFormGroup>
          <div class="flex gap-6">
            <UCheckbox v-model="form.is_staff" label="Admin (staff)" />
            <UCheckbox v-model="form.is_superuser" label="Superadmin" />
            <UCheckbox v-model="form.is_active" label="Activo" />
          </div>
        </div>

        <template #footer>
          <div class="flex justify-end gap-2">
            <UButton variant="ghost" color="gray" @click="showModal = false">Cancelar</UButton>
            <UButton color="amber" :loading="saving" @click="save">Guardar</UButton>
          </div>
        </template>
      </UCard>
    </UModal>
  </div>
</template>
