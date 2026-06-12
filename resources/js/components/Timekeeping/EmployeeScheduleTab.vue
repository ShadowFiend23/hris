<template>
  <div class="space-y-6">
    <!-- Sub-tab buttons -->
    <div class="flex gap-2">
      <button
        type="button"
        class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors"
        :class="activeSubTab === 'change_schedule'
          ? 'bg-blue-600 text-white'
          : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'"
        @click="activeSubTab = 'change_schedule'"
      >
        <CalendarClock :size="16" />
        Change Schedule
      </button>
      <button
        type="button"
        class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors"
        :class="activeSubTab === 'shifts'
          ? 'bg-blue-600 text-white'
          : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'"
        @click="activeSubTab = 'shifts'"
      >
        <LayoutGrid :size="16" />
        Shifts
      </button>
    </div>

    <ScheduleChangeRequest v-if="activeSubTab === 'change_schedule'" />
    <ShiftScheduling v-else />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { CalendarClock, LayoutGrid } from 'lucide-vue-next'
import ScheduleChangeRequest from '@/components/Timekeeping/ScheduleChangeRequest.vue'
import ShiftScheduling from '@/components/Timekeeping/ShiftScheduling.vue'

const activeSubTab = ref<'change_schedule' | 'shifts'>('change_schedule')
</script>
