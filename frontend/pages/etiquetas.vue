<script setup lang="ts">
definePageMeta({ middleware: "auth" })
</script>

<template>
  <div class="space-y-6">
    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Etiquetas</h1>
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-12 text-center">
      <UIcon name="i-heroicons-tag" class="w-12 h-12 text-gray-300 dark:text-slate-600 mx-auto mb-4" />
      <p class="text-gray-500 dark:text-slate-400 font-medium">Módulo de etiquetas en desarrollo</p>
      <p class="text-sm text-gray-400 dark:text-slate-500 mt-1">Próximamente podrás generar etiquetas postales.</p>
    </div>
  </div>
</template>
