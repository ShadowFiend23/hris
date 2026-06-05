<template>
  <div class="space-y-6">
    <!-- Clock In/Out Section -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Today's Attendance</h3>

      <!-- Loading State -->
      <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div v-for="i in 3" :key="i" class="flex flex-col items-center p-6 bg-gray-100 rounded-lg animate-pulse">
          <div class="w-8 h-8 bg-gray-300 rounded-full mb-3" />
          <div class="w-20 h-4 bg-gray-300 rounded mb-2" />
          <div class="w-16 h-8 bg-gray-300 rounded" />
        </div>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="flex flex-col items-center p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg">
          <Clock class="w-8 h-8 text-blue-600 mb-3" />
          <p class="text-sm text-gray-600 mb-2">Clock In</p>
          <p class="text-2xl font-bold text-gray-900">{{ formattedClockIn }}</p>
          <p class="text-xs text-gray-500 mt-2">{{ todayAttendance?.status || 'No record today' }}</p>
        </div>

        <div class="flex flex-col items-center p-6 bg-gradient-to-br from-green-50 to-green-100 rounded-lg">
          <Clock class="w-8 h-8 text-green-600 mb-3" />
          <p class="text-sm text-gray-600 mb-2">Hours Worked</p>
          <p class="text-2xl font-bold text-gray-900">{{ formattedHoursWorked }}</p>
          <p class="text-xs text-gray-500 mt-2">Total today</p>
        </div>
      </div>

      <p class="mt-4 text-xs text-gray-400 text-center">Attendance data is synced from Alpeta.</p>
    </div>

    <!-- Attendance History -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Attendance History</h3>

      <!-- Loading State -->
      <div v-if="historyLoading" class="space-y-3">
        <div v-for="i in 5" :key="i" class="h-12 bg-gray-100 rounded animate-pulse" />
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="text-left py-3 px-4 font-medium text-gray-900">Date</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Clock In</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Clock Out</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Hours Worked</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="attendanceHistory.length === 0">
              <td colspan="5" class="py-8 text-center text-gray-500">No attendance records found</td>
            </tr>
            <tr v-for="record in attendanceHistory" :key="record.id" class="border-b border-gray-200 hover:bg-gray-50">
              <td class="py-3 px-4 text-gray-900">{{ formatDate(record.date) }}</td>
              <td class="py-3 px-4 text-gray-600">{{ formatTime(record.clock_in) }}</td>
              <td class="py-3 px-4 text-gray-600">{{ formatTime(record.clock_out) }}</td>
              <td class="py-3 px-4 text-gray-900 font-medium">{{ Number(record.total_hours || 0).toFixed(1) }}h</td>
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
import { ref, computed, onMounted } from 'vue'
import { Clock } from 'lucide-vue-next'
import { useTimekeeping, type AttendanceRecord } from '@/composables/useTimekeeping'

const {
  fetchTodayAttendance,
  fetchAttendanceHistory,
  todayAttendance,
  isLoading,
} = useTimekeeping()

const attendanceHistory = ref<AttendanceRecord[]>([])
const historyLoading = ref(false)

// Computed properties for display formatting
const formattedClockIn = computed(() => {
  if (!todayAttendance.value?.clock_in) return '--:--'
  return formatTime(todayAttendance.value.clock_in)
})

const formattedHoursWorked = computed(() => {
  if (!todayAttendance.value?.total_hours) return '0h'
  const hours = Number(todayAttendance.value.total_hours)
  return `${hours.toFixed(1)}h`
})

// Helper functions
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
    const response = await fetchAttendanceHistory({ per_page: 10 })
    attendanceHistory.value = response.data || []
  } catch (e) {
    console.error('Failed to load attendance history:', e)
  } finally {
    historyLoading.value = false
  }
}

// Initialize data on mount
onMounted(async () => {
  await Promise.all([
    fetchTodayAttendance(),
    loadHistory(),
  ])
})
</script>
