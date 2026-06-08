<template>
  <div class="space-y-6">
    <!-- Clock In/Out Section -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">Today's Attendance</h3>

      <!-- Loading State -->
      <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div v-for="i in 3" :key="i" class="flex flex-col items-center p-6 bg-gray-100 dark:bg-gray-700 rounded-lg animate-pulse">
          <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full mb-3" />
          <div class="w-20 h-4 bg-gray-300 dark:bg-gray-600 rounded mb-2" />
          <div class="w-16 h-8 bg-gray-300 dark:bg-gray-600 rounded" />
        </div>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="flex flex-col items-center p-6 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 rounded-lg">
          <Clock class="w-8 h-8 text-blue-600 dark:text-blue-400 mb-3" />
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Clock In</p>
          <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formattedClockIn }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">{{ todayAttendance?.status || 'No record today' }}</p>
        </div>

        <div class="flex flex-col items-center p-6 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 rounded-lg">
          <Clock class="w-8 h-8 text-green-600 dark:text-green-400 mb-3" />
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Hours Worked</p>
          <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formattedHoursWorked }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Total today</p>
        </div>
      </div>

      <p class="mt-4 text-xs text-gray-400 dark:text-gray-500 text-center">Attendance data is synced from Alpeta.</p>
    </div>

    <!-- Attendance History -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Attendance History</h3>

        <!-- Date Range Filter -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
          <div class="flex items-center gap-2">
            <label class="text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">From</label>
            <DatePicker v-model="filterFrom" />
          </div>
          <div class="flex items-center gap-2">
            <label class="text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">To</label>
            <DatePicker v-model="filterTo" />
          </div>
          <button
            v-if="filterFrom || filterTo"
            @click="clearFilters"
            class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            Clear
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="historyLoading" class="space-y-3">
        <div v-for="i in 5" :key="i" class="h-12 bg-gray-100 dark:bg-gray-700 rounded animate-pulse" />
      </div>

      <div v-else class="overflow-x-auto overflow-y-auto max-h-96">
        <table class="w-full">
          <thead class="sticky top-0 bg-white dark:bg-gray-800 z-10">
            <tr class="border-b border-gray-200 dark:border-gray-700">
              <th class="text-left py-3 px-4 font-medium text-gray-900 dark:text-gray-100">Date</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900 dark:text-gray-100">Clock In</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900 dark:text-gray-100">Clock Out</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900 dark:text-gray-100">Hours Worked</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900 dark:text-gray-100">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="attendanceHistory.length === 0">
              <td colspan="5" class="py-8 text-center text-gray-500 dark:text-gray-400">No attendance records found</td>
            </tr>
            <tr v-for="record in attendanceHistory" :key="record.id" class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
              <td class="py-3 px-4 text-gray-900 dark:text-gray-100">{{ formatDate(record.date) }}</td>
              <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ formatTime(record.clock_in) }}</td>
              <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ formatTime(record.clock_out) }}</td>
              <td class="py-3 px-4 text-gray-900 dark:text-gray-100 font-medium">{{ Number(record.total_hours || 0).toFixed(1) }}h</td>
              <td class="py-3 px-4">
                <span :class="[
                  'inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium',
                  getStatusClass(record.status)
                ]">
                  <span :class="[
                    'w-2 h-2 rounded-full',
                    getStatusDotClass(record.status)
                  ]" />
                  {{ formatStatus(record.status) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { Clock } from 'lucide-vue-next'
import { useTimekeeping, type AttendanceRecord } from '@/composables/useTimekeeping'
import DatePicker from '@/components/ui/DatePicker.vue'

const {
  fetchTodayAttendance,
  fetchAttendanceHistory,
  todayAttendance,
  isLoading,
} = useTimekeeping()

const attendanceHistory = ref<AttendanceRecord[]>([])
const historyLoading = ref(false)
const toDateInputValue = (d: Date) => d.toISOString().slice(0, 10)

const today = new Date()
const oneMonthAgo = new Date(today)
oneMonthAgo.setMonth(oneMonthAgo.getMonth() - 1)

const filterFrom = ref(toDateInputValue(oneMonthAgo))
const filterTo = ref(toDateInputValue(today))

const formattedClockIn = computed(() => {
  if (!todayAttendance.value?.clock_in) return '--:--'
  return formatTime(todayAttendance.value.clock_in)
})

const formattedHoursWorked = computed(() => {
  if (!todayAttendance.value?.total_hours) return '0h'
  const hours = Number(todayAttendance.value.total_hours)
  return `${hours.toFixed(1)}h`
})

const formatTime = (time: string | null): string => {
  if (!time) return '-'
  const date = new Date(time)
  return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const formatStatus = (status: string): string => {
  const statusMap: Record<string, string> = {
    present: 'Present',
    late: 'Late',
    absent: 'Absent',
    half_day: 'Half Day',
    on_leave: 'On Leave',
  }
  return statusMap[status] || status
}

const getStatusClass = (status: string): string => {
  switch (status) {
    case 'present': return 'bg-green-100 text-green-700'
    case 'late': return 'bg-amber-100 text-amber-700'
    case 'absent': return 'bg-red-100 text-red-700'
    case 'half_day': return 'bg-blue-100 text-blue-700'
    case 'on_leave': return 'bg-purple-100 text-purple-700'
    default: return 'bg-gray-100 text-gray-700'
  }
}

const getStatusDotClass = (status: string): string => {
  switch (status) {
    case 'present': return 'bg-green-600'
    case 'late': return 'bg-amber-600'
    case 'absent': return 'bg-red-600'
    case 'half_day': return 'bg-blue-600'
    case 'on_leave': return 'bg-purple-600'
    default: return 'bg-gray-600'
  }
}

const loadHistory = async () => {
  historyLoading.value = true
  try {
    const filters: Record<string, any> = { per_page: 90 }
    if (filterFrom.value) filters.start_date = filterFrom.value
    if (filterTo.value) filters.end_date = filterTo.value
    const response = await fetchAttendanceHistory(filters)
    attendanceHistory.value = response.data || []
  } catch (e) {
    console.error('Failed to load attendance history:', e)
  } finally {
    historyLoading.value = false
  }
}

const clearFilters = () => {
  filterFrom.value = ''
  filterTo.value = ''
}

watch([filterFrom, filterTo], loadHistory)

onMounted(async () => {
  await Promise.all([
    fetchTodayAttendance(),
    loadHistory(),
  ])
})
</script>
