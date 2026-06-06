<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h3 class="text-lg font-semibold text-gray-900">Company Attendance</h3>
        <p class="text-sm text-gray-500 mt-0.5">{{ formatDate(selectedDate) }}</p>
      </div>
      <div class="flex items-center gap-2">
        <button @click="prevDay" class="rounded border border-gray-300 px-2 py-1 text-sm text-gray-600 hover:bg-gray-50" title="Previous day">&laquo;</button>
        <input
          v-model="selectedDate"
          type="date"
          :max="today"
          @change="load"
          class="rounded border border-gray-300 px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        <button @click="nextDay" :disabled="selectedDate >= today" class="rounded border border-gray-300 px-2 py-1 text-sm text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-300" title="Next day">&raquo;</button>
        <button @click="load" class="text-sm text-blue-600 hover:underline ml-1">Refresh</button>
      </div>
    </div>

    <!-- Summary Badges + Search -->
    <div v-if="!loading" class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap gap-3">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-700">
          <span class="h-2 w-2 rounded-full bg-green-500" />
          Present: {{ counts.present }}
        </span>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-sm font-medium text-amber-700">
          <span class="h-2 w-2 rounded-full bg-amber-500" />
          Late: {{ counts.late }}
        </span>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-700">
          <span class="h-2 w-2 rounded-full bg-red-500" />
          Absent: {{ counts.absent }}
        </span>
      </div>
      <input v-if="employees.length > 0" v-model="attendanceSearch" type="text" placeholder="Search by name, code, or department..." class="w-72 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 6" :key="i" class="h-14 animate-pulse rounded-lg bg-gray-100" />
    </div>

    <!-- Error -->
    <div v-else-if="error" class="rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ error }}</div>

    <!-- Table -->
    <div v-else class="overflow-x-auto rounded-lg border border-gray-200">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employee</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Department</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Clock In</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Clock Out</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Hours</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-if="employees.length === 0">
            <td colspan="7" class="py-12 text-center text-gray-500">No attendance records for this date.</td>
          </tr>
          <tr v-else-if="filteredEmployees.length === 0">
            <td colspan="7" class="py-12 text-center text-gray-500">No records match your search.</td>
          </tr>
          <tr v-for="emp in paginatedEmployees" :key="emp.employee_id" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <div class="text-sm font-medium text-gray-900">{{ emp.name }}</div>
              <div class="text-xs text-gray-500">{{ emp.employee_code }}</div>
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">{{ emp.department ?? '—' }}</td>
            <td class="px-4 py-3">
              <span :class="statusBadgeClass(emp.status)" class="rounded-full px-2 py-1 text-xs font-medium">
                {{ formatStatus(emp.status) }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ formatTime(emp.clock_in) }}</td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ formatTime(emp.clock_out) }}</td>
            <td class="px-4 py-3 text-sm text-gray-700">
              {{ emp.total_hours != null ? `${Number(emp.total_hours).toFixed(1)}h` : '—' }}
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <button
                  v-if="emp.attendance_record_id"
                  @click="openAdjust(emp)"
                  class="text-xs font-medium text-blue-600 hover:underline"
                >
                  Adjust
                </button>
                <button
                  v-if="!emp.attendance_record_id"
                  @click="markAbsent(emp)"
                  :disabled="markingAbsentId === emp.employee_id"
                  class="text-xs font-medium text-red-600 hover:underline disabled:opacity-50"
                >
                  {{ markingAbsentId === emp.employee_id ? 'Marking...' : 'Mark Absent' }}
                </button>
                <span v-if="emp.attendance_record_id && !emp.clock_in" class="text-xs text-gray-400">No record</span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="employees.length > 0" class="flex items-center border-t border-gray-200 bg-white px-6 py-4">
      <div class="flex w-1/3 items-center gap-2">
        <span class="text-sm text-gray-600">Per page:</span>
        <select v-model="attendancePerPage" @change="changeAttendancePage" class="rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option v-for="n in [5, 10, 25, 50]" :key="n" :value="n">{{ n }}</option>
        </select>
      </div>
      <div class="flex w-1/3 justify-center gap-2">
        <button @click="attendancePage--" :disabled="attendancePage <= 1" class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400" v-html="'&laquo;'" />
        <button v-for="p in attendanceTotalPages" :key="p" @click="attendancePage = p" :class="['rounded-lg px-3 py-1 text-sm font-medium transition-colors', attendancePage === p ? 'bg-blue-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50']">{{ p }}</button>
        <button @click="attendancePage++" :disabled="attendancePage >= attendanceTotalPages" class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400" v-html="'&raquo;'" />
      </div>
      <div class="flex w-1/3 justify-end">
        <p class="text-sm text-gray-600">{{ attendanceSearch ? `${filteredEmployees.length} of ${employees.length}` : employees.length }} records</p>
      </div>
    </div>

    <!-- Adjust Modal -->
    <AdjustAttendanceModal
      v-if="adjustTarget"
      :member="adjustTarget"
      @close="adjustTarget = null"
      @adjusted="onAdjusted"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import AdjustAttendanceModal from './AdjustAttendanceModal.vue'

interface AttendanceEmployee {
  employee_id: number
  employee_code: string
  name: string
  department: string | null
  position: string | null
  attendance_record_id: number | null
  clock_in: string | null
  clock_out: string | null
  total_hours: number | null
  status: string
  adjusted_by: number | null
  adjusted_at: string | null
  adjustment_reason: string | null
}

const employees = ref<AttendanceEmployee[]>([])
const loading = ref(true)
const error = ref('')
const adjustTarget = ref<AttendanceEmployee | null>(null)
const markingAbsentId = ref<number | null>(null)
const today = new Date().toISOString().slice(0, 10)
const selectedDate = ref(today)

const attendanceSearch = ref('')
const attendancePage = ref(1)
const attendancePerPage = ref(5)
const filteredEmployees = computed(() => {
  const q = attendanceSearch.value.toLowerCase()
  if (!q) { return employees.value }
  return employees.value.filter(e =>
    e.name.toLowerCase().includes(q) ||
    e.employee_code.toLowerCase().includes(q) ||
    (e.department ?? '').toLowerCase().includes(q),
  )
})
const attendanceTotalPages = computed(() => Math.max(1, Math.ceil(filteredEmployees.value.length / attendancePerPage.value)))
const paginatedEmployees = computed(() => {
  const start = (attendancePage.value - 1) * attendancePerPage.value
  return filteredEmployees.value.slice(start, start + attendancePerPage.value)
})
function changeAttendancePage(): void { attendancePage.value = 1 }
watch(attendanceSearch, () => { attendancePage.value = 1 })

const counts = computed(() => ({
  present: employees.value.filter((e) => ['present', 'late'].includes(e.status)).length,
  late: employees.value.filter((e) => e.status === 'late').length,
  absent: employees.value.filter((e) => e.status === 'absent').length,
}))

const prevDay = () => {
  const d = new Date(selectedDate.value)
  d.setDate(d.getDate() - 1)
  selectedDate.value = d.toISOString().slice(0, 10)
  load()
}

const nextDay = () => {
  if (selectedDate.value >= today) { return }
  const d = new Date(selectedDate.value)
  d.setDate(d.getDate() + 1)
  selectedDate.value = d.toISOString().slice(0, 10)
  load()
}

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const res = await fetch(`/api/timekeeping/attendance/company?date=${selectedDate.value}`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
    if (!res.ok) { throw new Error('Failed to load attendance') }
    const json = await res.json()
    employees.value = json.data ?? []
    attendancePage.value = 1
    attendanceSearch.value = ''
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'An error occurred'
  } finally {
    loading.value = false
  }
}

const csrfToken = () =>
  decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '')

const markAbsent = async (emp: AttendanceEmployee) => {
  markingAbsentId.value = emp.employee_id
  try {
    await fetch(`/api/timekeeping/attendance/employee/${emp.employee_id}/absent`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': csrfToken(),
      },
      credentials: 'same-origin',
      body: JSON.stringify({ date: selectedDate.value }),
    })
    await load()
  } catch {
    // silently refresh
  } finally {
    markingAbsentId.value = null
  }
}

const openAdjust = (emp: AttendanceEmployee) => {
  adjustTarget.value = emp
}

const onAdjusted = () => {
  adjustTarget.value = null
  load()
}

const formatTime = (time: string | null) => {
  if (!time) { return '—' }
  return new Date(time).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

const formatDate = (date: string) =>
  new Date(date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })

const formatStatus = (status: string) => {
  const map: Record<string, string> = { present: 'Present', late: 'Late', absent: 'Absent', on_leave: 'On Leave', holiday: 'Holiday' }
  return map[status] ?? status
}

const statusBadgeClass = (status: string) => {
  switch (status) {
    case 'present': return 'bg-green-100 text-green-700'
    case 'late': return 'bg-amber-100 text-amber-700'
    case 'absent': return 'bg-red-100 text-red-700'
    case 'on_leave': return 'bg-blue-100 text-blue-700'
    case 'holiday': return 'bg-purple-100 text-purple-700'
    default: return 'bg-gray-100 text-gray-700'
  }
}

onMounted(load)
</script>
