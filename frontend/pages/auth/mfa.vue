<script setup lang="ts">
definePageMeta({ layout: "auth" })

const config = useRuntimeConfig()
const toast = useToast()
const loading = ref(false)
const code = ref("")

// El token pendiente de MFA viene en la query (allauth headless lo devuelve)
const route = useRoute()

async function onSubmit() {
  loading.value = true
  try {
    await $fetch(`${config.public.apiBase}/auth/browser/v1/auth/2fa/authenticate`, {
      method: "POST",
      body: { code: code.value },
      credentials: "include",
    })
    navigateTo("/")
  } catch {
    toast.add({ title: "Código incorrecto", color: "red", icon: "i-heroicons-x-circle" })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UCard class="shadow-xl">
    <template #header>
      <div class="flex items-center gap-3">
        <UIcon name="i-heroicons-shield-check" class="w-6 h-6 text-primary-500" />
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Verificación en dos pasos</h2>
      </div>
    </template>

    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
      Introduce el código de 6 dígitos de tu aplicación de autenticación (Google Authenticator, Authy, etc.)
    </p>

    <UForm :state="{ code }" class="space-y-4" @submit="onSubmit">
      <UFormGroup label="Código TOTP" name="code">
        <UInput
          v-model="code"
          placeholder="123456"
          maxlength="6"
          inputmode="numeric"
          icon="i-heroicons-key"
          size="xl"
          class="text-center tracking-widest font-mono text-2xl"
        />
      </UFormGroup>

      <UButton type="submit" block :loading="loading" size="lg">
        Verificar
      </UButton>
    </UForm>

    <template #footer>
      <p class="text-xs text-gray-400 text-center">
        ¿Perdiste el acceso?
        <NuxtLink to="/auth/recovery" class="text-primary-500 hover:underline">Usar código de recuperación</NuxtLink>
      </p>
    </template>
  </UCard>
</template>
