<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-semibold text-gray-900">Overtime Requests</h3>
        <p class="text-sm text-gray-500 mt-0.5">Review and action pending overtime applications</p>
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
    <div v-else-if="pending.length === 0" class="rounded-lg border border-gray-200 py-16 text-center">
      <CheckCircle class="mx-auto mb-3 h-10 w-10 text-green-400" />
      <p class="font-medium text-gray-700">No pending overtime requests</p>
      <p class="mt-1 text-sm text-gray-500">All caught up!</p>
    </div>

    <!-- Pending Requests Table -->
    <div v-else class="overflow-x-auto rounded-lg border border-gray-200">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employee</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Time In</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Time Out</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Hours</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reason</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-for="rec in pending" :key="rec.id" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <div class="text-sm font-medium text-gray-900">{{ rec.employee?.full_name ?? '—' }}</div>
              <div class="text-xs text-gray-500">{{ rec.employee?.employee_id ?? '' }}</div>
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ formatDate(rec.date) }}</td>
            <td class="px-4 py-3 text-sm text-gray-600">{{ formatDateTime(rec.start_at) }}</td>
            <td class="px-4 py-3 text-sm text-gray-600">{{ formatDateTime(rec.end_at) }}</td>
            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ rec.hours }}h</td>
            <td class="px-4 py-3 max-w-xs truncate text-sm text-gray-600">{{ rec.reason || '—' }}</td>
            <td class="px-4 py-3">
              <div v-if="rejectingId !== rec.id" class="flex items-center gap-2">
                <button
                  @click="approve(rec.id)"
                  :disabled="processingId === rec.id"
                  class="rounded bg-green-600 px-3 py-1 text-xs font-medium text-white hover:bg-green-700 disabled:opacity-50"
                >
                  {{ processingId === rec.id ? '...' : 'Approve' }}
                </button>
                <button
                  @click="rejectingId = rec.id"
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
                    @click="reject(rec.id)"
                    :disabled="!rejectionReason.trim() || processingId === rec.id"
                    class="rounded bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-700 disabled:opacity-50"
                  >
                    {{ processingId === rec.id ? '...' : 'Confirm' }}
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

    <!-- Approved — Mark Paid Section -->
    <div class="rounded-lg border border-gray-200 bg-white p-6">
      <h4 class="mb-4 font-semibold text-gray-900">Approved Overtime</h4>

      <div v-if="historyLoading" class="space-y-2">
        <div v-for="i in 3" :key="i" class="h-10 animate-pulse rounded bg-gray-100" />
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="py-2 pr-4 text-left text-xs font-medium text-gray-500">Employee</th>
              <th class="py-2 pr-4 text-left text-xs font-medium text-gray-500">Date</th>
              <th class="py-2 pr-4 text-left text-xs font-medium text-gray-500">Time In</th>
              <th class="py-2 pr-4 text-left text-xs font-medium text-gray-500">Time Out</th>
              <th class="py-2 pr-4 text-left text-xs font-medium text-gray-500">Hours</th>
              <th class="py-2 pr-4 text-left text-xs font-medium text-gray-500">Status</th>
              <th class="py-2 text-left text-xs font-medium text-gray-500">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="approved.length === 0">
              <td colspan="7" class="py-6 text-center text-sm text-gray-400">No approved overtime records</td>
            </tr>
            <tr v-for="rec in approved" :key="rec.id" class="border-b border-gray-100 hover:bg-gray-50">
              <td class="py-2 pr-4 text-sm text-gray-900">{{ rec.employee?.full_name ?? '—' }}</td>
              <td class="py-2 pr-4 text-sm text-gray-600">{{ formatDate(rec.date) }}</td>
              <td class="py-2 pr-4 text-sm text-gray-600">{{ formatDateTime(rec.start_at) }}</td>
              <td class="py-2 pr-4 text-sm text-gray-600">{{ formatDateTime(rec.end_at) }}</td>
              <td class="py-2 pr-4 text-sm font-medium text-gray-900">{{ rec.hours }}h</td>
              <td class="py-2 pr-4">
                <span :class="statusBadgeClass(rec.status)" class="rounded-full px-2 py-0.5 text-xs font-medium">
                  {{ formatStatus(rec.status) }}
                </span>
              </td>
              <td class="py-2">
                <button
                  v-if="rec.status === 'approved'"
                  @click="markPaid(rec.id)"
                  :disabled="processingId === rec.id"
                  class="rounded bg-blue-600 px-3 py-1 text-xs font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                >
                  {{ processingId === rec.id ? '...' : 'Mark Paid' }}
                </button>
                <span v-else class="text-xs text-gray-400">—</span>
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

interface OvertimeRecord {
  id: number
  date: string
  start_at: string | null
  end_at: string | null
  hours: number
  overtime_type: string
  reason: string | null
  status: string
  employee?: { full_name: string; employee_id: string }
}

const pending = ref<OvertimeRecord[]>([])
const approved = ref<OvertimeRecord[]>([])
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
    const res = await fetch('/api/timekeeping/overtime/pending', {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
    if (!res.ok) { throw new Error('Failed to load') }
    const json = await res.json()
    pending.value = json.data ?? []
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'An error occurred'
  } finally {
    loading.value = false
  }
}

const loadApproved = async () => {
  historyLoading.value = true
  try {
    const res = await fetch('/api/timekeeping/overtime/history?status=approved&per_page=20', {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
    if (!res.ok) { return }
    const json = await res.json()
    approved.value = json.data ?? []
  } finally {
    historyLoading.value = false
  }
}

const approve = async (id: number) => {
  processingId.value = id
  try {
    await apiPost(`/api/timekeeping/overtime/${id}/approve`)
    await Promise.all([load(), loadApproved()])
  } catch { /* silently */ } finally {
    processingId.value = null
  }
}

const reject = async (id: number) => {
  if (!rejectionReason.value.trim()) { return }
  processingId.value = id
  try {
    await apiPost(`/api/timekeeping/overtime/${id}/reject`, { rejection_reason: rejectionReason.value })
    rejectingId.value = null
    rejectionReason.value = ''
    await Promise.all([load(), loadApproved()])
  } catch { /* silently */ } finally {
    processingId.value = null
  }
}

const markPaid = async (id: number) => {
  processingId.value = id
  try {
    await apiPost(`/api/timekeeping/overtime/${id}/mark-paid`)
    await loadApproved()
  } catch { /* silently */ } finally {
    processingId.value = null
  }
}

const formatDate = (date: string) =>
  new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })

const formatDateTime = (value: string | null) =>
  value ? new Date(value).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—'

const formatStatus = (status: string) => {
  const map: Record<string, string> = { pending: 'Pending', approved: 'Approved', rejected: 'Rejected', paid: 'Paid' }
  return map[status] ?? status
}

const statusBadgeClass = (status: string) => {
  switch (status) {
    case 'approved': return 'bg-green-100 text-green-700'
    case 'pending': return 'bg-amber-100 text-amber-700'
    case 'rejected': return 'bg-red-100 text-red-700'
    case 'paid': return 'bg-blue-100 text-blue-700'
    default: return 'bg-gray-100 text-gray-700'
  }
}

onMounted(() => Promise.all([load(), loadApproved()]))
</script>
