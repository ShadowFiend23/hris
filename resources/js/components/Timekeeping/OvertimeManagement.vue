<template>
  <div class="space-y-6">
    <!-- Overtime Summary -->
    <div v-if="summaryLoading" class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div v-for="i in 4" :key="i" class="bg-white rounded-lg border border-gray-200 p-6 animate-pulse">
        <div class="h-4 bg-gray-200 rounded w-32 mb-2" />
        <div class="h-10 bg-gray-200 rounded w-16" />
      </div>
    </div>
    <div v-else class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <p class="text-gray-600 text-sm font-medium">Overtime This Month</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">{{ summary.thisMonth }}h</p>
        <p class="text-xs text-gray-500 mt-2">{{ summary.thisMonthCount }} sessions</p>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <p class="text-gray-600 text-sm font-medium">Overtime This Year</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">{{ summary.thisYear }}h</p>
        <p class="text-xs text-gray-500 mt-2">across {{ summary.thisYearCount }} sessions</p>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <p class="text-gray-600 text-sm font-medium">Pending Approval</p>
        <p class="text-3xl font-bold text-amber-600 mt-2">{{ summary.pending }}h</p>
        <p class="text-xs text-gray-500 mt-2">{{ summary.pendingCount }} pending requests</p>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <p class="text-gray-600 text-sm font-medium">Approved</p>
        <p class="text-3xl font-bold text-green-600 mt-2">{{ summary.approved }}h</p>
        <p class="text-xs text-gray-500 mt-2">this period</p>
      </div>
    </div>

    <!-- Request Overtime -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Request Overtime</h3>

      <form @submit.prevent="handleSubmitOvertime" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Date of Time In</label>
            <DatePicker v-model="overtimeForm.start_date" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Time In</label>
            <input v-model="overtimeForm.start_time" type="time" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Date of Time Out</label>
            <DatePicker v-model="overtimeForm.end_date" :min-date="overtimeForm.start_date || undefined" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Time Out</label>
            <input v-model="overtimeForm.end_time" type="time" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
          </div>
        </div>

        <!-- Computed hours preview -->
        <div v-if="computedHours !== null" class="rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-800">
          Total overtime: <span class="font-semibold">{{ computedHours }}h</span>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Reason/Project</label>
          <textarea v-model="overtimeForm.reason" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Describe the reason for overtime" />
        </div>

        <!-- Error Message -->
        <div v-if="submitError" class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
          {{ submitError }}
        </div>

        <!-- Success Message -->
        <div v-if="submitSuccess" class="p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
          Overtime request submitted successfully!
        </div>

        <button
          type="submit"
          :disabled="isSubmitting"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center gap-2"
        >
          <Loader2 v-if="isSubmitting" class="animate-spin" :size="16" />
          Submit Overtime Request
        </button>
      </form>
    </div>

    <!-- Overtime History -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Overtime History</h3>

      <!-- Loading State -->
      <div v-if="historyLoading" class="space-y-3">
        <div v-for="i in 5" :key="i" class="h-12 bg-gray-100 rounded animate-pulse" />
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="text-left py-3 px-4 font-medium text-gray-900">Date</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Time In</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Time Out</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Hours</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Reason</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Status</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="overtimeHistory.length === 0">
              <td colspan="7" class="py-8 text-center text-gray-500">No overtime records found</td>
            </tr>
            <tr v-for="record in overtimeHistory" :key="record.id" class="border-b border-gray-200 hover:bg-gray-50">
              <td class="py-3 px-4 text-gray-900">{{ formatDate(record.date) }}</td>
              <td class="py-3 px-4 text-gray-600">{{ formatDateTime(record.start_at) }}</td>
              <td class="py-3 px-4 text-gray-600">{{ formatDateTime(record.end_at) }}</td>
              <td class="py-3 px-4 text-gray-900 font-medium">{{ record.hours }}h</td>
              <td class="py-3 px-4 text-gray-600">{{ record.reason || '-' }}</td>
              <td class="py-3 px-4">
                <span :class="[
                  'inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium',
                  getStatusClass(record.status)
                ]">
                  <span :class="['w-2 h-2 rounded-full', getStatusDotClass(record.status)]" />
                  {{ formatStatus(record.status) }}
                </span>
              </td>
              <td class="py-3 px-4">
                <button
                  v-if="record.status === 'pending'"
                  @click="handleCancelOvertime(record.id)"
                  :disabled="cancellingId === record.id"
                  class="text-red-600 hover:text-red-700 text-sm font-medium disabled:opacity-50"
                >
                  {{ cancellingId === record.id ? 'Cancelling...' : 'Cancel' }}
                </button>
                <span v-else class="text-gray-400 text-sm">-</span>
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
import { Loader2 } from 'lucide-vue-next'
import { useTimekeeping, type OvertimeRecord } from '@/composables/useTimekeeping'
import DatePicker from '@/components/ui/DatePicker.vue'

const {
  submitOvertimeRequest,
  fetchOvertimeHistory,
  fetchOvertimeSummary,
  cancelOvertimeRequest,
} = useTimekeeping()

const overtimeHistory = ref<OvertimeRecord[]>([])
const summary = ref({
  thisMonth: 0,
  thisMonthCount: 0,
  thisYear: 0,
  thisYearCount: 0,
  pending: 0,
  pendingCount: 0,
  approved: 0,
})

const historyLoading = ref(false)
const summaryLoading = ref(false)
const isSubmitting = ref(false)
const submitError = ref<string | null>(null)
const submitSuccess = ref(false)
const cancellingId = ref<number | null>(null)

const overtimeForm = ref({
  start_date: '',
  start_time: '',
  end_date: '',
  end_time: '',
  reason: ''
})

// Live preview of the computed overtime hours from the chosen window.
const computedHours = computed<number | null>(() => {
  const { start_date, start_time, end_date, end_time } = overtimeForm.value
  if (!start_date || !start_time || !end_date || !end_time) { return null }
  const start = new Date(`${start_date}T${start_time}`)
  const end = new Date(`${end_date}T${end_time}`)
  const diffMs = end.getTime() - start.getTime()
  if (Number.isNaN(diffMs) || diffMs <= 0) { return null }
  return Math.round((diffMs / 3_600_000) * 100) / 100
})

// Helper functions
const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const formatDateTime = (value: string | null): string => {
  if (!value) { return '-' }
  return new Date(value).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const formatStatus = (status: string): string => {
  const statusMap: Record<string, string> = {
    pending: 'Pending',
    approved: 'Approved',
    rejected: 'Rejected',
    paid: 'Paid',
  }
  return statusMap[status] || status
}

const getStatusClass = (status: string): string => {
  switch (status) {
    case 'approved': return 'bg-green-100 text-green-700'
    case 'pending': return 'bg-amber-100 text-amber-700'
    case 'rejected': return 'bg-red-100 text-red-700'
    case 'paid': return 'bg-blue-100 text-blue-700'
    default: return 'bg-gray-100 text-gray-700'
  }
}

const getStatusDotClass = (status: string): string => {
  switch (status) {
    case 'approved': return 'bg-green-600'
    case 'pending': return 'bg-amber-600'
    case 'rejected': return 'bg-red-600'
    case 'paid': return 'bg-blue-600'
    default: return 'bg-gray-600'
  }
}

// Event handlers
const handleSubmitOvertime = async () => {
  const { start_date, start_time, end_date, end_time } = overtimeForm.value
  if (!start_date || !start_time || !end_date || !end_time) {
    submitError.value = 'Please fill in the time-in and time-out fields'
    return
  }

  if (computedHours.value === null) {
    submitError.value = 'The time-out must be after the time-in'
    return
  }

  isSubmitting.value = true
  submitError.value = null
  submitSuccess.value = false

  try {
    await submitOvertimeRequest({
      start_date,
      start_time,
      end_date,
      end_time,
      reason: overtimeForm.value.reason || undefined,
    })

    submitSuccess.value = true
    overtimeForm.value = { start_date: '', start_time: '', end_date: '', end_time: '', reason: '' }

    // Refresh data
    await Promise.all([loadHistory(), loadSummary()])
  } catch (e) {
    submitError.value = e instanceof Error ? e.message : 'Failed to submit overtime request'
  } finally {
    isSubmitting.value = false
  }
}

const handleCancelOvertime = async (overtimeId: number) => {
  cancellingId.value = overtimeId
  try {
    await cancelOvertimeRequest(overtimeId)
    await Promise.all([loadHistory(), loadSummary()])
  } catch (e) {
    console.error('Failed to cancel overtime request:', e)
  } finally {
    cancellingId.value = null
  }
}

const loadHistory = async () => {
  historyLoading.value = true
  try {
    const response = await fetchOvertimeHistory({ per_page: 10 })
    overtimeHistory.value = response.data || []
  } catch (e) {
    console.error('Failed to load overtime history:', e)
  } finally {
    historyLoading.value = false
  }
}

const loadSummary = async () => {
  summaryLoading.value = true
  try {
    const now = new Date()
    const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0]
    const startOfYear = new Date(now.getFullYear(), 0, 1).toISOString().split('T')[0]
    const endOfYear = new Date(now.getFullYear(), 11, 31).toISOString().split('T')[0]

    const response = await fetchOvertimeSummary(startOfYear, endOfYear)
    if (response.data) {
      summary.value = {
        thisMonth: response.data.total_hours || 0,
        thisMonthCount: response.data.total_records || 0,
        thisYear: response.data.total_hours || 0,
        thisYearCount: response.data.total_records || 0,
        pending: response.data.pending_hours || 0,
        pendingCount: response.data.pending_count || 0,
        approved: response.data.approved_hours || 0,
      }
    }
  } catch (e) {
    console.error('Failed to load overtime summary:', e)
  } finally {
    summaryLoading.value = false
  }
}

// Initialize data on mount
onMounted(async () => {
  await Promise.all([loadHistory(), loadSummary()])
})
</script>
