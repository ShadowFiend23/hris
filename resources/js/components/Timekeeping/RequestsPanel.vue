<template>
  <div class="space-y-6">
    <!-- Sub-tab selector -->
    <div class="flex items-center justify-between gap-4 flex-wrap">
      <div class="flex gap-2">
        <button
          v-for="rt in requestTypes"
          :key="rt.id"
          @click="switchType(rt.id)"
          :class="[
            'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors',
            activeType === rt.id
              ? 'bg-blue-600 text-white'
              : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700',
          ]"
        >
          <component :is="rt.icon" :size="16" />
          {{ rt.label }}
          <span
            v-if="rt.id === 'leave' && pendingLeave.length"
            class="ml-1 inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-bold"
            :class="activeType === 'leave' ? 'bg-white/30 text-white' : 'bg-blue-100 text-blue-700'"
          >{{ pendingLeave.length }}</span>
          <span
            v-if="rt.id === 'overtime' && pendingOvertime.length"
            class="ml-1 inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-bold"
            :class="activeType === 'overtime' ? 'bg-white/30 text-white' : 'bg-blue-100 text-blue-700'"
          >{{ pendingOvertime.length }}</span>
          <span
            v-if="rt.id === 'schedule_change' && pendingScheduleChange.length"
            class="ml-1 inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-bold"
            :class="activeType === 'schedule_change' ? 'bg-white/30 text-white' : 'bg-blue-100 text-blue-700'"
          >{{ pendingScheduleChange.length }}</span>
        </button>
      </div>

      <button @click="refresh" :disabled="loading" class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors disabled:opacity-50">
        <RefreshCw :size="14" :class="loading ? 'animate-spin' : ''" />
        Refresh
      </button>
    </div>

    <!-- ── LEAVE REQUESTS ──────────────────────────────────── -->
    <template v-if="activeType === 'leave'">
      <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Pending Leave Requests</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Requests from your team awaiting approval</p>
        </div>

        <div v-if="loading" class="p-6 space-y-3">
          <div v-for="i in 3" :key="i" class="h-16 bg-gray-100 dark:bg-gray-700 rounded animate-pulse" />
        </div>

        <div v-else-if="pendingLeave.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
          <CheckCircle2 :size="40" class="text-green-400 mb-3" />
          <p class="text-gray-600 dark:text-gray-400 font-medium">All caught up</p>
          <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">No pending leave requests</p>
        </div>

        <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
          <div v-for="req in pendingLeave" :key="req.id" class="px-6 py-4">
            <div class="flex items-start justify-between gap-4">
              <!-- Employee + details -->
              <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-medium text-gray-900 dark:text-gray-100">
                    {{ req.employee?.first_name }} {{ req.employee?.last_name }}
                  </span>
                  <span class="text-xs text-gray-500 dark:text-gray-400">{{ req.employee?.department?.name }}</span>
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                    {{ req.leave_type?.name ?? 'Leave' }}
                  </span>
                </div>
                <div class="mt-1 flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400 flex-wrap">
                  <span>{{ formatDate(req.start_date) }} – {{ formatDate(req.end_date) }}</span>
                  <span class="font-medium text-gray-800 dark:text-gray-200">{{ req.total_days }}d</span>
                  <span v-if="req.reason" class="text-gray-500 dark:text-gray-400 truncate max-w-xs italic">"{{ req.reason }}"</span>
                </div>
              </div>

              <!-- Action buttons -->
              <div v-if="rejectingId !== req.id" class="flex items-center gap-2 shrink-0">
                <button
                  @click="handleApproveLeave(req.id)"
                  :disabled="processingId === req.id"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-green-600 text-white hover:bg-green-700 transition-colors disabled:opacity-50"
                >
                  <Check :size="14" />
                  Approve
                </button>
                <button
                  @click="startReject(req.id)"
                  :disabled="processingId === req.id"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors disabled:opacity-50"
                >
                  <X :size="14" />
                  Reject
                </button>
              </div>
            </div>

            <!-- Inline rejection form -->
            <div v-if="rejectingId === req.id" class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
              <label class="block text-sm font-medium text-red-700 dark:text-red-400 mb-1.5">Reason for rejection <span class="text-red-500">*</span></label>
              <textarea
                v-model="rejectionReason"
                rows="2"
                class="w-full px-3 py-2 text-sm border border-red-300 dark:border-red-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500 focus:outline-none"
                placeholder="Provide a reason…"
                autofocus
              />
              <div class="flex gap-2 mt-2">
                <button
                  @click="handleRejectLeave(req.id)"
                  :disabled="!rejectionReason.trim() || processingId === req.id"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-red-600 text-white hover:bg-red-700 transition-colors disabled:opacity-50"
                >
                  <Loader2 v-if="processingId === req.id" :size="14" class="animate-spin" />
                  Confirm Rejection
                </button>
                <button @click="cancelReject" class="px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                  Cancel
                </button>
              </div>
            </div>

            <!-- Feedback -->
            <p v-if="feedbackId === req.id" class="mt-2 text-sm" :class="feedbackError ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
              {{ feedbackMsg }}
            </p>
          </div>
        </div>
      </div>
    </template>

    <!-- ── OVERTIME REQUESTS ───────────────────────────────── -->
    <template v-else-if="activeType === 'overtime'">
      <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Pending Overtime Requests</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Requests from your team awaiting approval</p>
        </div>

        <div v-if="loading" class="p-6 space-y-3">
          <div v-for="i in 3" :key="i" class="h-16 bg-gray-100 dark:bg-gray-700 rounded animate-pulse" />
        </div>

        <div v-else-if="pendingOvertime.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
          <CheckCircle2 :size="40" class="text-green-400 mb-3" />
          <p class="text-gray-600 dark:text-gray-400 font-medium">All caught up</p>
          <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">No pending overtime requests</p>
        </div>

        <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
          <div v-for="rec in pendingOvertime" :key="rec.id" class="px-6 py-4">
            <div class="flex items-start justify-between gap-4">
              <!-- Employee + details -->
              <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-medium text-gray-900 dark:text-gray-100">
                    {{ rec.employee?.first_name }} {{ rec.employee?.last_name }}
                  </span>
                  <span class="text-xs text-gray-500 dark:text-gray-400">{{ rec.employee?.department?.name }}</span>
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 capitalize">
                    {{ rec.overtime_type }}
                  </span>
                </div>
                <div class="mt-1 flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400 flex-wrap">
                  <span>{{ formatDate(rec.date) }}</span>
                  <span class="font-medium text-gray-800 dark:text-gray-200">{{ rec.hours }}h</span>
                  <span v-if="rec.reason" class="text-gray-500 dark:text-gray-400 truncate max-w-xs italic">"{{ rec.reason }}"</span>
                </div>
              </div>

              <!-- Action buttons -->
              <div v-if="rejectingId !== rec.id" class="flex items-center gap-2 shrink-0">
                <button
                  @click="handleApproveOvertime(rec.id)"
                  :disabled="processingId === rec.id"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-green-600 text-white hover:bg-green-700 transition-colors disabled:opacity-50"
                >
                  <Check :size="14" />
                  Approve
                </button>
                <button
                  @click="startReject(rec.id)"
                  :disabled="processingId === rec.id"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors disabled:opacity-50"
                >
                  <X :size="14" />
                  Reject
                </button>
              </div>
            </div>

            <!-- Inline rejection form -->
            <div v-if="rejectingId === rec.id" class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
              <label class="block text-sm font-medium text-red-700 dark:text-red-400 mb-1.5">Reason for rejection <span class="text-gray-400 text-xs">(optional)</span></label>
              <textarea
                v-model="rejectionReason"
                rows="2"
                class="w-full px-3 py-2 text-sm border border-red-300 dark:border-red-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500 focus:outline-none"
                placeholder="Provide a reason…"
                autofocus
              />
              <div class="flex gap-2 mt-2">
                <button
                  @click="handleRejectOvertime(rec.id)"
                  :disabled="processingId === rec.id"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-red-600 text-white hover:bg-red-700 transition-colors disabled:opacity-50"
                >
                  <Loader2 v-if="processingId === rec.id" :size="14" class="animate-spin" />
                  Confirm Rejection
                </button>
                <button @click="cancelReject" class="px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                  Cancel
                </button>
              </div>
            </div>

            <!-- Feedback -->
            <p v-if="feedbackId === rec.id" class="mt-2 text-sm" :class="feedbackError ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
              {{ feedbackMsg }}
            </p>
          </div>
        </div>
      </div>
    </template>

    <!-- ── SCHEDULE CHANGE REQUESTS ──────────────────────── -->
    <template v-else-if="activeType === 'schedule_change'">
      <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Pending Schedule Change Requests</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">One-day shift change requests awaiting approval</p>
        </div>

        <div v-if="loading" class="p-6 space-y-3">
          <div v-for="i in 3" :key="i" class="h-16 bg-gray-100 dark:bg-gray-700 rounded animate-pulse" />
        </div>

        <div v-else-if="pendingScheduleChange.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
          <CheckCircle2 :size="40" class="text-green-400 mb-3" />
          <p class="text-gray-600 dark:text-gray-400 font-medium">All caught up</p>
          <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">No pending schedule change requests</p>
        </div>

        <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
          <div v-for="req in pendingScheduleChange" :key="req.id" class="px-6 py-4">
            <div class="flex items-start justify-between gap-4">
              <!-- Employee + details -->
              <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-medium text-gray-900 dark:text-gray-100">
                    {{ req.employee?.first_name }} {{ req.employee?.last_name }}
                  </span>
                  <span class="text-xs text-gray-500 dark:text-gray-400">{{ req.employee?.department?.name }}</span>
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">
                    Schedule Change
                  </span>
                </div>
                <div class="mt-1 flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400 flex-wrap">
                  <span>{{ formatDate(req.date) }}</span>
                  <span v-if="req.requested_shift_template" class="font-medium text-gray-800 dark:text-gray-200">
                    → {{ req.requested_shift_template.name }}
                    ({{ formatTime(req.requested_shift_template.start_time) }}–{{ formatTime(req.requested_shift_template.end_time) }})
                  </span>
                  <span v-if="req.reason" class="text-gray-500 dark:text-gray-400 truncate max-w-xs italic">"{{ req.reason }}"</span>
                </div>
              </div>

              <!-- Action buttons -->
              <div v-if="rejectingId !== req.id" class="flex items-center gap-2 shrink-0">
                <button
                  @click="handleApproveScheduleChange(req.id)"
                  :disabled="processingId === req.id"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-green-600 text-white hover:bg-green-700 transition-colors disabled:opacity-50"
                >
                  <Check :size="14" />
                  Approve
                </button>
                <button
                  @click="startReject(req.id)"
                  :disabled="processingId === req.id"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors disabled:opacity-50"
                >
                  <X :size="14" />
                  Reject
                </button>
              </div>
            </div>

            <!-- Inline rejection form -->
            <div v-if="rejectingId === req.id" class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
              <label class="block text-sm font-medium text-red-700 dark:text-red-400 mb-1.5">Reason for rejection <span class="text-red-500">*</span></label>
              <textarea
                v-model="rejectionReason"
                rows="2"
                class="w-full px-3 py-2 text-sm border border-red-300 dark:border-red-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500 focus:outline-none"
                placeholder="Provide a reason…"
                autofocus
              />
              <div class="flex gap-2 mt-2">
                <button
                  @click="handleRejectScheduleChange(req.id)"
                  :disabled="!rejectionReason.trim() || processingId === req.id"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-red-600 text-white hover:bg-red-700 transition-colors disabled:opacity-50"
                >
                  <Loader2 v-if="processingId === req.id" :size="14" class="animate-spin" />
                  Confirm Rejection
                </button>
                <button @click="cancelReject" class="px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                  Cancel
                </button>
              </div>
            </div>

            <!-- Feedback -->
            <p v-if="feedbackId === req.id" class="mt-2 text-sm" :class="feedbackError ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
              {{ feedbackMsg }}
            </p>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { CalendarDays, CalendarRange, Clock4, Check, X, RefreshCw, CheckCircle2, Loader2 } from 'lucide-vue-next'

// ── Types ──────────────────────────────────────────────────────────
interface Employee {
  first_name: string
  last_name: string
  department?: { name: string }
}

interface PendingLeave {
  id: number
  leave_type?: { name: string }
  employee?: Employee
  start_date: string
  end_date: string
  total_days: number
  reason?: string | null
}

interface PendingOvertime {
  id: number
  employee?: Employee
  date: string
  hours: number
  overtime_type: string
  reason?: string | null
}

interface PendingScheduleChange {
  id: number
  employee?: Employee
  date: string
  reason: string
  requested_shift_template?: { name: string; start_time: string; end_time: string }
  current_schedule?: { shift_template?: { name: string } }
}

// ── Config ─────────────────────────────────────────────────────────
const requestTypes = [
  { id: 'leave', label: 'Leave', icon: CalendarDays },
  { id: 'overtime', label: 'Overtime', icon: Clock4 },
  { id: 'schedule_change', label: 'Schedule Change', icon: CalendarRange },
]

const activeType = ref<'leave' | 'overtime' | 'schedule_change'>('leave')

// ── State ──────────────────────────────────────────────────────────
const pendingLeave = ref<PendingLeave[]>([])
const pendingOvertime = ref<PendingOvertime[]>([])
const pendingScheduleChange = ref<PendingScheduleChange[]>([])
const loading = ref(false)

const processingId = ref<number | null>(null)
const rejectingId = ref<number | null>(null)
const rejectionReason = ref('')

const feedbackId = ref<number | null>(null)
const feedbackMsg = ref('')
const feedbackError = ref(false)

// ── Helpers ────────────────────────────────────────────────────────
const getCsrfToken = () => {
  const cookie = document.cookie.split('; ').find((r) => r.startsWith('XSRF-TOKEN='))
  return cookie ? decodeURIComponent(cookie.split('=')[1]) : ''
}

const jsonHeaders = () => ({
  Accept: 'application/json',
  'Content-Type': 'application/json',
  'X-Requested-With': 'XMLHttpRequest',
  'X-XSRF-TOKEN': getCsrfToken(),
})

const formatDate = (date: string) =>
  new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })

const formatTime = (time: string) => (time ? time.substring(0, 5) : '')

const showFeedback = (id: number, msg: string, isError = false) => {
  feedbackId.value = id
  feedbackMsg.value = msg
  feedbackError.value = isError
  setTimeout(() => { if (feedbackId.value === id) feedbackId.value = null }, 3000)
}

// ── Data loading ───────────────────────────────────────────────────
const loadPendingLeave = async () => {
  const res = await fetch('/api/timekeeping/leave/pending', {
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'same-origin',
  })
  if (!res.ok) return
  const json = await res.json()
  pendingLeave.value = json.data ?? []
}

const loadPendingOvertime = async () => {
  const res = await fetch('/api/timekeeping/overtime/pending', {
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'same-origin',
  })
  if (!res.ok) return
  const json = await res.json()
  pendingOvertime.value = json.data ?? []
}

const loadPendingScheduleChange = async () => {
  const res = await fetch('/api/timekeeping/schedule-change/pending', {
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'same-origin',
  })
  if (!res.ok) return
  const json = await res.json()
  pendingScheduleChange.value = json.data ?? []
}

const refresh = async () => {
  loading.value = true
  try {
    await Promise.all([loadPendingLeave(), loadPendingOvertime(), loadPendingScheduleChange()])
  } finally {
    loading.value = false
  }
}

const switchType = (id: 'leave' | 'overtime' | 'schedule_change') => {
  activeType.value = id
  cancelReject()
}

// ── Reject inline ──────────────────────────────────────────────────
const startReject = (id: number) => {
  rejectingId.value = id
  rejectionReason.value = ''
}

const cancelReject = () => {
  rejectingId.value = null
  rejectionReason.value = ''
}

// ── Leave actions ──────────────────────────────────────────────────
const handleApproveLeave = async (id: number) => {
  processingId.value = id
  try {
    const res = await fetch(`/api/timekeeping/leave/request/${id}/approve`, {
      method: 'POST',
      headers: jsonHeaders(),
      credentials: 'same-origin',
    })
    if (!res.ok) throw new Error((await res.json()).error ?? 'Failed to approve')
    pendingLeave.value = pendingLeave.value.filter((r) => r.id !== id)
    showFeedback(id, 'Leave request approved.')
  } catch (e) {
    showFeedback(id, e instanceof Error ? e.message : 'Failed to approve.', true)
  } finally {
    processingId.value = null
  }
}

const handleRejectLeave = async (id: number) => {
  if (!rejectionReason.value.trim()) return
  processingId.value = id
  try {
    const res = await fetch(`/api/timekeeping/leave/request/${id}/reject`, {
      method: 'POST',
      headers: jsonHeaders(),
      credentials: 'same-origin',
      body: JSON.stringify({ rejection_reason: rejectionReason.value }),
    })
    if (!res.ok) throw new Error((await res.json()).error ?? 'Failed to reject')
    pendingLeave.value = pendingLeave.value.filter((r) => r.id !== id)
    cancelReject()
  } catch (e) {
    showFeedback(id, e instanceof Error ? e.message : 'Failed to reject.', true)
  } finally {
    processingId.value = null
  }
}

// ── Overtime actions ───────────────────────────────────────────────
const handleApproveOvertime = async (id: number) => {
  processingId.value = id
  try {
    const res = await fetch(`/api/timekeeping/overtime/${id}/approve`, {
      method: 'POST',
      headers: jsonHeaders(),
      credentials: 'same-origin',
    })
    if (!res.ok) throw new Error((await res.json()).error ?? 'Failed to approve')
    pendingOvertime.value = pendingOvertime.value.filter((r) => r.id !== id)
    showFeedback(id, 'Overtime request approved.')
  } catch (e) {
    showFeedback(id, e instanceof Error ? e.message : 'Failed to approve.', true)
  } finally {
    processingId.value = null
  }
}

const handleRejectOvertime = async (id: number) => {
  processingId.value = id
  try {
    const res = await fetch(`/api/timekeeping/overtime/${id}/reject`, {
      method: 'POST',
      headers: jsonHeaders(),
      credentials: 'same-origin',
      body: JSON.stringify({ rejection_reason: rejectionReason.value || null }),
    })
    if (!res.ok) throw new Error((await res.json()).error ?? 'Failed to reject')
    pendingOvertime.value = pendingOvertime.value.filter((r) => r.id !== id)
    cancelReject()
  } catch (e) {
    showFeedback(id, e instanceof Error ? e.message : 'Failed to reject.', true)
  } finally {
    processingId.value = null
  }
}

// ── Schedule Change actions ────────────────────────────────────────
const handleApproveScheduleChange = async (id: number) => {
  processingId.value = id
  try {
    const res = await fetch(`/api/timekeeping/schedule-change/${id}/approve`, {
      method: 'POST',
      headers: jsonHeaders(),
      credentials: 'same-origin',
    })
    if (!res.ok) throw new Error((await res.json()).error ?? 'Failed to approve')
    pendingScheduleChange.value = pendingScheduleChange.value.filter((r) => r.id !== id)
    showFeedback(id, 'Schedule change approved and schedule updated.')
  } catch (e) {
    showFeedback(id, e instanceof Error ? e.message : 'Failed to approve.', true)
  } finally {
    processingId.value = null
  }
}

const handleRejectScheduleChange = async (id: number) => {
  if (!rejectionReason.value.trim()) return
  processingId.value = id
  try {
    const res = await fetch(`/api/timekeeping/schedule-change/${id}/reject`, {
      method: 'POST',
      headers: jsonHeaders(),
      credentials: 'same-origin',
      body: JSON.stringify({ rejection_reason: rejectionReason.value }),
    })
    if (!res.ok) throw new Error((await res.json()).error ?? 'Failed to reject')
    pendingScheduleChange.value = pendingScheduleChange.value.filter((r) => r.id !== id)
    cancelReject()
  } catch (e) {
    showFeedback(id, e instanceof Error ? e.message : 'Failed to reject.', true)
  } finally {
    processingId.value = null
  }
}

onMounted(refresh)
</script>
