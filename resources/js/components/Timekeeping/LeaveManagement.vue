<template>
  <div class="space-y-6">
    <!-- Leave Balance Overview -->
    <div v-if="balanceLoading" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
      <div v-for="i in 4" :key="i" class="rounded-lg border border-gray-200 p-4 animate-pulse">
        <div class="h-3 bg-gray-200 rounded w-20 mb-3" />
        <div class="h-8 bg-gray-200 rounded w-12 mb-2" />
        <div class="h-2 bg-gray-200 rounded w-full" />
      </div>
    </div>
    <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
      <div v-if="paidLeaveBalances.length === 0" class="col-span-5 text-center py-8 text-gray-500">
        No leave balances configured
      </div>
      <div
        v-for="(balance, i) in paidLeaveBalances"
        :key="balance.id"
        class="rounded-lg border p-4"
        :class="leaveColors[i % leaveColors.length].card"
      >
        <p class="text-xs font-medium mb-2 truncate" :class="leaveColors[i % leaveColors.length].label">
          {{ balance.leave_type?.name ?? 'Leave' }}
        </p>
        <div class="flex items-baseline gap-1 mb-1">
          <span class="text-2xl font-bold" :class="leaveColors[i % leaveColors.length].value">
            {{ parseFloat(String(balance.remaining_days ?? 0)).toFixed(0) }}
          </span>
          <span class="text-xs" :class="leaveColors[i % leaveColors.length].sub">
            / {{ balance.total_days }}d
          </span>
        </div>
        <div class="w-full rounded-full h-1.5 mt-2" :class="leaveColors[i % leaveColors.length].track">
          <div
            class="h-1.5 rounded-full transition-all"
            :class="leaveColors[i % leaveColors.length].bar"
            :style="{
              width: `${Math.min(100, (parseFloat(String(balance.remaining_days ?? 0)) / (balance.total_days || 1)) * 100)}%`,
            }"
          />
        </div>
        <p class="text-xs mt-2" :class="leaveColors[i % leaveColors.length].sub">
          used: {{ parseFloat(String(balance.used_days ?? 0)).toFixed(0) }}
        </p>
      </div>
    </div>

    <!-- New Leave Request -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Request New Leave</h3>

      <form @submit.prevent="handleSubmitLeave" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Leave Type</label>
            <select v-model="leaveForm.leave_type_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              <option value="">Select leave type</option>
              <option v-for="type in paidLeaveTypes" :key="type.id" :value="type.id">
                {{ type.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Number of Days</label>
            <input :value="calculatedDays" type="number" disabled class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
            <input v-model="leaveForm.start_date" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
            <input v-model="leaveForm.end_date" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Reason</label>
          <textarea v-model="leaveForm.reason" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Provide reason for leave request" />
        </div>

        <!-- Error Message -->
        <div v-if="submitError" class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
          {{ submitError }}
        </div>

        <!-- Success Message -->
        <div v-if="submitSuccess" class="p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
          Leave request submitted successfully!
        </div>

        <button
          type="submit"
          :disabled="isSubmitting"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center gap-2"
        >
          <Loader2 v-if="isSubmitting" class="animate-spin" :size="16" />
          Submit Leave Request
        </button>
      </form>
    </div>

    <!-- Leave Requests History -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Leave Requests</h3>

      <!-- Loading State -->
      <div v-if="historyLoading" class="space-y-3">
        <div v-for="i in 3" :key="i" class="h-12 bg-gray-100 rounded animate-pulse" />
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="text-left py-3 px-4 font-medium text-gray-900">Type</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Start Date</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">End Date</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Days</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Status</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="leaveRequests.length === 0">
              <td colspan="6" class="py-8 text-center text-gray-500">No leave requests found</td>
            </tr>
            <tr v-for="request in leaveRequests" :key="request.id" class="border-b border-gray-200 hover:bg-gray-50">
              <td class="py-3 px-4 text-gray-900 font-medium">{{ request.leave_type?.name || 'Leave' }}</td>
              <td class="py-3 px-4 text-gray-600">{{ formatDate(request.start_date) }}</td>
              <td class="py-3 px-4 text-gray-600">{{ formatDate(request.end_date) }}</td>
              <td class="py-3 px-4 text-gray-900">{{ request.total_days }}</td>
              <td class="py-3 px-4">
                <span :class="[
                  'inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium',
                  getStatusClass(request.status)
                ]">
                  <span :class="[
                    'w-2 h-2 rounded-full',
                    getStatusDotClass(request.status)
                  ]" />
                  {{ formatStatus(request.status) }}
                </span>
              </td>
              <td class="py-3 px-4">
                <button
                  v-if="request.status === 'pending'"
                  @click="handleCancelRequest(request.id)"
                  :disabled="cancellingId === request.id"
                  class="text-red-600 hover:text-red-700 text-sm font-medium disabled:opacity-50"
                >
                  {{ cancellingId === request.id ? 'Cancelling...' : 'Cancel' }}
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
import { useTimekeeping, type LeaveType, type LeaveBalance, type LeaveRequest } from '@/composables/useTimekeeping'

const {
  fetchLeaveTypes,
  fetchLeaveBalance,
  fetchLeaveHistory,
  submitLeaveRequest,
  cancelLeaveRequest,
  leaveTypes,
  leaveBalances,
} = useTimekeeping()

const paidLeaveBalances = computed(() =>
  leaveBalances.value.filter((b) => b.leave_type?.is_paid !== false),
)

const paidLeaveTypes = computed(() =>
  leaveTypes.value.filter((t) => t.is_paid !== false),
)

const leaveColors = [
  { card: 'bg-blue-50 border-blue-200', label: 'text-blue-600', value: 'text-blue-800', sub: 'text-blue-400', track: 'bg-blue-200', bar: 'bg-blue-500' },
  { card: 'bg-green-50 border-green-200', label: 'text-green-600', value: 'text-green-800', sub: 'text-green-400', track: 'bg-green-200', bar: 'bg-green-500' },
  { card: 'bg-amber-50 border-amber-200', label: 'text-amber-600', value: 'text-amber-800', sub: 'text-amber-400', track: 'bg-amber-200', bar: 'bg-amber-500' },
  { card: 'bg-purple-50 border-purple-200', label: 'text-purple-600', value: 'text-purple-800', sub: 'text-purple-400', track: 'bg-purple-200', bar: 'bg-purple-500' },
  { card: 'bg-rose-50 border-rose-200', label: 'text-rose-600', value: 'text-rose-800', sub: 'text-rose-400', track: 'bg-rose-200', bar: 'bg-rose-500' },
  { card: 'bg-teal-50 border-teal-200', label: 'text-teal-600', value: 'text-teal-800', sub: 'text-teal-400', track: 'bg-teal-200', bar: 'bg-teal-500' },
]

const leaveRequests = ref<LeaveRequest[]>([])
const historyLoading = ref(false)
const balanceLoading = ref(false)
const isSubmitting = ref(false)
const submitError = ref<string | null>(null)
const submitSuccess = ref(false)
const cancellingId = ref<number | null>(null)

const leaveForm = ref({
  leave_type_id: '' as number | string,
  start_date: '',
  end_date: '',
  reason: ''
})

// Calculate days between dates
const calculatedDays = computed(() => {
  if (!leaveForm.value.start_date || !leaveForm.value.end_date) return 0
  const start = new Date(leaveForm.value.start_date)
  const end = new Date(leaveForm.value.end_date)
  const diffTime = Math.abs(end.getTime() - start.getTime())
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1
})

// Helper functions
const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const formatStatus = (status: string): string => {
  const statusMap: Record<string, string> = {
    pending: 'Pending',
    approved: 'Approved',
    rejected: 'Rejected',
    cancelled: 'Cancelled',
  }
  return statusMap[status] || status
}

const getStatusClass = (status: string): string => {
  switch (status) {
    case 'approved': return 'bg-green-100 text-green-700'
    case 'pending': return 'bg-amber-100 text-amber-700'
    case 'rejected': return 'bg-red-100 text-red-700'
    case 'cancelled': return 'bg-gray-100 text-gray-700'
    default: return 'bg-gray-100 text-gray-700'
  }
}

const getStatusDotClass = (status: string): string => {
  switch (status) {
    case 'approved': return 'bg-green-600'
    case 'pending': return 'bg-amber-600'
    case 'rejected': return 'bg-red-600'
    case 'cancelled': return 'bg-gray-600'
    default: return 'bg-gray-600'
  }
}

// Event handlers
const handleSubmitLeave = async () => {
  if (!leaveForm.value.leave_type_id || !leaveForm.value.start_date || !leaveForm.value.end_date) {
    submitError.value = 'Please fill in all required fields'
    return
  }

  isSubmitting.value = true
  submitError.value = null
  submitSuccess.value = false

  try {
    await submitLeaveRequest({
      leave_type_id: Number(leaveForm.value.leave_type_id),
      start_date: leaveForm.value.start_date,
      end_date: leaveForm.value.end_date,
      reason: leaveForm.value.reason || undefined,
    })

    submitSuccess.value = true
    leaveForm.value = { leave_type_id: '', start_date: '', end_date: '', reason: '' }

    // Refresh data
    await Promise.all([loadHistory(), loadBalance()])
  } catch (e) {
    submitError.value = e instanceof Error ? e.message : 'Failed to submit leave request'
  } finally {
    isSubmitting.value = false
  }
}

const handleCancelRequest = async (requestId: number) => {
  cancellingId.value = requestId
  try {
    await cancelLeaveRequest(requestId)
    await Promise.all([loadHistory(), loadBalance()])
  } catch (e) {
    console.error('Failed to cancel leave request:', e)
  } finally {
    cancellingId.value = null
  }
}

const loadHistory = async () => {
  historyLoading.value = true
  try {
    const response = await fetchLeaveHistory({ per_page: 10 })
    leaveRequests.value = response.data || []
  } catch (e) {
    console.error('Failed to load leave history:', e)
  } finally {
    historyLoading.value = false
  }
}

const loadBalance = async () => {
  balanceLoading.value = true
  try {
    await fetchLeaveBalance()
  } catch (e) {
    console.error('Failed to load leave balance:', e)
  } finally {
    balanceLoading.value = false
  }
}

// Initialize data on mount
onMounted(async () => {
  await Promise.all([
    fetchLeaveTypes(),
    loadBalance(),
    loadHistory(),
  ])
})
</script>
