<template>
  <div class="space-y-6">
    <!-- Submit Request Form -->
    <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
      <h3 class="mb-6 text-lg font-semibold text-gray-900 dark:text-gray-100">Request Schedule Change</h3>

      <form class="space-y-4" @submit.prevent="handleSubmit">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
            <DatePicker v-model="form.date" :min-date="tomorrow" :required="true" />
            <p v-if="formErrors.date" class="mt-1 text-xs text-red-600">{{ formErrors.date }}</p>
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Requested Shift Template</label>
            <select
              v-model="form.requested_shift_template_id"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              required
            >
              <option value="">Select a shift template</option>
              <option v-for="template in shiftTemplates" :key="template.id" :value="template.id">
                {{ template.name }} ({{ formatTime(template.start_time) }} – {{ formatTime(template.end_time) }})
              </option>
            </select>
            <p v-if="formErrors.requested_shift_template_id" class="mt-1 text-xs text-red-600">{{ formErrors.requested_shift_template_id }}</p>
          </div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Reason</label>
          <textarea
            v-model="form.reason"
            rows="3"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            placeholder="Explain why you need to change your schedule for this day..."
            required
          />
          <p v-if="formErrors.reason" class="mt-1 text-xs text-red-600">{{ formErrors.reason }}</p>
        </div>

        <div v-if="submitError" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400">
          {{ submitError }}
        </div>

        <button
          type="submit"
          :disabled="isSubmitting"
          class="flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 disabled:opacity-50"
        >
          <Loader2 v-if="isSubmitting" class="animate-spin" :size="16" />
          Submit Request
        </button>
      </form>
    </div>

    <!-- My Requests -->
    <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
      <h3 class="mb-6 text-lg font-semibold text-gray-900 dark:text-gray-100">My Schedule Change Requests</h3>

      <div v-if="historyLoading" class="space-y-3">
        <div v-for="i in 3" :key="i" class="h-12 animate-pulse rounded-lg bg-gray-100 dark:bg-gray-700" />
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-200 dark:border-gray-700">
              <th class="py-3 px-4 text-left font-medium text-gray-900 dark:text-gray-100">Date</th>
              <th class="py-3 px-4 text-left font-medium text-gray-900 dark:text-gray-100">Requested Shift</th>
              <th class="py-3 px-4 text-left font-medium text-gray-900 dark:text-gray-100">Reason</th>
              <th class="py-3 px-4 text-left font-medium text-gray-900 dark:text-gray-100">Status</th>
              <th class="py-3 px-4 text-left font-medium text-gray-900 dark:text-gray-100">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="requests.length === 0">
              <td colspan="5" class="py-8 text-center text-gray-500 dark:text-gray-400">No schedule change requests yet.</td>
            </tr>
            <tr
              v-for="req in requests"
              :key="req.id"
              class="border-b border-gray-200 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/50"
            >
              <td class="py-3 px-4 font-medium text-gray-900 dark:text-gray-100">{{ formatDate(req.date) }}</td>
              <td class="py-3 px-4 text-gray-600 dark:text-gray-400">
                {{ req.requested_shift_template?.name ?? '—' }}
                <span v-if="req.requested_shift_template" class="ml-1 text-xs text-gray-400">
                  ({{ formatTime(req.requested_shift_template.start_time) }} – {{ formatTime(req.requested_shift_template.end_time) }})
                </span>
              </td>
              <td class="py-3 px-4 max-w-xs text-gray-600 dark:text-gray-400">
                <span class="line-clamp-2">{{ req.reason }}</span>
              </td>
              <td class="py-3 px-4">
                <span :class="statusClass(req.status)" class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm font-medium">
                  <span :class="statusDotClass(req.status)" class="h-2 w-2 rounded-full" />
                  {{ formatStatus(req.status) }}
                </span>
              </td>
              <td class="py-3 px-4">
                <button
                  v-if="req.status === 'pending'"
                  :disabled="cancellingId === req.id"
                  class="text-sm font-medium text-red-600 hover:text-red-700 disabled:opacity-50 dark:text-red-400"
                  @click="handleCancel(req.id)"
                >
                  {{ cancellingId === req.id ? 'Cancelling...' : 'Cancel' }}
                </button>
                <span v-else-if="req.status === 'rejected' && req.rejection_reason" class="text-xs text-gray-400 dark:text-gray-500" :title="req.rejection_reason">
                  Reason on file
                </span>
                <span v-else class="text-gray-400 text-sm">—</span>
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
import { Loader2 } from 'lucide-vue-next'
import { push } from 'notivue'
import DatePicker from '@/components/ui/DatePicker.vue'

interface ShiftTemplate {
  id: number
  name: string
  start_time: string
  end_time: string
}

interface ScheduleChangeRequest {
  id: number
  date: string
  reason: string
  status: string
  rejection_reason: string | null
  requested_shift_template: ShiftTemplate | null
}

const shiftTemplates = ref<ShiftTemplate[]>([])
const requests = ref<ScheduleChangeRequest[]>([])
const historyLoading = ref(false)
const isSubmitting = ref(false)
const submitError = ref<string | null>(null)
const cancellingId = ref<number | null>(null)

const tomorrow = new Date()
tomorrow.setDate(tomorrow.getDate() + 1)
const tomorrowStr = tomorrow.toISOString().slice(0, 10)

const form = ref({
  date: '',
  requested_shift_template_id: '' as number | string,
  reason: '',
})
const formErrors = ref<Record<string, string>>({})

const csrfToken = () =>
  decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '')

const headers = () => ({
  Accept: 'application/json',
  'Content-Type': 'application/json',
  'X-Requested-With': 'XMLHttpRequest',
  'X-XSRF-TOKEN': csrfToken(),
})

const formatTime = (time: string | null): string => {
  if (!time) { return '' }
  return time.substring(0, 5)
}

const formatDate = (date: string): string =>
  new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })

const formatStatus = (status: string): string => {
  const map: Record<string, string> = { pending: 'Pending', approved: 'Approved', rejected: 'Rejected', cancelled: 'Cancelled' }
  return map[status] ?? status
}

const statusClass = (status: string): string => {
  switch (status) {
    case 'approved': return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
    case 'pending': return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
    case 'rejected': return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
    default: return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
  }
}

const statusDotClass = (status: string): string => {
  switch (status) {
    case 'approved': return 'bg-green-600'
    case 'pending': return 'bg-amber-600'
    case 'rejected': return 'bg-red-600'
    default: return 'bg-gray-600'
  }
}

const loadTemplates = async () => {
  try {
    const res = await fetch('/api/timekeeping/schedule-change/templates', {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
    const json = await res.json()
    shiftTemplates.value = json.data ?? []
  } catch {
    // silently fail
  }
}

const loadHistory = async () => {
  historyLoading.value = true
  try {
    const res = await fetch('/api/timekeeping/schedule-change?per_page=20', {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
    const json = await res.json()
    requests.value = json.data ?? []
  } catch {
    requests.value = []
  } finally {
    historyLoading.value = false
  }
}

const handleSubmit = async () => {
  formErrors.value = {}
  submitError.value = null

  if (!form.value.date) {
    formErrors.value.date = 'Please select a date.'
    return
  }
  if (!form.value.requested_shift_template_id) {
    formErrors.value.requested_shift_template_id = 'Please select a shift template.'
    return
  }
  if (!form.value.reason.trim()) {
    formErrors.value.reason = 'Please provide a reason.'
    return
  }

  isSubmitting.value = true
  try {
    const res = await fetch('/api/timekeeping/schedule-change', {
      method: 'POST',
      headers: headers(),
      credentials: 'same-origin',
      body: JSON.stringify({
        date: form.value.date,
        requested_shift_template_id: Number(form.value.requested_shift_template_id),
        reason: form.value.reason,
      }),
    })
    const json = await res.json()
    if (!res.ok) {
      submitError.value = json.error ?? 'Failed to submit request.'
      if (json.errors) {
        formErrors.value = Object.fromEntries(
          Object.entries(json.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : String(v)]),
        )
      }
      return
    }
    push.success({ title: 'Request Submitted', message: 'Your schedule change request has been submitted.' })
    form.value = { date: '', requested_shift_template_id: '', reason: '' }
    await loadHistory()
  } catch {
    submitError.value = 'An unexpected error occurred.'
  } finally {
    isSubmitting.value = false
  }
}

const handleCancel = async (id: number) => {
  cancellingId.value = id
  try {
    const res = await fetch(`/api/timekeeping/schedule-change/${id}/cancel`, {
      method: 'POST',
      headers: headers(),
      credentials: 'same-origin',
    })
    if (res.ok) {
      push.success({ title: 'Cancelled', message: 'Schedule change request cancelled.' })
      await loadHistory()
    }
  } catch {
    // silently fail
  } finally {
    cancellingId.value = null
  }
}

onMounted(async () => {
  await Promise.all([loadTemplates(), loadHistory()])
})
</script>
