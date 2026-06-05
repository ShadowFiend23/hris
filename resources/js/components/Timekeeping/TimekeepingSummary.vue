<template>
  <!-- Admin: company-wide stats -->
  <div v-if="isAdmin" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-gray-600 text-sm font-medium">Total Present Today</p>
          <p class="text-3xl font-bold text-gray-900 mt-2">
            <span v-if="loading" class="block w-16 h-8 bg-gray-200 rounded animate-pulse" />
            <template v-else>{{ adminStats.totalPresent }}</template>
          </p>
        </div>
        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
          <CheckCircle2 class="w-6 h-6 text-green-600" />
        </div>
      </div>
      <p class="text-xs text-gray-500 mt-4">employees clocked in</p>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-gray-600 text-sm font-medium">Total Absent</p>
          <p class="text-3xl font-bold text-red-600 mt-2">
            <span v-if="loading" class="block w-16 h-8 bg-gray-200 rounded animate-pulse" />
            <template v-else>{{ adminStats.totalAbsent }}</template>
          </p>
        </div>
        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
          <XCircle class="w-6 h-6 text-red-600" />
        </div>
      </div>
      <p class="text-xs text-gray-500 mt-4">unexcused absences today</p>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-gray-600 text-sm font-medium">Pending Leave Approvals</p>
          <p class="text-3xl font-bold text-amber-600 mt-2">
            <span v-if="loading" class="block w-16 h-8 bg-gray-200 rounded animate-pulse" />
            <template v-else>{{ adminStats.pendingLeave }}</template>
          </p>
        </div>
        <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
          <Calendar class="w-6 h-6 text-amber-600" />
        </div>
      </div>
      <p class="text-xs text-gray-500 mt-4">requests awaiting review</p>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-gray-600 text-sm font-medium">On Leave Today</p>
          <p class="text-3xl font-bold text-purple-600 mt-2">
            <span v-if="loading" class="block w-16 h-8 bg-gray-200 rounded animate-pulse" />
            <template v-else>{{ adminStats.onLeaveToday }}</template>
          </p>
        </div>
        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
          <TrendingUp class="w-6 h-6 text-purple-600" />
        </div>
      </div>
      <p class="text-xs text-gray-500 mt-4">approved leave today</p>
    </div>
  </div>

  <!-- Employee / Manager: personal stats -->
  <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-gray-600 text-sm font-medium">Attendance Today</p>
          <p class="text-3xl font-bold text-gray-900 mt-2">
            <span v-if="loading" class="block w-16 h-8 bg-gray-200 rounded animate-pulse" />
            <template v-else>{{ companyAttendancePct != null ? `${companyAttendancePct}%` : '—' }}</template>
          </p>
        </div>
        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
          <CheckCircle2 class="w-6 h-6 text-green-600" />
        </div>
      </div>
      <p class="text-xs text-gray-500 mt-4">Company-wide today</p>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-gray-600 text-sm font-medium">Hours This Week</p>
          <p class="text-3xl font-bold text-gray-900 mt-2">
            <span v-if="loading" class="block w-16 h-8 bg-gray-200 rounded animate-pulse" />
            <template v-else>{{ hoursThisWeek != null ? Number(hoursThisWeek).toFixed(2) : '—' }}</template>
          </p>
        </div>
        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
          <Clock class="w-6 h-6 text-blue-600" />
        </div>
      </div>
      <p class="text-xs text-gray-500 mt-4">out of 40 hours</p>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-gray-600 text-sm font-medium">Leave Balance</p>
          <p class="text-3xl font-bold text-gray-900 mt-2">
            <span v-if="loading" class="block w-16 h-8 bg-gray-200 rounded animate-pulse" />
            <template v-else>{{ leaveBalance }}</template>
          </p>
        </div>
        <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
          <Calendar class="w-6 h-6 text-amber-600" />
        </div>
      </div>
      <p class="text-xs text-gray-500 mt-4">days remaining</p>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-gray-600 text-sm font-medium">Overtime Hours</p>
          <p class="text-3xl font-bold text-gray-900 mt-2">
            <span v-if="loading" class="block w-16 h-8 bg-gray-200 rounded animate-pulse" />
            <template v-else>{{ overtimeHoursThisMonth != null ? overtimeHoursThisMonth : '—' }}</template>
          </p>
        </div>
        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
          <TrendingUp class="w-6 h-6 text-purple-600" />
        </div>
      </div>
      <p class="text-xs text-gray-500 mt-4">this month</p>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-gray-600 text-sm font-medium">Current Status</p>
          <p class="text-lg font-bold text-gray-900 mt-2" :class="statusColor">
            <span v-if="loading" class="block w-24 h-6 bg-gray-200 rounded animate-pulse" />
            <template v-else>{{ currentStatus }}</template>
          </p>
        </div>
        <div class="w-12 h-12 rounded-lg flex items-center justify-center" :class="statusBgColor">
          <Clock class="w-6 h-6" :class="statusIconColor" />
        </div>
      </div>
      <p class="text-xs text-gray-500 mt-4">clock-in: {{ loading ? '...' : clockInTime }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Calendar, CheckCircle2, Clock, TrendingUp, XCircle } from 'lucide-vue-next'
import { computed, onMounted, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useTimekeeping } from '@/composables/useTimekeeping'

const page = usePage()
const isAdmin = computed(() => (page.props.auth as any)?.isAdmin === true)

const {
  fetchTodayAttendance,
  todayAttendance,
  fetchLeaveBalance,
  leaveBalances,
  fetchAttendanceSummary,
  fetchOvertimeSummary,
} = useTimekeeping()

const adminStats = ref({ totalPresent: 0, totalAbsent: 0, pendingLeave: 0, onLeaveToday: 0 })

const loading = ref(true)
const companyAttendancePct = ref<number | null>(null)
const hoursThisWeek = ref<number | null>(null)
const overtimeHoursThisMonth = ref<number | null>(null)

const leaveBalance = computed(() =>
  leaveBalances.value
    .filter((b) => b.leave_type?.is_paid !== false)
    .reduce((sum, b) => sum + parseFloat(String(b.remaining_days ?? 0)), 0),
)

const clockInTime = computed(() => {
  if (!todayAttendance.value?.clock_in) return '—'
  const date = new Date(todayAttendance.value.clock_in)
  return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
})

const currentStatus = computed(() => {
  const status = todayAttendance.value?.status
  if (!status) return 'No Record'
  const map: Record<string, string> = {
    present: 'Present',
    late: 'Late',
    absent: 'Absent',
    half_day: 'Half Day',
    on_leave: 'On Leave',
  }
  return map[status] ?? status
})

const statusColor = computed(() => {
  switch (todayAttendance.value?.status) {
    case 'present': return 'text-green-600'
    case 'late': return 'text-amber-600'
    case 'absent': return 'text-red-600'
    case 'on_leave': return 'text-purple-600'
    default: return 'text-gray-600'
  }
})

const statusBgColor = computed(() => {
  switch (todayAttendance.value?.status) {
    case 'present': return 'bg-green-100'
    case 'late': return 'bg-amber-100'
    case 'absent': return 'bg-red-100'
    case 'on_leave': return 'bg-purple-100'
    default: return 'bg-gray-100'
  }
})

const statusIconColor = computed(() => {
  switch (todayAttendance.value?.status) {
    case 'present': return 'text-green-600'
    case 'late': return 'text-amber-600'
    case 'absent': return 'text-red-600'
    case 'on_leave': return 'text-purple-600'
    default: return 'text-gray-600'
  }
})

const jsonHeaders = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
const fetchJson = (url: string) => fetch(url, { headers: jsonHeaders, credentials: 'same-origin' })

onMounted(async () => {
  loading.value = true
  try {
    if (isAdmin.value) {
      const [companyRes, pendingLeaveRes] = await Promise.all([
        fetchJson('/api/timekeeping/attendance/company'),
        fetchJson('/api/timekeeping/leave/pending'),
      ])

      if (companyRes.ok) {
        const data = await companyRes.json()
        const summary = data.summary ?? {}
        adminStats.value.totalPresent = (summary.clocked_in ?? 0) + (summary.clocked_out ?? 0)
        adminStats.value.totalAbsent = summary.absent ?? 0
        adminStats.value.onLeaveToday = summary.on_leave ?? 0
      }

      if (pendingLeaveRes.ok) {
        const data = await pendingLeaveRes.json()
        adminStats.value.pendingLeave = Array.isArray(data.data) ? data.data.length : 0
      }
    } else {
      const now = new Date()
      const todayStr = now.toISOString().split('T')[0]

      const dayOfWeek = now.getDay()
      const daysFromMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1
      const weekStart = new Date(now)
      weekStart.setDate(now.getDate() - daysFromMonday)
      const weekStartStr = weekStart.toISOString().split('T')[0]

      const monthStart = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0]

      await Promise.all([
        fetchTodayAttendance(),
        fetchLeaveBalance(),
      ])

      const [companyRes, attendanceSummary, overtimeSummary] = await Promise.all([
        fetchJson('/api/timekeeping/attendance/company'),
        fetchAttendanceSummary(weekStartStr, todayStr).catch(() => null),
        fetchOvertimeSummary(monthStart, todayStr).catch(() => null),
      ])

      if (companyRes.ok) {
        const data = await companyRes.json()
        const total = data.summary?.total ?? 0
        const present = (data.summary?.clocked_in ?? 0) + (data.summary?.clocked_out ?? 0)
        companyAttendancePct.value = total > 0 ? Math.round((present / total) * 100) : 0
      }

      if (attendanceSummary?.data) {
        hoursThisWeek.value = attendanceSummary.data.total_hours ?? 0
      }

      if (overtimeSummary?.data) {
        overtimeHoursThisMonth.value = overtimeSummary.data.total_hours ?? 0
      }
    }
  } catch (e) {
    console.error('Failed to load timekeeping summary:', e)
  } finally {
    loading.value = false
  }
})
</script>
