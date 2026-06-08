<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Schedule Change Requests</h3>
        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Review and action pending one-day schedule change requests.</p>
      </div>
      <button
        class="text-sm text-blue-600 hover:underline dark:text-blue-400"
        @click="load"
      >
        Refresh
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 4" :key="i" class="h-14 animate-pulse rounded-lg bg-gray-100 dark:bg-gray-700" />
    </div>

    <!-- Error -->
    <div v-else-if="error" class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-400">{{ error }}</div>

    <!-- Table -->
    <div v-else class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
      <table class="w-full text-sm">
        <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Employee</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Date</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Requested Shift</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Current Shift</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Reason</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
          <tr v-if="requests.length === 0">
            <td colspan="6" class="py-12 text-center text-gray-400 dark:text-gray-500">No pending schedule change requests.</td>
          </tr>
          <tr
            v-for="req in requests"
            :key="req.id"
            class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50"
          >
            <td class="px-4 py-3">
              <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ req.employee?.first_name }} {{ req.employee?.last_name }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">{{ req.employee?.employee_code }}</div>
            </td>
            <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ formatDate(req.date) }}</td>
            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
              <span class="font-medium">{{ req.requested_shift_template?.name }}</span>
              <span class="ml-1 text-xs text-gray-400">
                ({{ formatTime(req.requested_shift_template?.start_time) }} – {{ formatTime(req.requested_shift_template?.end_time) }})
              </span>
            </td>
            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
              {{ req.current_schedule?.shift_template?.name ?? '—' }}
            </td>
            <td class="px-4 py-3 max-w-xs text-gray-600 dark:text-gray-400">
              <span class="line-clamp-2">{{ req.reason }}</span>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <button
                  :disabled="actioningId === req.id"
                  class="rounded-lg bg-green-600 px-3 py-1 text-xs font-medium text-white hover:bg-green-700 disabled:opacity-50"
                  @click="handleApprove(req.id)"
                >
                  Approve
                </button>
                <button
                  :disabled="actioningId === req.id"
                  class="rounded-lg bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-700 disabled:opacity-50"
                  @click="openRejectModal(req)"
                >
                  Reject
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Reject Modal -->
    <div v-if="rejectingRequest" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
      <div class="mx-4 w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
        <h3 class="mb-1 text-lg font-semibold text-gray-900 dark:text-white">Reject Schedule Change</h3>
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
          Rejecting request from <strong>{{ rejectingRequest.employee?.first_name }} {{ rejectingRequest.employee?.last_name }}</strong>
          for <strong>{{ formatDate(rejectingRequest.date) }}</strong>.
        </p>
        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Reason for Rejection</label>
          <textarea
            v-model="rejectionReason"
            rows="3"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            placeholder="Provide a reason for rejecting this request..."
          />
        </div>
        <div class="flex justify-end gap-3">
          <button class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" @click="closeRejectModal">Cancel</button>
          <button
            :disabled="!rejectionReason.trim() || actioningId === rejectingRequest.id"
            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
            @click="handleReject"
          >
            {{ actioningId === rejectingRequest.id ? 'Rejecting...' : 'Reject Request' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { push } from 'notivue'

interface Employee {
  id: number
  first_name: string
  last_name: string
  employee_code: string
}

interface ShiftTemplate {
  id: number
  name: string
  start_time: string
  end_time: string
}

interface CurrentSchedule {
  shift_template: ShiftTemplate | null
}

interface ScheduleChangeRequest {
  id: number
  date: string
  reason: string
  status: string
  employee: Employee | null
  requested_shift_template: ShiftTemplate | null
  current_schedule: CurrentSchedule | null
}

const requests = ref<ScheduleChangeRequest[]>([])
const loading = ref(true)
const error = ref('')
const actioningId = ref<number | null>(null)
const rejectingRequest = ref<ScheduleChangeRequest | null>(null)
const rejectionReason = ref('')

const csrfToken = () =>
  decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '')

const headers = () => ({
  Accept: 'application/json',
  'Content-Type': 'application/json',
  'X-Requested-With': 'XMLHttpRequest',
  'X-XSRF-TOKEN': csrfToken(),
})

const formatTime = (time: string | null | undefined): string => {
  if (!time) { return '' }
  return time.substring(0, 5)
}

const formatDate = (date: string): string =>
  new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const res = await fetch('/api/timekeeping/schedule-change/pending', {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
    if (!res.ok) { throw new Error('Failed to load requests') }
    const json = await res.json()
    requests.value = json.data ?? []
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'An error occurred'
  } finally {
    loading.value = false
  }
}

const handleApprove = async (id: number) => {
  actioningId.value = id
  try {
    const res = await fetch(`/api/timekeeping/schedule-change/${id}/approve`, {
      method: 'POST',
      headers: headers(),
      credentials: 'same-origin',
    })
    const json = await res.json()
    if (!res.ok) {
      push.error({ title: 'Error', message: json.error ?? 'Failed to approve request.' })
      return
    }
    push.success({ title: 'Approved', message: 'Schedule change request approved and employee schedule updated.' })
    await load()
  } catch {
    push.error({ title: 'Error', message: 'An unexpected error occurred.' })
  } finally {
    actioningId.value = null
  }
}

const openRejectModal = (req: ScheduleChangeRequest) => {
  rejectingRequest.value = req
  rejectionReason.value = ''
}

const closeRejectModal = () => {
  rejectingRequest.value = null
  rejectionReason.value = ''
}

const handleReject = async () => {
  if (!rejectingRequest.value || !rejectionReason.value.trim()) { return }
  const id = rejectingRequest.value.id
  actioningId.value = id
  try {
    const res = await fetch(`/api/timekeeping/schedule-change/${id}/reject`, {
      method: 'POST',
      headers: headers(),
      credentials: 'same-origin',
      body: JSON.stringify({ rejection_reason: rejectionReason.value }),
    })
    const json = await res.json()
    if (!res.ok) {
      push.error({ title: 'Error', message: json.error ?? 'Failed to reject request.' })
      return
    }
    push.success({ title: 'Rejected', message: 'Schedule change request rejected.' })
    closeRejectModal()
    await load()
  } catch {
    push.error({ title: 'Error', message: 'An unexpected error occurred.' })
  } finally {
    actioningId.value = null
  }
}

onMounted(load)
</script>
