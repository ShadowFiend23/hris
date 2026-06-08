<template>
  <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
    <div class="mb-3 flex items-center gap-2">
      <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/40">
        <component :is="resolvedIcon" :size="16" class="text-blue-600 dark:text-blue-400" />
      </div>
      <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
    </div>
    <div class="text-sm text-gray-600 dark:text-gray-400">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import * as icons from 'lucide-vue-next'

const props = defineProps<{
  title: string
  icon: string
}>()

const resolvedIcon = computed(() => (icons as Record<string, unknown>)[props.icon])
</script>
