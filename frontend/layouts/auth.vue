<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-950 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-primary-600 mb-4">
          <UIcon name="i-heroicons-academic-cap" class="w-8 h-8 text-white" />
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">TarfíaDB</h1>
        <p class="text-sm text-gray-500 mt-1">Gestión de socios y familias</p>
      </div>
      <slot />
    </div>
  </div>
</template>
