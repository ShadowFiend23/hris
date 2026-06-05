<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-semibold text-gray-900">Leave Requests</h3>
        <p class="text-sm text-gray-500 mt-0.5">Review and action pending leave applications</p>
      </div>
      <button @click="load" class="text-sm text-blue-600 hover:underline">Refresh</button>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 4" :key="i" class="h-16 animate-pulse rounded-lg bg-gray-100" />
    </div>

    <!-- Error -->
    <div v-else-if="error" class="rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ error }}</div>

    <!-- Empty -->
    <div v-else-if="requests.length === 0" class="rounded-lg border border-gray-200 py-16 text-center">
      <CheckCircle class="mx-auto mb-3 h-10 w-10 text-green-400" />
      <p class="font-medium text-gray-700">No pending leave requests</p>
      <p class="mt-1 text-sm text-gray-500">All caught up!</p>
    </div>

    <!-- Table -->
    <div v-else class="overflow-x-auto rounded-lg border border-gray-200">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employee</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Leave Type</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Dates</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Days</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reason</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-for="req in requests" :key="req.id" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <div class="text-sm font-medium text-gray-900">{{ req.employee?.full_name ?? '—' }}</div>
              <div class="text-xs text-gray-500">{{ req.employee?.employee_id ?? '' }}</div>
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ req.leave_type?.name ?? '—' }}</td>
            <td class="px-4 py-3 text-sm text-gray-600">
              {{ formatDate(req.start_date) }}
              <span v-if="req.end_date !== req.start_date"> – {{ formatDate(req.end_date) }}</span>
            </td>
            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ req.total_days }}d</td>
            <td class="px-4 py-3 max-w-xs text-sm text-gray-600 truncate">{{ req.reason || '—' }}</td>
            <td class="px-4 py-3">
              <div v-if="rejectingId !== req.id" class="flex items-center gap-2">
                <button
                  @click="approve(req.id)"
                  :disabled="processingId === req.id"
                  class="rounded bg-green-600 px-3 py-1 text-xs font-medium text-white hover:bg-green-700 disabled:opacity-50"
                >
                  {{ processingId === req.id ? '...' : 'Approve' }}
                </button>
                <button
                  @click="rejectingId = req.id"
                  class="rounded border border-red-300 px-3 py-1 text-xs font-medium text-red-600 hover:bg-red-50"
                >
                  Reject
                </button>
              </div>
              <!-- Rejection reason inline form -->
              <div v-else class="flex flex-col gap-1 min-w-[200px]">
                <input
                  v-model="rejectionReason"
                  type="text"
                  placeholder="Rejection reason…"
                  class="rounded border border-gray-300 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-red-400"
                />
                <div class="flex gap-1">
                  <button
                    @click="reject(req.id)"
                    :disabled="!rejectionReason.trim() || processingId === req.id"
                    class="rounded bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-700 disabled:opacity-50"
                  >
                    {{ processingId === req.id ? '...' : 'Confirm' }}
                  </button>
                  <button
                    @click="rejectingId = null; rejectionReason = ''"
                    class="rounded border border-gray-300 px-3 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50"
                  >
                    Cancel
                  </button>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Recent History -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h4 class="mb-4 font-semibold text-gray-900">Recent Leave Requests</h4>

      <div v-if="historyLoading" class="space-y-2">
        <div v-for="i in 3" :key="i" class="h-10 animate-pulse rounded bg-gray-100" />
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="py-2 text-left text-xs font-medium text-gray-500">Employee</th>
              <th class="py-2 text-left text-xs font-medium text-gray-500">Type</th>
              <th class="py-2 text-left text-xs font-medium text-gray-500">Dates</th>
              <th class="py-2 text-left text-xs font-medium text-gray-500">Days</th>
              <th class="py-2 text-left text-xs font-medium text-gray-500">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="history.length === 0">
              <td colspan="5" class="py-6 text-center text-sm text-gray-400">No recent records</td>
            </tr>
            <tr v-for="rec in history" :key="rec.id" class="border-b border-gray-100">
              <td class="py-2 text-sm text-gray-900">{{ rec.employee?.full_name ?? '—' }}</td>
              <td class="py-2 text-sm text-gray-600">{{ rec.leave_type?.name ?? '—' }}</td>
              <td class="py-2 text-sm text-gray-600">{{ formatDate(rec.start_date) }}</td>
              <td class="py-2 text-sm text-gray-900">{{ rec.total_days }}d</td>
              <td class="py-2">
                <span :class="statusBadgeClass(rec.status)" class="rounded-full px-2 py-0.5 text-xs font-medium">
                  {{ formatStatus(rec.status) }}
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
import { onMounted, ref } from 'vue'
import { CheckCircle } from 'lucide-vue-next'

interface LeaveRequest {
  id: number
  start_date: string
  end_date: string
  total_days: number
  reason: string | null
  status: string
  employee?: { full_name: string; employee_id: string }
  leave_type?: { name: string }
}

const requests = ref<LeaveRequest[]>([])
const history = ref<LeaveRequest[]>([])
const loading = ref(true)
const historyLoading = ref(false)
const error = ref('')
const processingId = ref<number | null>(null)
const rejectingId = ref<number | null>(null)
const rejectionReason = ref('')

const csrfToken = () =>
  decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '')

const apiPost = async (url: string, body?: object) => {
  const res = await fetch(url, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-XSRF-TOKEN': csrfToken(),
    },
    credentials: 'same-origin',
    body: body ? JSON.stringify(body) : undefined,
  })
  if (!res.ok) {
    const json = await res.json().catch(() => ({}))
    throw new Error((json as any).error ?? 'Request failed')
  }
  return res.json()
}

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const res = await fetch('/api/timekeeping/leave/pending', {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
    if (!res.ok) { throw new Error('Failed to load') }
    const json = await res.json()
    requests.value = json.data ?? []
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'An error occurred'
  } finally {
    loading.value = false
  }
}

const loadHistory = async () => {
  historyLoading.value = true
  try {
    const res = await fetch('/api/timekeeping/leave/history', {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
    if (!res.ok) { return }
    const json = await res.json()
    history.value = (json.data ?? []).slice(0, 10)
  } finally {
    historyLoading.value = false
  }
}

const approve = async (id: number) => {
  processingId.value = id
  try {
    await apiPost(`/api/timekeeping/leave/request/${id}/approve`)
    await Promise.all([load(), loadHistory()])
  } catch {
    // silently
  } finally {
    processingId.value = null
  }
}

const reject = async (id: number) => {
  if (!rejectionReason.value.trim()) { return }
  processingId.value = id
  try {
    await apiPost(`/api/timekeeping/leave/request/${id}/reject`, { rejection_reason: rejectionReason.value })
    rejectingId.value = null
    rejectionReason.value = ''
    await Promise.all([load(), loadHistory()])
  } catch {
    // silently
  } finally {
    processingId.value = null
  }
}

const formatDate = (date: string) =>
  new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })

const formatStatus = (status: string) => {
  const map: Record<string, string> = { pending: 'Pending', approved: 'Approved', rejected: 'Rejected', cancelled: 'Cancelled' }
  return map[status] ?? status
}

const statusBadgeClass = (status: string) => {
  switch (status) {
    case 'approved': return 'bg-green-100 text-green-700'
    case 'pending': return 'bg-amber-100 text-amber-700'
    case 'rejected': return 'bg-red-100 text-red-700'
    default: return 'bg-gray-100 text-gray-700'
  }
}

onMounted(() => Promise.all([load(), loadHistory()]))
</script>
