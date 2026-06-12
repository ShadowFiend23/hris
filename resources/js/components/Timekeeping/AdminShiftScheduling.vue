<template>
  <div class="space-y-6">
    <!-- Sub-tab buttons -->
    <div class="flex gap-2">
      <button
        type="button"
        class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors"
        :class="activeSubTab === 'change_schedule'
          ? 'bg-blue-600 text-white'
          : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'"
        @click="activeSubTab = 'change_schedule'"
      >
        <CalendarClock :size="16" />
        Change Schedule
      </button>
      <button
        type="button"
        class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors"
        :class="activeSubTab === 'shifts'
          ? 'bg-blue-600 text-white'
          : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'"
        @click="activeSubTab = 'shifts'"
      >
        <LayoutGrid :size="16" />
        Shifts
      </button>
    </div>

    <!-- Change Schedule sub-tab -->
    <template v-if="activeSubTab === 'change_schedule'">
      <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
        <h3 class="mb-5 text-lg font-semibold text-gray-900 dark:text-gray-100">Change Schedule</h3>

        <form @submit.prevent="assignShift" class="space-y-4">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <!-- Employee search autocomplete -->
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Employee</label>
              <div class="relative" ref="assignWrap">
                <input
                  v-model="assignQuery"
                  type="text"
                  placeholder="Name or ID…"
                  autocomplete="off"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                  @input="onAssignInput"
                  @focus="assignShowSuggestions = assignSuggestions.length > 0"
                  @keydown.escape="assignShowSuggestions = false"
                  @keydown.down.prevent="assignHighlightNext"
                  @keydown.up.prevent="assignHighlightPrev"
                  @keydown.enter.prevent="assignSelectHighlighted"
                />
                <button v-if="assignForm.employee_id" @click="clearAssignEmployee" type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                  <X :size="14" />
                </button>
                <Search v-else :size="14" class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-gray-400" />

                <div v-if="assignSuggestionsLoading" class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-400 shadow-lg dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                  Searching…
                </div>
                <ul
                  v-else-if="assignShowSuggestions && assignSuggestions.length > 0"
                  class="absolute z-20 mt-1 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-700"
                  style="max-height: 220px"
                >
                  <li
                    v-for="(emp, idx) in assignSuggestions"
                    :key="emp.id"
                    @mousedown.prevent="selectAssignEmployee(emp)"
                    :class="['cursor-pointer px-3 py-2 text-sm', idx === assignHighlightedIndex ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : 'text-gray-800 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-600']"
                  >
                    <span class="font-medium">{{ emp.last_name }},</span> {{ emp.first_name }}
                    <span class="ml-1 text-xs text-gray-400 dark:text-gray-500">{{ emp.employee_id }}</span>
                  </li>
                </ul>
                <div
                  v-else-if="assignShowSuggestions && assignQuery.length >= 2"
                  class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-500 shadow-lg dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400"
                >
                  No employees found
                </div>
              </div>
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Shift Template</label>
              <select
                v-model="assignForm.shift_template_id"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                required
              >
                <option value="">Select shift</option>
                <option v-for="tmpl in templates" :key="tmpl.id" :value="tmpl.id">
                  {{ tmpl.name }} ({{ tmpl.start_time }} – {{ tmpl.end_time }})
                </option>
              </select>
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
              <DatePicker v-model="assignForm.date" :required="true" />
            </div>
          </div>

          <div v-if="assignError" class="rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-400">{{ assignError }}</div>
          <div v-if="assignSuccess" class="rounded-lg bg-green-50 p-3 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-400">Schedule changed successfully!</div>

          <button
            type="submit"
            :disabled="assigning || !assignForm.employee_id"
            class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
          >
            {{ assigning ? 'Saving…' : 'Change Schedule' }}
          </button>
        </form>
      </div>

      <AdminScheduleChangeRequest />
    </template>

    <!-- Shifts sub-tab -->
    <template v-else>
      <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-5 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">View Employee Shifts</h3>
          <div class="flex items-center gap-2">
            <button @click="navigateWeek(-1)" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
              <ChevronLeft :size="18" />
            </button>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ weekRange }}</span>
            <button @click="navigateWeek(1)" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
              <ChevronRight :size="18" />
            </button>
          </div>
        </div>

        <!-- View employee search autocomplete -->
        <div class="mb-4">
          <div class="relative w-full max-w-xs" ref="viewWrap">
            <input
              v-model="viewQuery"
              type="text"
              placeholder="Search employee…"
              autocomplete="off"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
              @input="onViewInput"
              @focus="viewShowSuggestions = viewSuggestions.length > 0"
              @keydown.escape="viewShowSuggestions = false"
              @keydown.down.prevent="viewHighlightNext"
              @keydown.up.prevent="viewHighlightPrev"
              @keydown.enter.prevent="viewSelectHighlighted"
            />
            <button v-if="viewEmployeeId" @click="clearViewEmployee" type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
              <X :size="14" />
            </button>
            <Search v-else :size="14" class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-gray-400" />

            <div v-if="viewSuggestionsLoading" class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-400 shadow-lg dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
              Searching…
            </div>
            <ul
              v-else-if="viewShowSuggestions && viewSuggestions.length > 0"
              class="absolute z-20 mt-1 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-700"
              style="max-height: 220px"
            >
              <li
                v-for="(emp, idx) in viewSuggestions"
                :key="emp.id"
                @mousedown.prevent="selectViewEmployee(emp)"
                :class="['cursor-pointer px-3 py-2 text-sm', idx === viewHighlightedIndex ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : 'text-gray-800 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-600']"
              >
                <span class="font-medium">{{ emp.last_name }},</span> {{ emp.first_name }}
                <span class="ml-1 text-xs text-gray-400 dark:text-gray-500">{{ emp.employee_id }}</span>
              </li>
            </ul>
            <div
              v-else-if="viewShowSuggestions && viewQuery.length >= 2"
              class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-500 shadow-lg dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400"
            >
              No employees found
            </div>
          </div>
        </div>

        <div v-if="scheduleLoading" class="space-y-2">
          <div v-for="i in 7" :key="i" class="h-10 animate-pulse rounded bg-gray-100 dark:bg-gray-700" />
        </div>

        <div v-else-if="!viewEmployeeId" class="py-10 text-center text-sm text-gray-400 dark:text-gray-500">
          Search an employee to view their shifts
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full">
            <thead>
              <tr class="border-b border-gray-200 dark:border-gray-700">
                <th class="py-3 pr-4 text-left text-sm font-medium text-gray-900 dark:text-gray-100">Day</th>
                <th class="py-3 pr-4 text-left text-sm font-medium text-gray-900 dark:text-gray-100">Shift</th>
                <th class="py-3 pr-4 text-left text-sm font-medium text-gray-900 dark:text-gray-100">Start</th>
                <th class="py-3 pr-4 text-left text-sm font-medium text-gray-900 dark:text-gray-100">End</th>
                <th class="py-3 pr-4 text-left text-sm font-medium text-gray-900 dark:text-gray-100">Hours</th>
                <th class="py-3 text-left text-sm font-medium text-gray-900 dark:text-gray-100">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="schedule.length === 0">
                <td colspan="6" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">No shifts for this week</td>
              </tr>
              <tr v-for="s in schedule" :key="s.id" class="border-b border-gray-100 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/50">
                <td class="py-3 pr-4 font-medium text-gray-900 dark:text-gray-100">{{ formatDay(s.date) }}</td>
                <td class="py-3 pr-4 text-sm text-gray-600 dark:text-gray-400">{{ s.shift_template?.name ?? 'Custom' }}</td>
                <td class="py-3 pr-4 text-sm text-gray-600 dark:text-gray-400">{{ formatTime(s.start_time) }}</td>
                <td class="py-3 pr-4 text-sm text-gray-600 dark:text-gray-400">{{ formatTime(s.end_time) }}</td>
                <td class="py-3 pr-4 text-sm text-gray-900 dark:text-gray-100">{{ s.shift_template?.duration_hours ?? '—' }}h</td>
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
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { CalendarClock, ChevronLeft, ChevronRight, LayoutGrid, Search, X } from 'lucide-vue-next'
import DatePicker from '@/components/ui/DatePicker.vue'
import AdminScheduleChangeRequest from '@/components/Timekeeping/AdminScheduleChangeRequest.vue'

interface EmployeeSuggestion { id: number; employee_id: string; first_name: string; last_name: string }
interface ShiftTemplate { id: number; name: string; start_time: string; end_time: string; duration_hours: number }
interface ScheduleEntry {
  id: number
  date: string
  start_time: string
  end_time: string
  status: string
  shift_template?: ShiftTemplate
}

const activeSubTab = ref<'change_schedule' | 'shifts'>('change_schedule')

// --- shared ---
const templates = ref<ShiftTemplate[]>([])

// --- assign form ---
const assignForm = ref({ employee_id: '' as number | '', shift_template_id: '' as number | '', date: '' })
const assigning = ref(false)
const assignError = ref('')
const assignSuccess = ref(false)

const assignQuery = ref('')
const assignSuggestions = ref<EmployeeSuggestion[]>([])
const assignShowSuggestions = ref(false)
const assignSuggestionsLoading = ref(false)
const assignHighlightedIndex = ref(-1)
const assignWrap = ref<HTMLElement | null>(null)
let assignDebounce: ReturnType<typeof setTimeout>

const onAssignInput = () => {
  clearTimeout(assignDebounce)
  assignForm.value.employee_id = ''
  if (assignQuery.value.length < 2) {
    assignSuggestions.value = []
    assignShowSuggestions.value = false
    return
  }
  assignSuggestionsLoading.value = true
  assignShowSuggestions.value = true
  assignDebounce = setTimeout(() => fetchSuggestions('assign'), 280)
}

const selectAssignEmployee = (emp: EmployeeSuggestion) => {
  assignForm.value.employee_id = emp.id
  assignQuery.value = `${emp.last_name}, ${emp.first_name}`
  assignShowSuggestions.value = false
  assignSuggestions.value = []
}

const clearAssignEmployee = () => {
  assignForm.value.employee_id = ''
  assignQuery.value = ''
  assignSuggestions.value = []
  assignShowSuggestions.value = false
}

const assignHighlightNext = () => { if (assignHighlightedIndex.value < assignSuggestions.value.length - 1) { assignHighlightedIndex.value++ } }
const assignHighlightPrev = () => { if (assignHighlightedIndex.value > 0) { assignHighlightedIndex.value-- } }
const assignSelectHighlighted = () => {
  if (assignHighlightedIndex.value >= 0 && assignSuggestions.value[assignHighlightedIndex.value]) {
    selectAssignEmployee(assignSuggestions.value[assignHighlightedIndex.value])
  }
}

// --- view shifts ---
const schedule = ref<ScheduleEntry[]>([])
const scheduleLoading = ref(false)
const viewEmployeeId = ref<number | ''>('')
const weekOffset = ref(0)

const viewQuery = ref('')
const viewSuggestions = ref<EmployeeSuggestion[]>([])
const viewShowSuggestions = ref(false)
const viewSuggestionsLoading = ref(false)
const viewHighlightedIndex = ref(-1)
const viewWrap = ref<HTMLElement | null>(null)
let viewDebounce: ReturnType<typeof setTimeout>

const onViewInput = () => {
  clearTimeout(viewDebounce)
  viewEmployeeId.value = ''
  schedule.value = []
  if (viewQuery.value.length < 2) {
    viewSuggestions.value = []
    viewShowSuggestions.value = false
    return
  }
  viewSuggestionsLoading.value = true
  viewShowSuggestions.value = true
  viewDebounce = setTimeout(() => fetchSuggestions('view'), 280)
}

const selectViewEmployee = (emp: EmployeeSuggestion) => {
  viewEmployeeId.value = emp.id
  viewQuery.value = `${emp.last_name}, ${emp.first_name}`
  viewShowSuggestions.value = false
  viewSuggestions.value = []
  loadSchedule()
}

const clearViewEmployee = () => {
  viewEmployeeId.value = ''
  viewQuery.value = ''
  schedule.value = []
  viewSuggestions.value = []
  viewShowSuggestions.value = false
}

const viewHighlightNext = () => { if (viewHighlightedIndex.value < viewSuggestions.value.length - 1) { viewHighlightedIndex.value++ } }
const viewHighlightPrev = () => { if (viewHighlightedIndex.value > 0) { viewHighlightedIndex.value-- } }
const viewSelectHighlighted = () => {
  if (viewHighlightedIndex.value >= 0 && viewSuggestions.value[viewHighlightedIndex.value]) {
    selectViewEmployee(viewSuggestions.value[viewHighlightedIndex.value])
  }
}

// --- shared employee search ---
const fetchSuggestions = async (target: 'assign' | 'view') => {
  const query = target === 'assign' ? assignQuery.value : viewQuery.value
  try {
    const res = await fetch(`/api/core/employees?search=${encodeURIComponent(query)}&is_active=1`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
    if (!res.ok) { return }
    const json = await res.json()
    const list: any[] = json.data ?? json
    const mapped = list
      .map((e: any) => ({ id: e.id, employee_id: e.employee_id, first_name: e.first_name, last_name: e.last_name }))
      .sort((a, b) => a.last_name.localeCompare(b.last_name))
      .slice(0, 8)
    if (target === 'assign') {
      assignSuggestions.value = mapped
      assignHighlightedIndex.value = -1
    } else {
      viewSuggestions.value = mapped
      viewHighlightedIndex.value = -1
    }
  } finally {
    if (target === 'assign') { assignSuggestionsLoading.value = false } else { viewSuggestionsLoading.value = false }
  }
}

// --- week navigation ---
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

// --- API ---
const csrfToken = () =>
  decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '')

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
      throw new Error((json as any).error ?? 'Failed to change schedule')
    }
    assignSuccess.value = true
    assignForm.value = { employee_id: '', shift_template_id: '', date: '' }
    assignQuery.value = ''
    setTimeout(() => { assignSuccess.value = false }, 3000)
  } catch (e) {
    assignError.value = e instanceof Error ? e.message : 'An error occurred'
  } finally {
    assigning.value = false
  }
}

// --- formatters ---
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

watch(viewEmployeeId, (val) => { if (!val) { schedule.value = [] } })

const handleOutsideClick = (e: MouseEvent) => {
  if (assignWrap.value && !assignWrap.value.contains(e.target as Node)) {
    assignShowSuggestions.value = false
  }
  if (viewWrap.value && !viewWrap.value.contains(e.target as Node)) {
    viewShowSuggestions.value = false
  }
}

onMounted(() => {
  loadTemplates()
  document.addEventListener('click', handleOutsideClick)
})

onUnmounted(() => {
  document.removeEventListener('click', handleOutsideClick)
})
</script>
