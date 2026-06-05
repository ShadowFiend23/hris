<template>
  <div class="relative bg-gray-50 rounded-lg border border-gray-200 p-5">
    <!-- Header: Month navigation -->
    <div class="flex items-center justify-between mb-4">
      <button
        class="p-1.5 rounded hover:bg-gray-100 text-gray-500 hover:text-gray-800 transition-colors"
        @click="prevMonth"
      >
        <ChevronLeft :size="16" />
      </button>
      <h3 class="text-base font-semibold text-gray-800">{{ monthLabel }}</h3>
      <button
        class="p-1.5 rounded hover:bg-gray-100 text-gray-500 hover:text-gray-800 transition-colors"
        @click="nextMonth"
      >
        <ChevronRight :size="16" />
      </button>
    </div>

    <!-- Day-of-week headers -->
    <div class="grid grid-cols-7 mb-0 border border-b-0 border-gray-100 rounded-t-lg overflow-hidden bg-gray-50 divide-x divide-gray-100">
      <div
        v-for="day in dayHeaders"
        :key="day"
        class="text-center text-xs font-semibold text-gray-400 py-1.5"
      >
        {{ day }}
      </div>
    </div>

    <!-- Calendar grid -->
    <div class="grid grid-cols-7 divide-x divide-y divide-gray-100 border border-gray-100 rounded-lg overflow-hidden">
      <div
        v-for="(cell, i) in calendarCells"
        :key="i"
        class="min-h-[5.5rem] p-1.5 transition-colors"
        :class="{
          'ring-inset ring-2 ring-blue-400 bg-blue-50/60': cell.isToday,
          'bg-gray-50/60 opacity-40': !cell.isCurrentMonth,
          'bg-white': cell.isCurrentMonth && !cell.isToday,
        }"
      >
        <!-- Day number -->
        <span
          class="block text-center text-xs leading-tight mb-1.5"
          :class="[
            cell.isToday ? 'font-bold text-blue-600' : 'font-medium text-gray-500',
          ]"
        >
          {{ cell.day }}
        </span>

        <!-- Event chips -->
        <div class="space-y-0.5">
          <template v-for="(evt, ei) in cell.events" :key="ei">
            <!-- Schedule chip (teal) -->
            <div
              v-if="evt.type === 'schedule'"
              class="text-[11px] font-semibold leading-tight rounded px-1.5 py-1 text-teal-700 bg-teal-50 flex justify-between gap-1"
              :title="evt.detail ?? evt.label"
            >
              <span>{{ evt.detail?.split(' – ')[0] ?? 'Work Day' }}</span>
              <span v-if="evt.detail?.split(' – ')[1]" class="opacity-60 font-normal">{{ evt.detail.split(' – ')[1] }}</span>
            </div>

            <!-- Attendance chip (purple / amber / red) -->
            <div
              v-else-if="evt.type === 'attendance'"
              class="text-[11px] font-semibold leading-tight rounded px-1.5 py-1 flex justify-between gap-1"
              :class="attendanceChipClass(evt.color)"
              :title="`${evt.label}${evt.detail ? ': ' + evt.detail : ''}`"
            >
              <span>{{ evt.detail?.split(' / ')[0] ?? evt.label }}</span>
              <span v-if="evt.detail?.split(' / ')[1]" class="opacity-60 font-normal">{{ evt.detail.split(' / ')[1] }}</span>
            </div>

            <!-- Leave / Holiday: dot + short label -->
            <div
              v-else
              class="flex items-center gap-0.5"
              :title="evt.label"
            >
              <span class="w-2 h-2 rounded-full shrink-0" :class="dotColor(evt.color)" />
              <span class="text-[11px] text-gray-500 truncate leading-tight">{{ evt.detail ?? evt.label }}</span>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- Legend -->
    <div class="mt-4 flex flex-wrap gap-4 border-t border-gray-100 pt-3">
      <div v-for="item in legend" :key="item.label" class="flex items-center gap-1.5">
        <span class="w-2 h-2 rounded-full inline-block" :class="item.dot" />
        <span class="text-xs text-gray-500">{{ item.label }}</span>
      </div>
    </div>

    <!-- Loading overlay -->
    <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-white/70 rounded-lg z-10">
      <Loader2 class="w-6 h-6 animate-spin text-blue-500" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ChevronLeft, ChevronRight, Loader2 } from 'lucide-vue-next'
import { computed, onMounted, ref, watch } from 'vue'
import axios from 'axios'

interface CalendarEvent {
  date: string
  type: string
  label: string
  detail: string | null
  color: string
}

interface CalendarCell {
  day: number
  date: string
  isCurrentMonth: boolean
  isToday: boolean
  events: CalendarEvent[]
}

const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

const legend = [
  { label: 'Schedule', dot: 'bg-teal-400' },
  { label: 'Attendance', dot: 'bg-purple-400' },
  { label: 'Late', dot: 'bg-amber-400' },
  { label: 'Absent', dot: 'bg-red-400' },
  { label: 'Leave', dot: 'bg-green-400' },
  { label: 'Holiday', dot: 'bg-rose-400' },
]

const now = new Date()
const viewYear = ref(now.getFullYear())
const viewMonth = ref(now.getMonth() + 1) // 1-based
const events = ref<CalendarEvent[]>([])
const loading = ref(false)

const monthLabel = computed(() => {
  const d = new Date(viewYear.value, viewMonth.value - 1, 1)
  return d.toLocaleString('default', { month: 'long', year: 'numeric' })
})

function prevMonth(): void {
  if (viewMonth.value === 1) {
    viewMonth.value = 12
    viewYear.value--
  } else {
    viewMonth.value--
  }
}

function nextMonth(): void {
  if (viewMonth.value === 12) {
    viewMonth.value = 1
    viewYear.value++
  } else {
    viewMonth.value++
  }
}

function dotColor(color: string): string {
  const map: Record<string, string> = {
    teal: 'bg-teal-400',
    purple: 'bg-purple-400',
    amber: 'bg-amber-400',
    red: 'bg-red-400',
    orange: 'bg-orange-400',
    green: 'bg-green-400',
    rose: 'bg-rose-400',
    blue: 'bg-blue-400',
  }
  return map[color] ?? 'bg-gray-400'
}

function attendanceChipClass(color: string): string {
  const map: Record<string, string> = {
    purple: 'text-purple-700 bg-purple-50',
    amber: 'text-amber-700 bg-amber-50',
    red: 'text-red-700 bg-red-50',
    orange: 'text-orange-700 bg-orange-50',
    blue: 'text-blue-700 bg-blue-50',
  }
  return map[color] ?? 'text-gray-700 bg-gray-50'
}

/** For schedule events, show a compact time range e.g. "8 – 5:00 PM" */
function scheduleLabel(evt: CalendarEvent): string {
  return evt.detail ?? 'Work Day'
}

/** Format a local Date to YYYY-MM-DD without timezone shift */
function localDateStr(d: Date): string {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const dd = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${dd}`
}

const calendarCells = computed((): CalendarCell[] => {
  const firstDay = new Date(viewYear.value, viewMonth.value - 1, 1)
  const lastDay = new Date(viewYear.value, viewMonth.value, 0)
  const todayStr = localDateStr(new Date())

  const cells: CalendarCell[] = []

  // Leading days from previous month
  const leadingDays = firstDay.getDay() // 0=Sun
  for (let i = leadingDays - 1; i >= 0; i--) {
    const d = new Date(viewYear.value, viewMonth.value - 1, -i)
    const dateStr = localDateStr(d)
    cells.push({ day: d.getDate(), date: dateStr, isCurrentMonth: false, isToday: false, events: [] })
  }

  // Current month days
  for (let day = 1; day <= lastDay.getDate(); day++) {
    const d = new Date(viewYear.value, viewMonth.value - 1, day)
    const dateStr = localDateStr(d)
    cells.push({
      day,
      date: dateStr,
      isCurrentMonth: true,
      isToday: dateStr === todayStr,
      events: events.value.filter((e) => e.date === dateStr),
    })
  }

  // Trailing days to complete the last row
  const trailing = 7 - (cells.length % 7)
  if (trailing < 7) {
    for (let i = 1; i <= trailing; i++) {
      const d = new Date(viewYear.value, viewMonth.value, i)
      const dateStr = localDateStr(d)
      cells.push({ day: d.getDate(), date: dateStr, isCurrentMonth: false, isToday: false, events: [] })
    }
  }

  return cells
})

async function fetchEvents(): Promise<void> {
  loading.value = true
  try {
    const response = await axios.get('/api/dashboard/calendar', {
      params: { year: viewYear.value, month: viewMonth.value },
    })
    events.value = response.data.events ?? []
  } catch {
    events.value = []
  } finally {
    loading.value = false
  }
}

watch([viewYear, viewMonth], fetchEvents)
onMounted(fetchEvents)
</script>
