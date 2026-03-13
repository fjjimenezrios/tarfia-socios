<script setup lang="ts">
definePageMeta({ middleware: "auth" })

const auth = useAuthStore()
const colorMode = useColorMode()

const navigation = [
  { label: "Inicio", icon: "i-heroicons-home", to: "/" },
  { label: "Socios", icon: "i-heroicons-users", to: "/socios" },
  { label: "Familias", icon: "i-heroicons-building-library", to: "/familias" },
]

const isDark = computed({
  get: () => colorMode.value === "dark",
  set: (val) => { colorMode.preference = val ? "dark" : "light" },
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-950 flex">
    <!-- Sidebar -->
    <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800">
      <!-- Logo -->
      <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-200 dark:border-gray-800">
        <div class="w-8 h-8 rounded-lg bg-primary-600 flex items-center justify-center">
          <UIcon name="i-heroicons-academic-cap" class="w-5 h-5 text-white" />
        </div>
        <span class="font-bold text-gray-900 dark:text-white text-lg">TarfíaDB</span>
      </div>

      <!-- Nav -->
      <nav class="flex-1 px-3 py-4 space-y-1">
        <UVerticalNavigation :links="navigation" />
      </nav>

      <!-- User -->
      <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-800">
        <div class="flex items-center gap-3">
          <UAvatar
            :alt="auth.user?.email"
            size="sm"
            :src="auth.user?.avatar || undefined"
          />
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
              {{ auth.user?.first_name || auth.user?.email }}
            </p>
            <p class="text-xs text-gray-500 truncate">{{ auth.user?.email }}</p>
          </div>
          <UButton
            icon="i-heroicons-arrow-right-on-rectangle"
            color="gray"
            variant="ghost"
            size="xs"
            @click="auth.logout()"
          />
        </div>
      </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top bar -->
      <header class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 px-6 py-3 flex items-center justify-between">
        <div />
        <div class="flex items-center gap-2">
          <UButton
            :icon="isDark ? 'i-heroicons-sun' : 'i-heroicons-moon'"
            color="gray"
            variant="ghost"
            size="sm"
            @click="isDark = !isDark"
          />
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 p-6 overflow-auto">
        <slot />
      </main>
    </div>
  </div>
</template>
