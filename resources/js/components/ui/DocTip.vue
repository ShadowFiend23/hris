<template>
  <div class="mt-3 flex items-start gap-2 rounded-lg bg-blue-50 px-4 py-3 dark:bg-blue-900/20">
    <Lightbulb :size="14" class="mt-0.5 shrink-0 text-blue-500 dark:text-blue-400" />
    <p class="text-xs text-blue-700 dark:text-blue-300"><slot /></p>
  </div>
</template>

<script setup lang="ts">
import { Lightbulb } from 'lucide-vue-next'
</script>
