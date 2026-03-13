<script setup lang="ts">
definePageMeta({ layout: "auth" })

const auth = useAuthStore()
const toast = useToast()
const config = useRuntimeConfig()

const loading = ref(false)
const form = reactive({ email: "", password: "" })

async function onLogin() {
  loading.value = true
  try {
    await auth.login(form.email, form.password)
    navigateTo("/")
  } catch {
    toast.add({ title: "Credenciales incorrectas", color: "red", icon: "i-heroicons-x-circle" })
  } finally {
    loading.value = false
  }
}

const googleUrl = `${config.public.apiBase}/auth/browser/v1/auth/provider/redirect?provider=google&callback_url=/auth/callback`
const githubUrl = `${config.public.apiBase}/auth/browser/v1/auth/provider/redirect?provider=github&callback_url=/auth/callback`
</script>

<template>
  <UCard class="shadow-xl">
    <template #header>
      <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Iniciar sesión</h2>
    </template>

    <UForm :state="form" class="space-y-4" @submit="onLogin">
      <UFormGroup label="Email" name="email">
        <UInput
          v-model="form.email"
          type="email"
          placeholder="admin@tarfia.es"
          icon="i-heroicons-envelope"
          autocomplete="email"
        />
      </UFormGroup>

      <UFormGroup label="Contraseña" name="password">
        <UInput
          v-model="form.password"
          type="password"
          icon="i-heroicons-lock-closed"
          autocomplete="current-password"
        />
      </UFormGroup>

      <UButton type="submit" block :loading="loading" size="lg">
        Entrar
      </UButton>
    </UForm>

    <UDivider label="o continúa con" class="my-4" />

    <div class="grid grid-cols-2 gap-3">
      <UButton
        as="a"
        :href="googleUrl"
        color="white"
        block
        icon="i-simple-icons-google"
      >
        Google
      </UButton>
      <UButton
        as="a"
        :href="githubUrl"
        color="white"
        block
        icon="i-simple-icons-github"
      >
        GitHub
      </UButton>
    </div>
  </UCard>
</template>
