<template>
  <Layout>
    <div class="w-full">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-1">Welcome, {{ userName }}</h1>
        <p class="text-gray-500">Here's your summary for today</p>
      </div>

      <!-- Top Row — 4 equal-height cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <!-- Profile Card -->
        <div v-if="employeeProfile" class="bg-white rounded-lg border border-gray-200 p-6 flex flex-col">
          <div class="flex items-center gap-4 mb-4">
            <img
              v-if="employeeProfile.profile_photo_url"
              :src="employeeProfile.profile_photo_url"
              class="w-16 h-16 rounded-full object-cover border-2 border-blue-200 shrink-0"
              alt="Profile photo"
            />
            <div
              v-else
              class="w-16 h-16 rounded-full bg-blue-600 flex items-center justify-center text-white text-xl font-bold shrink-0"
            >
              {{ profileInitials }}
            </div>
            <div class="min-w-0">
              <p class="text-base font-semibold text-gray-900 truncate">{{ employeeProfile.full_name }}</p>
              <p class="text-sm text-gray-500 truncate">{{ employeeProfile.position ?? '—' }}</p>
              <p class="text-xs text-gray-400 truncate">{{ employeeProfile.department ?? '—' }}</p>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3 text-sm border-t border-gray-100 pt-4 flex-1">
            <div>
              <p class="text-xs text-gray-400">Employee ID</p>
              <p class="font-medium text-gray-800">{{ employeeProfile.employee_id ?? '—' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400">Status</p>
              <span :class="statusBadge(employeeProfile.employment_status ?? '')" class="text-xs px-2 py-0.5 rounded-full font-medium">
                {{ ucFirst(employeeProfile.employment_status ?? '') }}
              </span>
            </div>
            <div class="col-span-2">
              <p class="text-xs text-gray-400">Date Hired</p>
              <p class="font-medium text-gray-800">{{ employeeProfile.date_hired ? formatDate(employeeProfile.date_hired) : '—' }}</p>
            </div>
          </div>
          <Link
            :href="`/employees/${employeeProfile.id}`"
            class="flex items-center justify-center gap-2 w-full mt-4 px-4 py-2 rounded-lg border border-blue-600 text-blue-600 text-sm font-medium hover:bg-blue-50 transition-colors"
          >
            <UserCircle :size="16" /> View Profile
          </Link>
        </div>

        <!-- Today's Status -->
        <div class="bg-white rounded-lg border border-gray-200 p-6 flex flex-col justify-between">
          <div>
            <div class="flex items-start justify-between mb-4">
              <p class="text-sm font-medium text-gray-500">Today's Status</p>
              <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="statusBg">
                <Clock class="w-5 h-5" :class="statusColor" />
              </div>
            </div>
            <p class="text-3xl font-bold" :class="statusColor">{{ currentStatus }}</p>
          </div>
          <p class="text-xs text-gray-400 mt-4">
            <template v-if="todayAttendance?.clock_in">
              Clocked in at {{ formatTime(todayAttendance.clock_in) }}
            </template>
            <template v-else>No clock-in recorded yet</template>
          </p>
        </div>

        <!-- Late This Month -->
        <div class="bg-amber-50 rounded-lg border border-amber-200 p-6 flex flex-col justify-between">
          <div>
            <div class="flex items-start justify-between mb-4">
              <p class="text-sm font-medium text-amber-600">Late</p>
              <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                <Clock class="w-5 h-5 text-amber-600" />
              </div>
            </div>
            <p class="text-4xl font-bold text-amber-800">{{ props.lateThisMonth }}</p>
          </div>
          <div class="mt-4">
            <p class="text-xs text-amber-500">this month</p>
            <p class="text-xs text-amber-400 mt-1">YTD: {{ props.lateYTD }}</p>
          </div>
        </div>

        <!-- Absences This Month -->
        <div class="bg-rose-50 rounded-lg border border-rose-200 p-6 flex flex-col justify-between">
          <div>
            <div class="flex items-start justify-between mb-4">
              <p class="text-sm font-medium text-rose-600">Absences</p>
              <div class="w-10 h-10 bg-rose-100 rounded-lg flex items-center justify-center">
                <UserX class="w-5 h-5 text-rose-600" />
              </div>
            </div>
            <p class="text-4xl font-bold text-rose-800">{{ props.absencesThisMonth }}</p>
          </div>
          <div class="mt-4">
            <p class="text-xs text-rose-500">this month</p>
            <p class="text-xs text-rose-400 mt-1">YTD: {{ props.absencesYTD }}</p>
          </div>
        </div>
      </div>

      <!-- Payroll Period (full width) -->
      <div v-if="latestPayroll" class="bg-white rounded-lg border border-gray-200 p-5 mb-8 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center shrink-0">
            <PhilippinePeso class="w-5 h-5 text-green-600" />
          </div>
          <div>
            <p class="text-xs text-gray-400">Latest Payroll Period</p>
            <p class="font-semibold text-gray-900">{{ formatDate(latestPayroll.start_date) }} – {{ formatDate(latestPayroll.end_date) }}</p>
          </div>
        </div>
        <span class="text-xs px-3 py-1 rounded-full bg-green-100 text-green-700 font-medium">Finalized</span>
      </div>

      <!-- Leave Balances -->
      <div v-if="paidLeaveBalances.length" class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
        <h2 class="text-base font-semibold text-gray-800 mb-4">Leave Balances</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <div
            v-for="(balance, i) in paidLeaveBalances"
            :key="balance.id"
            class="rounded-lg border p-4"
            :class="leaveColors[i % leaveColors.length].card"
          >
            <p class="text-xs font-medium mb-2 truncate" :class="leaveColors[i % leaveColors.length].label">
              {{ balance.leave_type?.name ?? 'Leave' }}
            </p>
            <p class="text-2xl font-bold" :class="leaveColors[i % leaveColors.length].value">
              {{ parseFloat(String(balance.remaining_days ?? 0)).toFixed(2) }}
            </p>
            <div class="flex items-center justify-between mt-2">
              <p class="text-xs" :class="leaveColors[i % leaveColors.length].sub">
                used: {{ parseFloat(String(balance.used_days ?? 0)).toFixed(2) }}
              </p>
              <div class="w-1.5 h-1.5 rounded-full" :class="leaveColors[i % leaveColors.length].dot"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Leave Requests -->
      <div v-if="recentLeaveRequests.length" class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-800 mb-4">My Recent Leave Requests</h2>
        <div class="space-y-2">
          <div
            v-for="req in recentLeaveRequests"
            :key="req.id"
            class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0"
          >
            <div class="text-sm">
              <span class="font-medium text-gray-800">{{ formatDate(req.start_date) }} – {{ formatDate(req.end_date) }}</span>
              <span class="text-gray-400 ml-2">({{ req.total_days }}d)</span>
            </div>
            <span :class="statusBadge(req.status)" class="text-xs px-2 py-1 rounded-full font-medium">
              {{ ucFirst(req.status) }}
            </span>
          </div>
        </div>
      </div>

      <!-- My Schedule Calendar -->
      <div>
        <h2 class="text-base font-semibold text-gray-800 mb-3">My Schedule</h2>
        <MiniCalendar />
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { Clock, PhilippinePeso, UserCircle, UserX } from 'lucide-vue-next'
import { computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import Layout from '@/components/Layout.vue'
import MiniCalendar from '@/components/Dashboard/MiniCalendar.vue'

interface AttendanceRecord {
  id: number
  clock_in: string | null
  clock_out: string | null
  status: string
}

interface LeaveBalance {
  id: number
  leave_type_id: number
  remaining_days: number
  used_days: number
  leave_type?: { name: string }
}

interface LeaveRequest {
  id: number
  status: string
  start_date: string
  end_date: string
  total_days: number
}

interface PayrollPeriod {
  id: number
  start_date: string
  end_date: string
}

interface EmployeeProfile {
  id: number
  employee_id: string | null
  full_name: string
  department: string | null
  position: string | null
  employment_status: string | null
  date_hired: string | null
  profile_photo_url: string | null
}

const props = defineProps<{
  todayAttendance: AttendanceRecord | null
  leaveBalances: LeaveBalance[]
  recentLeaveRequests: LeaveRequest[]
  latestPayroll: PayrollPeriod | null
  lateThisMonth: number
  lateYTD: number
  absencesThisMonth: number
  absencesYTD: number
  employeeProfile: EmployeeProfile | null
}>()

const page = usePage()
const userName = computed(() => {
  const name = (page.props.auth as any)?.user?.name ?? 'there'
  return name.split(' ')[0]
})

const profileInitials = computed(() => {
  const name = props.employeeProfile?.full_name ?? ''
  const parts = name.trim().split(' ').filter(Boolean)
  return parts.length >= 2
    ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
    : name.slice(0, 2).toUpperCase()
})

const paidLeaveBalances = computed(() =>
  props.leaveBalances.filter((b) => b.leave_type?.is_paid !== false),
)

const leaveColors = [
  { card: 'bg-blue-50 border-blue-200', label: 'text-blue-600', value: 'text-blue-800', sub: 'text-blue-400', dot: 'bg-blue-400' },
  { card: 'bg-green-50 border-green-200', label: 'text-green-600', value: 'text-green-800', sub: 'text-green-400', dot: 'bg-green-400' },
  { card: 'bg-amber-50 border-amber-200', label: 'text-amber-600', value: 'text-amber-800', sub: 'text-amber-400', dot: 'bg-amber-400' },
  { card: 'bg-purple-50 border-purple-200', label: 'text-purple-600', value: 'text-purple-800', sub: 'text-purple-400', dot: 'bg-purple-400' },
  { card: 'bg-rose-50 border-rose-200', label: 'text-rose-600', value: 'text-rose-800', sub: 'text-rose-400', dot: 'bg-rose-400' },
  { card: 'bg-teal-50 border-teal-200', label: 'text-teal-600', value: 'text-teal-800', sub: 'text-teal-400', dot: 'bg-teal-400' },
]

const currentStatus = computed(() => {
  const s = props.todayAttendance?.status
  if (!s) return 'Not Clocked In'
  const map: Record<string, string> = {
    present: 'Present',
    late: 'Late',
    absent: 'Absent',
    half_day: 'Half Day',
    on_leave: 'On Leave',
  }
  return map[s] ?? s
})

const statusColor = computed(() => {
  switch (props.todayAttendance?.status) {
    case 'present': return 'text-green-600'
    case 'late': return 'text-amber-600'
    case 'absent': return 'text-red-600'
    case 'on_leave': return 'text-purple-600'
    default: return 'text-gray-500'
  }
})

const statusBg = computed(() => {
  switch (props.todayAttendance?.status) {
    case 'present': return 'bg-green-100'
    case 'late': return 'bg-amber-100'
    case 'absent': return 'bg-red-100'
    case 'on_leave': return 'bg-purple-100'
    default: return 'bg-gray-100'
  }
})

const formatTime = (dt: string) =>
  new Date(dt).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })

const formatDate = (d: string) =>
  new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })

const ucFirst = (s: string) => s.charAt(0).toUpperCase() + s.slice(1)

const statusBadge = (status: string) => {
  const map: Record<string, string> = {
    approved: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-700',
    pending: 'bg-amber-100 text-amber-700',
    cancelled: 'bg-gray-100 text-gray-600',
  }
  return map[status] ?? 'bg-gray-100 text-gray-600'
}
</script>
