<template>
  <Layout>
    <div class="w-full">
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Daily Time Record</h1>
          <p class="mt-1 text-gray-600">Civil Service Form No. 48</p>
        </div>
        <div class="flex items-center gap-3">
          <button
            class="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors"
            @click="printDtr"
          >
            <Printer :size="18" />
            Print
          </button>
          <a
            :href="downloadUrl"
            class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors"
          >
            <Download :size="18" />
            Download PDF
          </a>
        </div>
      </div>

      <!-- Controls -->
      <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
        <div class="flex flex-wrap items-end gap-4">
          <!-- Employee selector (for managers/admins) -->
          <div v-if="props.employees.length > 1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
            <select
              v-model="selectedEmployeeId"
              class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
              @change="fetchDtrData"
            >
              <option v-for="emp in props.employees" :key="emp.id" :value="emp.id">
                {{ emp.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
            <select
              v-model="selectedMonth"
              class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
              @change="fetchDtrData"
            >
              <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
            <select
              v-model="selectedYear"
              class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
              @change="fetchDtrData"
            >
              <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Loading skeleton -->
      <div v-if="isLoading" class="bg-white rounded-lg border border-gray-200 p-6 space-y-3">
        <div class="h-6 bg-gray-100 rounded animate-pulse w-1/2 mx-auto" />
        <div v-for="i in 10" :key="i" class="h-8 bg-gray-100 rounded animate-pulse" />
      </div>

      <!-- Error -->
      <div v-else-if="loadError" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
        {{ loadError }}
      </div>

      <!-- DTR Preview -->
      <div v-else-if="dtrData" id="dtr-print-area" class="bg-white rounded-lg border border-gray-200 p-8 print:p-0 print:border-0 print:shadow-none">
        <!-- Form Header -->
        <div class="text-center mb-4">
          <p class="text-xs text-gray-500">Civil Service Form No. 48</p>
          <h2 class="text-2xl font-bold tracking-wider mt-1">DAILY TIME RECORD</h2>
        </div>

        <!-- Employee Info Grid -->
        <div class="space-y-2 mb-4 text-sm">
          <div class="flex items-end gap-2">
            <span class="text-gray-500 shrink-0">Name:</span>
            <span class="border-b border-gray-800 flex-1 font-semibold uppercase">
              {{ selectedEmployee?.name ?? '' }}
            </span>
          </div>
          <div class="grid grid-cols-2 gap-6">
            <div class="flex items-end gap-2">
              <span class="text-gray-500 shrink-0">For the Month of:</span>
              <span class="border-b border-gray-800 flex-1 uppercase font-medium">{{ dtrData.month_name }}</span>
            </div>
            <div class="flex items-end gap-2">
              <span class="text-gray-500 shrink-0">Official Hours:</span>
              <div class="flex-1">
                <div class="text-xs">AM: {{ dtrData.official_am }}</div>
                <div class="text-xs">PM: {{ dtrData.official_pm }}</div>
                <div class="text-xs text-gray-400">Sat & Sun: AS REQUIRED</div>
              </div>
            </div>
          </div>
        </div>

        <!-- DTR Table -->
        <div class="overflow-x-auto">
          <table class="w-full border-collapse text-sm">
            <thead>
              <tr>
                <th rowspan="2" class="border border-gray-400 px-3 py-2 text-center font-semibold bg-gray-50">Day</th>
                <th colspan="2" class="border border-gray-400 px-3 py-2 text-center font-semibold bg-gray-50">A.M.</th>
                <th colspan="2" class="border border-gray-400 px-3 py-2 text-center font-semibold bg-gray-50">P.M.</th>
                <th colspan="2" class="border border-gray-400 px-3 py-2 text-center font-semibold bg-gray-50">Undertime</th>
              </tr>
              <tr>
                <th class="border border-gray-400 px-3 py-1 text-center text-xs font-medium bg-gray-50">Arrival</th>
                <th class="border border-gray-400 px-3 py-1 text-center text-xs font-medium bg-gray-50">Departure</th>
                <th class="border border-gray-400 px-3 py-1 text-center text-xs font-medium bg-gray-50">Arrival</th>
                <th class="border border-gray-400 px-3 py-1 text-center text-xs font-medium bg-gray-50">Departure</th>
                <th class="border border-gray-400 px-3 py-1 text-center text-xs font-medium bg-gray-50">Hours</th>
                <th class="border border-gray-400 px-3 py-1 text-center text-xs font-medium bg-gray-50">Min</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in dtrData.rows" :key="row.day" class="hover:bg-gray-50">
                <td class="border border-gray-300 px-3 py-1 text-center font-semibold text-gray-800">{{ row.day }}</td>
                <td class="border border-gray-300 px-3 py-1 text-center text-gray-700">{{ row.morning_arrival }}</td>
                <td class="border border-gray-300 px-3 py-1 text-center text-gray-700">{{ row.morning_departure }}</td>
                <td class="border border-gray-300 px-3 py-1 text-center text-gray-700">{{ row.afternoon_arrival }}</td>
                <td class="border border-gray-300 px-3 py-1 text-center text-gray-700">{{ row.afternoon_departure }}</td>
                <td class="border border-gray-300 px-3 py-1 text-center text-gray-600">
                  {{ row.undertime_hours > 0 ? row.undertime_hours : '' }}
                </td>
                <td class="border border-gray-300 px-3 py-1 text-center text-gray-600">
                  {{ row.undertime_minutes > 0 ? row.undertime_minutes : '' }}
                </td>
              </tr>
              <tr class="bg-gray-50 font-semibold">
                <td colspan="5" class="border border-gray-400 px-3 py-2 text-right pr-4">TOTAL</td>
                <td class="border border-gray-400 px-3 py-2 text-center">
                  {{ dtrData.total_undertime_hours > 0 ? dtrData.total_undertime_hours : '' }}
                </td>
                <td class="border border-gray-400 px-3 py-2 text-center">
                  {{ dtrData.total_undertime_minutes > 0 ? dtrData.total_undertime_minutes : '' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Certification -->
        <div class="mt-6 text-sm text-gray-700 space-y-4">
          <p>
            I CERTIFY on my honor that the above is a true and correct report of the hours of work
            performed, record of which was made daily at the time of arrival and departure from office.
          </p>
          <div class="grid grid-cols-2 gap-12 mt-8">
            <div>
              <div class="border-t border-gray-800 pt-1 text-center text-xs font-semibold uppercase">
                {{ selectedEmployee?.name ?? '' }}
              </div>
              <div class="text-center text-xs text-gray-500 mt-1">Employee's Signature</div>
            </div>
            <div>
              <p class="text-xs text-gray-500 mb-8">VERIFIED as to the prescribed office hours:</p>
              <div class="border-t border-gray-800 pt-1 text-center text-xs font-semibold uppercase">
                &nbsp;
              </div>
              <div class="text-center text-xs text-gray-500 mt-1">In-charge / Supervisor</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import Layout from '@/components/Layout.vue'
import { Download, Printer } from 'lucide-vue-next'
import { computed, onMounted, ref } from 'vue'

interface EmployeeOption {
  id: number
  name: string
}

interface DtrRow {
  day: number
  morning_arrival: string
  morning_departure: string
  afternoon_arrival: string
  afternoon_departure: string
  undertime_hours: number
  undertime_minutes: number
}

interface DtrData {
  month_name: string
  official_am: string
  official_pm: string
  rows: DtrRow[]
  total_undertime_hours: number
  total_undertime_minutes: number
}

const props = defineProps<{
  employees: EmployeeOption[]
  currentYear: number
  currentMonth: number
  myEmployeeId: number
}>()

const selectedEmployeeId = ref<number>(props.myEmployeeId)
const selectedYear = ref<number>(props.currentYear)
const selectedMonth = ref<number>(props.currentMonth)

const dtrData = ref<DtrData | null>(null)
const isLoading = ref(false)
const loadError = ref<string | null>(null)

const selectedEmployee = computed(() => props.employees.find((e) => e.id === selectedEmployeeId.value))

const months = [
  { value: 1, label: 'January' }, { value: 2, label: 'February' }, { value: 3, label: 'March' },
  { value: 4, label: 'April' }, { value: 5, label: 'May' }, { value: 6, label: 'June' },
  { value: 7, label: 'July' }, { value: 8, label: 'August' }, { value: 9, label: 'September' },
  { value: 10, label: 'October' }, { value: 11, label: 'November' }, { value: 12, label: 'December' },
]

const years = computed(() => {
  const current = props.currentYear
  return Array.from({ length: 5 }, (_, i) => current - 2 + i)
})

const downloadUrl = computed(() => {
  const params = new URLSearchParams({
    year: String(selectedYear.value),
    month: String(selectedMonth.value),
  })
  return `/timekeeping/dtr/${selectedEmployeeId.value}/download?${params.toString()}`
})

async function fetchDtrData(): Promise<void> {
  isLoading.value = true
  loadError.value = null

  try {
    const params = new URLSearchParams({
      employee_id: String(selectedEmployeeId.value),
      year: String(selectedYear.value),
      month: String(selectedMonth.value),
    })

    const response = await fetch(`/api/timekeeping/dtr/data?${params.toString()}`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })

    if (!response.ok) {
      const body = await response.json().catch(() => ({}))
      loadError.value = body.error ?? 'Failed to load DTR data.'
      dtrData.value = null
      return
    }

    dtrData.value = await response.json()
  } catch {
    loadError.value = 'An unexpected error occurred while loading DTR data.'
    dtrData.value = null
  } finally {
    isLoading.value = false
  }
}

function printDtr(): void {
  window.print()
}

onMounted(() => {
  fetchDtrData()
})
</script>

<style>
@media print {
  body > *:not(#dtr-print-area) {
    display: none !important;
  }
  #dtr-print-area {
    display: block !important;
  }
}
</style>
