<template>
  <div class="space-y-6">
    <!-- Report Filters -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Generate Reports</h3>

      <div :class="['grid grid-cols-1 gap-4 mb-4', isAdmin ? 'md:grid-cols-5' : 'md:grid-cols-4']">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
          <select v-model="reportFilters.type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="attendance">Attendance Report</option>
            <option value="overtime">Overtime Report</option>
            <option value="leave">Leave Report</option>
            <option value="summary">Summary Report</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Period</label>
          <select v-model="reportFilters.period" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
            <option value="yearly">Yearly</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
          <input v-model="reportFilters.startDate" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
          <input v-model="reportFilters.endDate" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
        </div>

        <!-- Employee search — admin only -->
        <div v-if="isAdmin" class="relative" ref="autocompleteWrap">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Employee <span class="font-normal text-gray-400">(optional)</span>
          </label>
          <div class="relative">
            <input
              v-model="employeeQuery"
              type="text"
              placeholder="Name or ID…"
              autocomplete="off"
              class="w-full px-4 py-2 pr-8 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              @input="onEmployeeInput"
              @focus="showSuggestions = suggestions.length > 0"
              @keydown.escape="showSuggestions = false"
              @keydown.down.prevent="highlightNext"
              @keydown.up.prevent="highlightPrev"
              @keydown.enter.prevent="selectHighlighted"
            />
            <button v-if="selectedEmployee" @click="clearEmployee" type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
              <X :size="14" />
            </button>
            <Search v-else :size="14" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
          </div>

          <div v-if="suggestionsLoading" class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-400 shadow-lg">
            Searching…
          </div>
          <ul
            v-else-if="showSuggestions && suggestions.length > 0"
            class="absolute z-20 mt-1 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg"
            style="max-height: 220px"
          >
            <li
              v-for="(emp, idx) in suggestions"
              :key="emp.id"
              @mousedown.prevent="selectEmployee(emp)"
              :class="['cursor-pointer px-3 py-2 text-sm', idx === highlightedIndex ? 'bg-blue-50 text-blue-700' : 'text-gray-800 hover:bg-gray-50']"
            >
              <span class="font-medium">{{ emp.last_name }},</span> {{ emp.first_name }}
              <span class="ml-1 text-xs text-gray-400">{{ emp.employee_id }}</span>
            </li>
          </ul>
          <div
            v-else-if="showSuggestions && employeeQuery.length >= 2"
            class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-500 shadow-lg"
          >
            No employees found
          </div>
        </div>
      </div>

      <div class="flex gap-2">
        <button
          @click="generateReport"
          :disabled="isLoading"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center gap-2"
        >
          <Loader2 v-if="isLoading" class="animate-spin" :size="16" />
          Generate Report
        </button>
        <button @click="downloadReport" class="px-6 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors flex items-center gap-2">
          <Download :size="18" />
          Download
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="space-y-4">
        <div v-for="i in 5" :key="i" class="h-12 bg-gray-100 rounded animate-pulse" />
      </div>
    </div>

    <!-- Attendance Report -->
    <div v-else-if="activeReport === 'attendance'" class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Attendance Report</h3>

      <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-blue-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Days Present</p>
          <p class="text-2xl font-bold text-blue-600">{{ attendanceSummary.daysPresent }}</p>
        </div>
        <div class="bg-amber-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Days Late</p>
          <p class="text-2xl font-bold text-amber-600">{{ attendanceSummary.daysLate }}</p>
        </div>
        <div class="bg-red-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Days Absent</p>
          <p class="text-2xl font-bold text-red-600">{{ attendanceSummary.daysAbsent }}</p>
        </div>
        <div class="bg-green-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Total Hours</p>
          <p class="text-2xl font-bold text-green-600">{{ Number(attendanceSummary.totalHours || 0).toFixed(1) }}h</p>
        </div>
        <div class="bg-purple-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Attendance %</p>
          <p class="text-2xl font-bold text-purple-600">{{ Number(attendanceSummary.attendanceRate || 0).toFixed(1) }}%</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="text-left py-3 px-4 font-medium text-gray-900">Date</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Clock In</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Clock Out</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Hours</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="attendanceData.length === 0">
              <td colspan="5" class="py-8 text-center text-gray-500">No attendance records found</td>
            </tr>
            <tr v-for="record in attendanceData" :key="record.id" class="border-b border-gray-200 hover:bg-gray-50">
              <td class="py-3 px-4 text-gray-900">{{ formatDate(record.date) }}</td>
              <td class="py-3 px-4 text-gray-600">{{ formatTime(record.clock_in) }}</td>
              <td class="py-3 px-4 text-gray-600">{{ formatTime(record.clock_out) }}</td>
              <td class="py-3 px-4 text-gray-900">{{ Number(record.total_hours || 0).toFixed(1) }}h</td>
              <td class="py-3 px-4">
                <span :class="['inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium', getStatusClass(record.status)]">
                  {{ formatStatus(record.status) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Overtime Report -->
    <div v-else-if="activeReport === 'overtime'" class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Overtime Report</h3>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Total Overtime</p>
          <p class="text-2xl font-bold text-blue-600">{{ overtimeSummary.totalHours }}h</p>
        </div>
        <div class="bg-green-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Approved</p>
          <p class="text-2xl font-bold text-green-600">{{ overtimeSummary.approvedHours }}h</p>
        </div>
        <div class="bg-amber-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Pending</p>
          <p class="text-2xl font-bold text-amber-600">{{ overtimeSummary.pendingHours }}h</p>
        </div>
        <div class="bg-purple-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Est. Compensation</p>
          <p class="text-2xl font-bold text-purple-600">${{ overtimeSummary.estimatedCompensation }}</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="text-left py-3 px-4 font-medium text-gray-900">Date</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Hours</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Type</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Reason</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="overtimeData.length === 0">
              <td colspan="5" class="py-8 text-center text-gray-500">No overtime records found</td>
            </tr>
            <tr v-for="record in overtimeData" :key="record.id" class="border-b border-gray-200 hover:bg-gray-50">
              <td class="py-3 px-4 text-gray-900">{{ formatDate(record.date) }}</td>
              <td class="py-3 px-4 text-gray-900 font-medium">{{ record.hours }}h</td>
              <td class="py-3 px-4 text-gray-600 capitalize">{{ record.overtime_type }}</td>
              <td class="py-3 px-4 text-gray-600">{{ record.reason || '-' }}</td>
              <td class="py-3 px-4">
                <span :class="['inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium', getStatusClass(record.status)]">
                  {{ formatStatus(record.status) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Leave Report -->
    <div v-else-if="activeReport === 'leave'" class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Leave Report</h3>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Total Allocated</p>
          <p class="text-2xl font-bold text-blue-600">{{ leaveSummary.totalAllocated }}</p>
          <p class="text-xs text-gray-500 mt-1">days</p>
        </div>
        <div class="bg-orange-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Used</p>
          <p class="text-2xl font-bold text-orange-600">{{ leaveSummary.totalUsed }}</p>
          <p class="text-xs text-gray-500 mt-1">days</p>
        </div>
        <div class="bg-green-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Remaining</p>
          <p class="text-2xl font-bold text-green-600">{{ leaveSummary.totalRemaining }}</p>
          <p class="text-xs text-gray-500 mt-1">days</p>
        </div>
        <div class="bg-purple-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Approval Rate</p>
          <p class="text-2xl font-bold text-purple-600">{{ leaveSummary.approvalRate }}%</p>
          <p class="text-xs text-gray-500 mt-1">approved</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="text-left py-3 px-4 font-medium text-gray-900">Type</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Allocated</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Used</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Remaining</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="leaveData.length === 0">
              <td colspan="4" class="py-8 text-center text-gray-500">No leave balance data found</td>
            </tr>
            <tr v-for="leave in leaveData" :key="leave.id" class="border-b border-gray-200 hover:bg-gray-50">
              <td class="py-3 px-4 text-gray-900 font-medium">{{ leave.leave_type?.name || 'Leave' }}</td>
              <td class="py-3 px-4 text-gray-600">{{ leave.total_days }}</td>
              <td class="py-3 px-4 text-gray-600">{{ leave.used_days }}</td>
              <td class="py-3 px-4 text-gray-900">{{ leave.remaining_days }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Daily Time Record (DTR) -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">Daily Time Record (DTR)</h3>
          <p class="text-sm text-gray-500 mt-1">Civil Service Form No. 48 — print or download per employee per month</p>
        </div>
        <Link
          href="/timekeeping/dtr"
          class="flex items-center gap-2 px-5 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors"
        >
          <FileText :size="18" />
          Open DTR
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import { Download, FileText, Loader2, Search, X } from 'lucide-vue-next'
import { useTimekeeping, type AttendanceRecord, type OvertimeRecord, type LeaveBalance } from '@/composables/useTimekeeping'

const page = usePage()
const isAdmin = (page.props.auth as any)?.isAdmin === true

// Employee autocomplete (admin only)
interface EmployeeSuggestion { id: number; employee_id: string; first_name: string; last_name: string }
const employeeQuery = ref('')
const selectedEmployee = ref<EmployeeSuggestion | null>(null)
const suggestions = ref<EmployeeSuggestion[]>([])
const showSuggestions = ref(false)
const suggestionsLoading = ref(false)
const highlightedIndex = ref(-1)
const autocompleteWrap = ref<HTMLElement | null>(null)
let debounceTimer: ReturnType<typeof setTimeout>

const onEmployeeInput = () => {
  selectedEmployee.value = null
  clearTimeout(debounceTimer)
  if (employeeQuery.value.length < 2) {
    suggestions.value = []
    showSuggestions.value = false
    return
  }
  suggestionsLoading.value = true
  showSuggestions.value = true
  debounceTimer = setTimeout(fetchSuggestions, 280)
}

const fetchSuggestions = async () => {
  try {
    const res = await fetch(`/api/core/employees?search=${encodeURIComponent(employeeQuery.value)}&is_active=1`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
    if (!res.ok) { return }
    const json = await res.json()
    const list: any[] = json.data ?? json
    suggestions.value = list
      .map((e: any) => ({ id: e.id, employee_id: e.employee_id, first_name: e.first_name, last_name: e.last_name }))
      .sort((a, b) => a.last_name.localeCompare(b.last_name))
      .slice(0, 8)
    highlightedIndex.value = -1
  } finally {
    suggestionsLoading.value = false
  }
}

const selectEmployee = (emp: EmployeeSuggestion) => {
  selectedEmployee.value = emp
  employeeQuery.value = `${emp.last_name}, ${emp.first_name}`
  showSuggestions.value = false
  suggestions.value = []
}

const clearEmployee = () => {
  selectedEmployee.value = null
  employeeQuery.value = ''
  suggestions.value = []
  showSuggestions.value = false
}

const highlightNext = () => { if (highlightedIndex.value < suggestions.value.length - 1) { highlightedIndex.value++ } }
const highlightPrev = () => { if (highlightedIndex.value > 0) { highlightedIndex.value-- } }
const selectHighlighted = () => {
  if (highlightedIndex.value >= 0 && suggestions.value[highlightedIndex.value]) {
    selectEmployee(suggestions.value[highlightedIndex.value])
  }
}

const {
  fetchAttendanceHistory,
  fetchAttendanceSummary,
  fetchOvertimeHistory,
  fetchLeaveBalance,
  fetchMySummary,
} = useTimekeeping()

const activeReport = ref('attendance')
const isLoading = ref(false)

const reportFilters = ref({
  type: 'attendance',
  period: 'monthly',
  startDate: getDefaultStartDate(),
  endDate: getDefaultEndDate()
})

// Report data
const attendanceData = ref<AttendanceRecord[]>([])
const attendanceSummary = ref({
  daysPresent: 0,
  daysLate: 0,
  daysAbsent: 0,
  totalHours: 0,
  attendanceRate: 0
})

const overtimeData = ref<OvertimeRecord[]>([])
const overtimeSummary = ref({
  totalHours: 0,
  approvedHours: 0,
  pendingHours: 0,
  estimatedCompensation: 0
})

const leaveData = ref<LeaveBalance[]>([])
const leaveSummary = ref({
  totalAllocated: 0,
  totalUsed: 0,
  totalRemaining: 0,
  approvalRate: 100
})

// Helper functions
function getDefaultStartDate(): string {
  const date = new Date()
  date.setMonth(date.getMonth() - 1)
  return date.toISOString().split('T')[0]
}

function getDefaultEndDate(): string {
  return new Date().toISOString().split('T')[0]
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const formatTime = (time: string | null): string => {
  if (!time) return '-'
  const date = new Date(time)
  return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

const formatStatus = (status: string): string => {
  const statusMap: Record<string, string> = {
    present: 'Present',
    late: 'Late',
    absent: 'Absent',
    approved: 'Approved',
    pending: 'Pending',
    rejected: 'Rejected',
  }
  return statusMap[status] || status
}

const getStatusClass = (status: string): string => {
  switch (status) {
    case 'present':
    case 'approved': return 'bg-green-100 text-green-700'
    case 'late':
    case 'pending': return 'bg-amber-100 text-amber-700'
    case 'absent':
    case 'rejected': return 'bg-red-100 text-red-700'
    default: return 'bg-gray-100 text-gray-700'
  }
}

const generateReport = async () => {
  isLoading.value = true
  activeReport.value = reportFilters.value.type

  try {
    const { startDate, endDate } = reportFilters.value

    if (reportFilters.value.type === 'attendance') {
      const [historyResponse, summaryResponse] = await Promise.all([
        fetchAttendanceHistory({ start_date: startDate, end_date: endDate, per_page: 50 }),
        fetchAttendanceSummary(startDate, endDate)
      ])
      attendanceData.value = historyResponse.data || []
      if (summaryResponse.data) {
        attendanceSummary.value = {
          daysPresent: summaryResponse.data.days_present || 0,
          daysLate: summaryResponse.data.days_late || 0,
          daysAbsent: summaryResponse.data.days_absent || 0,
          totalHours: summaryResponse.data.total_hours || 0,
          attendanceRate: summaryResponse.data.attendance_rate || 0
        }
      }
    } else if (reportFilters.value.type === 'overtime') {
      const response = await fetchOvertimeHistory({ start_date: startDate, end_date: endDate, per_page: 50 })
      overtimeData.value = response.data || []

      // Calculate summary from data
      const approved = overtimeData.value.filter(r => r.status === 'approved')
      const pending = overtimeData.value.filter(r => r.status === 'pending')
      overtimeSummary.value = {
        totalHours: overtimeData.value.reduce((sum, r) => sum + r.hours, 0),
        approvedHours: approved.reduce((sum, r) => sum + r.hours, 0),
        pendingHours: pending.reduce((sum, r) => sum + r.hours, 0),
        estimatedCompensation: approved.reduce((sum, r) => sum + (r.hours * 30), 0) // Estimate
      }
    } else if (reportFilters.value.type === 'leave') {
      await fetchLeaveBalance()
      // leaveData will be populated from composable state
    }
  } catch (e) {
    console.error('Failed to generate report:', e)
  } finally {
    isLoading.value = false
  }
}

const downloadReport = () => {
  console.log('Downloading report:', reportFilters.value)
  alert('Report download feature coming soon')
}

// Initialize with attendance report
onMounted(async () => {
  await generateReport()
  document.addEventListener('click', (e) => {
    if (autocompleteWrap.value && !autocompleteWrap.value.contains(e.target as Node)) {
      showSuggestions.value = false
    }
  })
})
</script>
