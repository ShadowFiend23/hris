<template>
  <div class="space-y-6">
    <!-- Schedule Overview -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Weekly Schedule</h3>
        <div class="flex items-center gap-2">
          <button @click="navigateWeek(-1)" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg">
            <ChevronLeft :size="20" />
          </button>
          <span class="text-sm font-medium text-gray-700">{{ weekRange }}</span>
          <button @click="navigateWeek(1)" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg">
            <ChevronRight :size="20" />
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="scheduleLoading" class="space-y-3">
        <div v-for="i in 7" :key="i" class="h-12 bg-gray-100 rounded animate-pulse" />
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="text-left py-3 px-4 font-medium text-gray-900">Day</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Shift</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Start Time</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">End Time</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Duration</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="weeklySchedule.length === 0">
              <td colspan="6" class="py-8 text-center text-gray-500">No schedule assigned for this week</td>
            </tr>
            <tr v-for="schedule in weeklySchedule" :key="schedule.id" class="border-b border-gray-200 hover:bg-gray-50">
              <td class="py-3 px-4 font-medium text-gray-900">{{ formatDay(schedule.date) }}</td>
              <td class="py-3 px-4 text-gray-600">{{ schedule.shift_template?.name || 'Custom' }}</td>
              <td class="py-3 px-4 text-gray-600">{{ formatTime(schedule.start_time) }}</td>
              <td class="py-3 px-4 text-gray-600">{{ formatTime(schedule.end_time) }}</td>
              <td class="py-3 px-4 text-gray-900">{{ schedule.shift_template?.duration_hours || '-' }}h</td>
              <td class="py-3 px-4">
                <span :class="[
                  'inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium',
                  getScheduleStatusClass(schedule.status)
                ]">
                  <span :class="['w-2 h-2 rounded-full', getScheduleStatusDotClass(schedule.status)]" />
                  {{ formatStatus(schedule.status) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Shift Swap Requests -->
    <div v-if="swapEnabled" class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Shift Swap Requests</h3>

      <!-- Loading State -->
      <div v-if="swapsLoading" class="space-y-3">
        <div v-for="i in 2" :key="i" class="h-24 bg-gray-100 rounded animate-pulse" />
      </div>

      <div v-else class="space-y-4">
        <div v-if="shiftSwaps.length === 0" class="text-center py-8 text-gray-500">
          No shift swap requests
        </div>
        <div v-for="swap in shiftSwaps" :key="swap.id" class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
          <div class="flex items-start justify-between">
            <div>
              <p class="font-medium text-gray-900">
                {{ formatDate(swap.requester_schedule?.date) }} → {{ formatDate(swap.target_schedule?.date) }}
              </p>
              <p class="text-sm text-gray-600 mt-1">
                {{ swap.requester_schedule?.shift_template?.name || 'Shift' }} ↔ {{ swap.target_schedule?.shift_template?.name || 'Shift' }}
              </p>
              <p class="text-sm text-gray-500 mt-1">
                Swap with: {{ swap.target_employee?.first_name }} {{ swap.target_employee?.last_name }}
              </p>
            </div>
            <span :class="[
              'inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium',
              getSwapStatusClass(swap.status)
            ]">
              <span :class="['w-2 h-2 rounded-full', getSwapStatusDotClass(swap.status)]" />
              {{ formatStatus(swap.status) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Request Shift Swap -->
    <div v-if="swapEnabled" class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Request Shift Swap</h3>

      <form @submit.prevent="handleSubmitSwap" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Your Schedule</label>
            <select v-model="shiftSwapForm.my_schedule_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              <option value="">Select your schedule</option>
              <option v-for="schedule in myScheduleOptions" :key="schedule.id" :value="schedule.id">
                {{ formatDate(schedule.date) }} - {{ schedule.shift_template?.name || 'Shift' }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Target Schedule (to swap with)</label>
            <select v-model="shiftSwapForm.target_schedule_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              <option value="">Select target schedule</option>
              <option v-for="schedule in targetScheduleOptions" :key="schedule.id" :value="schedule.id">
                {{ formatDate(schedule.date) }} - {{ schedule.shift_template?.name || 'Shift' }}
                ({{ schedule.employee?.first_name }} {{ schedule.employee?.last_name }})
              </option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Reason</label>
          <textarea v-model="shiftSwapForm.reason" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Provide reason for shift swap" />
        </div>

        <!-- Error Message -->
        <div v-if="submitError" class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
          {{ submitError }}
        </div>

        <!-- Success Message -->
        <div v-if="submitSuccess" class="p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
          Shift swap request submitted successfully!
        </div>

        <button
          type="submit"
          :disabled="isSubmitting"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center gap-2"
        >
          <Loader2 v-if="isSubmitting" class="animate-spin" :size="16" />
          Request Shift Swap
        </button>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { ChevronLeft, ChevronRight, Loader2 } from 'lucide-vue-next'
import { usePage } from '@inertiajs/vue3'
import { useTimekeeping, type EmployeeSchedule } from '@/composables/useTimekeeping'

const page = usePage()
const swapEnabled = computed(() => !!(page.props as any).swapEnabled)

interface ShiftSwapRequest {
  id: number
  status: string
  requester_schedule?: EmployeeSchedule & { shift_template?: { name: string } }
  target_schedule?: EmployeeSchedule & { shift_template?: { name: string } }
  target_employee?: { first_name: string; last_name: string }
}

interface ScheduleWithEmployee extends EmployeeSchedule {
  employee?: { first_name: string; last_name: string }
}

const {
  fetchMySchedule,
  fetchWeeklySchedule,
  requestShiftSwap,
  schedule,
} = useTimekeeping()

const weeklySchedule = ref<EmployeeSchedule[]>([])
const shiftSwaps = ref<ShiftSwapRequest[]>([])
const myScheduleOptions = ref<EmployeeSchedule[]>([])
const targetScheduleOptions = ref<ScheduleWithEmployee[]>([])
const scheduleLoading = ref(false)
const swapsLoading = ref(false)
const isSubmitting = ref(false)
const submitError = ref<string | null>(null)
const submitSuccess = ref(false)
const currentWeekStart = ref(getWeekStart(new Date()))

const shiftSwapForm = ref({
  my_schedule_id: '' as number | string,
  target_schedule_id: '' as number | string,
  reason: ''
})

// Computed
const weekRange = computed(() => {
  const start = new Date(currentWeekStart.value)
  const end = new Date(start)
  end.setDate(end.getDate() + 6)
  return `${formatDate(start.toISOString())} - ${formatDate(end.toISOString())}`
})

// Helper functions
function getWeekStart(date: Date): string {
  const d = new Date(date)
  const day = d.getDay()
  const diff = d.getDate() - day + (day === 0 ? -6 : 1)
  d.setDate(diff)
  return d.toISOString().split('T')[0]
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const formatDay = (date: string): string => {
  return new Date(date).toLocaleDateString('en-US', { weekday: 'long' })
}

const formatTime = (time: string): string => {
  if (!time) return '-'
  const [hours, minutes] = time.split(':')
  const date = new Date()
  date.setHours(parseInt(hours), parseInt(minutes))
  return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

const formatStatus = (status: string): string => {
  const statusMap: Record<string, string> = {
    scheduled: 'Scheduled',
    completed: 'Completed',
    pending: 'Pending',
    approved: 'Approved',
    rejected: 'Rejected',
  }
  return statusMap[status] || status
}

const getScheduleStatusClass = (status: string): string => {
  switch (status) {
    case 'scheduled': return 'bg-blue-100 text-blue-700'
    case 'completed': return 'bg-green-100 text-green-700'
    default: return 'bg-gray-100 text-gray-700'
  }
}

const getScheduleStatusDotClass = (status: string): string => {
  switch (status) {
    case 'scheduled': return 'bg-blue-600'
    case 'completed': return 'bg-green-600'
    default: return 'bg-gray-600'
  }
}

const getSwapStatusClass = (status: string): string => {
  switch (status) {
    case 'approved': return 'bg-green-100 text-green-700'
    case 'pending': return 'bg-amber-100 text-amber-700'
    case 'rejected': return 'bg-red-100 text-red-700'
    default: return 'bg-gray-100 text-gray-700'
  }
}

const getSwapStatusDotClass = (status: string): string => {
  switch (status) {
    case 'approved': return 'bg-green-600'
    case 'pending': return 'bg-amber-600'
    case 'rejected': return 'bg-red-600'
    default: return 'bg-gray-600'
  }
}

// Navigation
const navigateWeek = (direction: number) => {
  const current = new Date(currentWeekStart.value)
  current.setDate(current.getDate() + (direction * 7))
  currentWeekStart.value = getWeekStart(current)
  loadSchedule()
}

// Event handlers
const handleSubmitSwap = async () => {
  if (!shiftSwapForm.value.my_schedule_id || !shiftSwapForm.value.target_schedule_id) {
    submitError.value = 'Please select both schedules'
    return
  }

  isSubmitting.value = true
  submitError.value = null
  submitSuccess.value = false

  try {
    await requestShiftSwap({
      my_schedule_id: Number(shiftSwapForm.value.my_schedule_id),
      target_schedule_id: Number(shiftSwapForm.value.target_schedule_id),
      reason: shiftSwapForm.value.reason || undefined,
    })

    submitSuccess.value = true
    shiftSwapForm.value = { my_schedule_id: '', target_schedule_id: '', reason: '' }

    // Refresh data
    await loadSchedule()
  } catch (e) {
    submitError.value = e instanceof Error ? e.message : 'Failed to submit shift swap request'
  } finally {
    isSubmitting.value = false
  }
}

const loadSchedule = async () => {
  scheduleLoading.value = true
  try {
    const response = await fetchWeeklySchedule(currentWeekStart.value)
    weeklySchedule.value = response.data || []

    // Also load for swap form options
    await fetchMySchedule()
    myScheduleOptions.value = schedule.value
  } catch (e) {
    console.error('Failed to load schedule:', e)
  } finally {
    scheduleLoading.value = false
  }
}

// Initialize data on mount
onMounted(async () => {
  await loadSchedule()
})
</script>
