<template>
  <div class="space-y-6">
    <!-- Assign Shift Section -->
    <div class="rounded-lg border border-gray-200 bg-white p-6">
      <h3 class="mb-5 text-lg font-semibold text-gray-900">Assign Shift</h3>

      <form @submit.prevent="assignShift" class="space-y-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">Employee</label>
            <select
              v-model="assignForm.employee_id"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            >
              <option value="">Select employee</option>
              <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
            </select>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">Shift Template</label>
            <select
              v-model="assignForm.shift_template_id"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            >
              <option value="">Select shift</option>
              <option v-for="tmpl in templates" :key="tmpl.id" :value="tmpl.id">
                {{ tmpl.name }} ({{ tmpl.start_time }} – {{ tmpl.end_time }})
              </option>
            </select>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">Date</label>
            <input
              v-model="assignForm.date"
              type="date"
              required
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>

        <div v-if="assignError" class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ assignError }}</div>
        <div v-if="assignSuccess" class="rounded-lg bg-green-50 p-3 text-sm text-green-700">Shift assigned successfully!</div>

        <button
          type="submit"
          :disabled="assigning"
          class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
        >
          {{ assigning ? 'Assigning…' : 'Assign Shift' }}
        </button>
      </form>
    </div>

    <!-- Employee Schedule Viewer -->
    <div class="rounded-lg border border-gray-200 bg-white p-6">
      <div class="mb-5 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">View Employee Schedule</h3>
        <div class="flex items-center gap-2">
          <button @click="navigateWeek(-1)" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100">
            <ChevronLeft :size="18" />
          </button>
          <span class="text-sm font-medium text-gray-700">{{ weekRange }}</span>
          <button @click="navigateWeek(1)" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100">
            <ChevronRight :size="18" />
          </button>
        </div>
      </div>

      <div class="mb-4">
        <select
          v-model="viewEmployeeId"
          @change="loadSchedule"
          class="w-full max-w-xs rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">Select employee to view schedule</option>
          <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
        </select>
      </div>

      <div v-if="scheduleLoading" class="space-y-2">
        <div v-for="i in 7" :key="i" class="h-10 animate-pulse rounded bg-gray-100" />
      </div>

      <div v-else-if="!viewEmployeeId" class="py-10 text-center text-sm text-gray-400">
        Select an employee to view their schedule
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="py-3 pr-4 text-left text-sm font-medium text-gray-900">Day</th>
              <th class="py-3 pr-4 text-left text-sm font-medium text-gray-900">Shift</th>
              <th class="py-3 pr-4 text-left text-sm font-medium text-gray-900">Start</th>
              <th class="py-3 pr-4 text-left text-sm font-medium text-gray-900">End</th>
              <th class="py-3 pr-4 text-left text-sm font-medium text-gray-900">Hours</th>
              <th class="py-3 text-left text-sm font-medium text-gray-900">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="schedule.length === 0">
              <td colspan="6" class="py-8 text-center text-sm text-gray-500">No schedule for this week</td>
            </tr>
            <tr v-for="s in schedule" :key="s.id" class="border-b border-gray-100 hover:bg-gray-50">
              <td class="py-3 pr-4 font-medium text-gray-900">{{ formatDay(s.date) }}</td>
              <td class="py-3 pr-4 text-sm text-gray-600">{{ s.shift_template?.name ?? 'Custom' }}</td>
              <td class="py-3 pr-4 text-sm text-gray-600">{{ formatTime(s.start_time) }}</td>
              <td class="py-3 pr-4 text-sm text-gray-600">{{ formatTime(s.end_time) }}</td>
              <td class="py-3 pr-4 text-sm text-gray-900">{{ s.shift_template?.duration_hours ?? '—' }}h</td>
              <td class="py-3">
                <span :class="statusBadgeClass(s.status)" class="rounded-full px-2 py-0.5 text-xs font-medium">
                  {{ formatStatus(s.status) }}
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
import { computed, onMounted, ref, watch } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

interface Employee { id: number; name: string }
interface ShiftTemplate { id: number; name: string; start_time: string; end_time: string; duration_hours: number }
interface ScheduleEntry {
  id: number
  date: string
  start_time: string
  end_time: string
  status: string
  shift_template?: ShiftTemplate
}

const employees = ref<Employee[]>([])
const templates = ref<ShiftTemplate[]>([])
const schedule = ref<ScheduleEntry[]>([])
const scheduleLoading = ref(false)
const assigning = ref(false)
const assignError = ref('')
const assignSuccess = ref(false)
const viewEmployeeId = ref<number | ''>('')
const weekOffset = ref(0)

const assignForm = ref({ employee_id: '' as number | '', shift_template_id: '' as number | '', date: '' })

const weekStart = computed(() => {
  const d = new Date()
  const day = d.getDay()
  d.setDate(d.getDate() - day + weekOffset.value * 7)
  return d
})

const weekEnd = computed(() => {
  const d = new Date(weekStart.value)
  d.setDate(d.getDate() + 6)
  return d
})

const weekRange = computed(() => {
  const opts: Intl.DateTimeFormatOptions = { month: 'short', day: 'numeric' }
  return `${weekStart.value.toLocaleDateString('en-US', opts)} – ${weekEnd.value.toLocaleDateString('en-US', opts)}`
})

const navigateWeek = (dir: number) => {
  weekOffset.value += dir
  if (viewEmployeeId.value) { loadSchedule() }
}

const csrfToken = () =>
  decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '')

const loadEmployees = async () => {
  try {
    const res = await fetch('/api/timekeeping/attendance/company', {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
    if (!res.ok) { return }
    const json = await res.json()
    employees.value = (json.data ?? []).map((e: any) => ({ id: e.employee_id, name: e.name }))
  } catch { /* silently */ }
}

const loadTemplates = async () => {
  try {
    const res = await fetch('/api/timekeeping/shift/templates', {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
    if (!res.ok) { return }
    const json = await res.json()
    templates.value = json.data ?? json
  } catch { /* silently */ }
}

const loadSchedule = async () => {
  if (!viewEmployeeId.value) { return }
  scheduleLoading.value = true
  try {
    const start = weekStart.value.toISOString().slice(0, 10)
    const end = weekEnd.value.toISOString().slice(0, 10)
    const res = await fetch(`/api/timekeeping/shift/employee/${viewEmployeeId.value}?start_date=${start}&end_date=${end}`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
    if (!res.ok) { return }
    const json = await res.json()
    schedule.value = json.data ?? json
  } catch { /* silently */ } finally {
    scheduleLoading.value = false
  }
}

const assignShift = async () => {
  assigning.value = true
  assignError.value = ''
  assignSuccess.value = false
  try {
    const res = await fetch('/api/timekeeping/shift/assign', {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': csrfToken(),
      },
      credentials: 'same-origin',
      body: JSON.stringify(assignForm.value),
    })
    if (!res.ok) {
      const json = await res.json().catch(() => ({}))
      throw new Error((json as any).error ?? 'Failed to assign shift')
    }
    assignSuccess.value = true
    assignForm.value = { employee_id: '', shift_template_id: '', date: '' }
    if (viewEmployeeId.value) { await loadSchedule() }
    setTimeout(() => { assignSuccess.value = false }, 3000)
  } catch (e) {
    assignError.value = e instanceof Error ? e.message : 'An error occurred'
  } finally {
    assigning.value = false
  }
}

const formatDay = (date: string) =>
  new Date(date).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })

const formatTime = (time: string) => {
  if (!time) { return '—' }
  const [h, m] = time.split(':')
  const d = new Date()
  d.setHours(Number(h), Number(m))
  return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

const formatStatus = (status: string) => {
  const map: Record<string, string> = { scheduled: 'Scheduled', completed: 'Completed', absent: 'Absent', on_leave: 'On Leave' }
  return map[status] ?? status
}

const statusBadgeClass = (status: string) => {
  switch (status) {
    case 'scheduled': return 'bg-blue-100 text-blue-700'
    case 'completed': return 'bg-green-100 text-green-700'
    case 'absent': return 'bg-red-100 text-red-700'
    case 'on_leave': return 'bg-purple-100 text-purple-700'
    default: return 'bg-gray-100 text-gray-700'
  }
}

watch(viewEmployeeId, (val) => { if (val) { loadSchedule() } else { schedule.value = [] } })

onMounted(() => Promise.all([loadEmployees(), loadTemplates()]))
</script>
