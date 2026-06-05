<template>
  <Layout>
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">My Payslips</h1>
        <p class="mt-1 text-gray-600 dark:text-gray-400">View and download your pay statements</p>
      </div>
    </div>

    <!-- Empty state -->
    <div
      v-if="periods.length === 0"
      class="rounded-lg border border-gray-200 bg-white p-16 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800"
    >
      <FileText :size="48" class="mx-auto mb-4 text-gray-300 dark:text-gray-600" />
      <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No payslips yet</p>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-500">
        Your payslips will appear here once payroll has been processed.
      </p>
    </div>

    <div v-else>
      <!-- Period Selector -->
      <div class="mb-6 flex items-center justify-between rounded-lg border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center gap-3">
          <Calendar :size="18" class="text-blue-500" />
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Pay Period</span>
        </div>
        <div class="flex items-center gap-3">
          <select
            v-model="selectedItemId"
            :disabled="loading"
            class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            @change="onPeriodChange"
          >
            <option v-for="p in periods" :key="p.payroll_item_id" :value="p.payroll_item_id">
              {{ p.label }} · Pay date: {{ formatDate(p.pay_date) }}
            </option>
          </select>
          <a
            v-if="current"
            :href="`/payroll/payslips/${current.id}/download`"
            class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
          >
            <Download :size="15" />
            Download PDF
          </a>
        </div>
      </div>

      <!-- Loading overlay -->
      <div
        v-if="loading"
        class="mx-auto flex max-w-3xl items-center justify-center rounded-lg border border-gray-200 bg-white p-24 shadow-sm dark:border-gray-700 dark:bg-gray-800"
      >
        <div class="flex flex-col items-center gap-3">
          <Loader2 :size="36" class="animate-spin text-blue-500" />
          <p class="text-sm text-gray-500 dark:text-gray-400">Loading payslip…</p>
        </div>
      </div>

      <!-- Payslip card -->
      <div
        v-else-if="current"
        class="mx-auto max-w-3xl rounded-lg border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-700 dark:bg-gray-800"
      >
        <!-- Company header -->
        <div class="mb-6 border-b border-gray-200 pb-6 text-center dark:border-gray-700">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">
            {{ current.employee.company?.name ?? 'Company' }}
          </h2>
          <p class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Payslip</p>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
            Pay Period: {{ formatDate(current.period.start_date) }} – {{ formatDate(current.period.end_date) }}
          </p>
          <p v-if="current.period.cutoff_start_date" class="text-sm text-gray-600 dark:text-gray-300">
            Cut-off Period: {{ formatDate(current.period.cutoff_start_date) }} –
            {{ formatDate(current.period.cutoff_end_date!) }}
          </p>
          <p class="text-sm text-gray-600 dark:text-gray-300">
            Pay Date: {{ formatDate(current.period.pay_date) }}
          </p>
        </div>

        <!-- Employee info -->
        <div class="mb-6 grid grid-cols-2 gap-4 text-sm">
          <div>
            <p class="font-semibold text-gray-900 dark:text-white">
              {{ current.employee.last_name }}, {{ current.employee.first_name }}
              {{ current.employee.middle_name ?? '' }}
            </p>
            <p class="text-gray-600 dark:text-gray-400">{{ current.employee.employee_id }}</p>
            <p class="text-gray-600 dark:text-gray-400">{{ current.employee.department?.name ?? '—' }}</p>
            <p class="text-gray-600 dark:text-gray-400">{{ current.employee.position?.name ?? '—' }}</p>
          </div>
          <div class="text-right">
            <p class="text-gray-600 dark:text-gray-400">SSS: {{ current.employee.sss_number ?? '—' }}</p>
            <p class="text-gray-600 dark:text-gray-400">PhilHealth: {{ current.employee.philhealth_number ?? '—' }}</p>
            <p class="text-gray-600 dark:text-gray-400">Pag-IBIG: {{ current.employee.pagibig_number ?? '—' }}</p>
            <p class="text-gray-600 dark:text-gray-400">TIN: {{ current.employee.tin ?? '—' }}</p>
          </div>
        </div>

        <!-- Attendance summary -->
        <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Days Worked</th>
                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Days Absent</th>
                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Total Hours</th>
                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Minutes Late</th>
              </tr>
            </thead>
            <tbody>
              <tr class="border-t border-gray-100 dark:border-gray-700">
                <td class="px-4 py-2 text-gray-900 dark:text-white">{{ current.days_worked }}</td>
                <td class="px-4 py-2 text-red-600">{{ current.days_absent }}</td>
                <td class="px-4 py-2 text-gray-900 dark:text-white">{{ Number(current.total_hours).toFixed(2) }}</td>
                <td class="px-4 py-2 text-red-600">{{ current.minutes_late }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Earnings & Deductions -->
        <div class="mb-6 space-y-4">
          <!-- Taxable Earnings -->
          <div>
            <h3 class="mb-2 font-semibold text-gray-900 dark:text-white">Taxable Earnings</h3>
            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-200">Description</th>
                    <th class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-200">Hours</th>
                    <th class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-200">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="earning in taxableEarnings"
                    :key="earning.id"
                    class="border-t border-gray-100 dark:border-gray-700"
                  >
                    <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ earning.description ?? formatEarningType(earning.type) }}</td>
                    <td class="px-4 py-2 text-right text-gray-500 dark:text-gray-400">
                      {{ earning.hours != null ? Number(earning.hours).toFixed(2) : '—' }}
                    </td>
                    <td class="px-4 py-2 text-right text-gray-900 dark:text-white">{{ formatPeso(earning.amount) }}</td>
                  </tr>
                  <tr v-if="taxableEarnings.length === 0">
                    <td colspan="3" class="px-4 py-2 text-center text-gray-400">None</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Non-Taxable Benefits -->
          <div v-if="nonTaxableEarnings.length > 0">
            <h3 class="mb-2 font-semibold text-gray-900 dark:text-white">Non-Taxable Benefits</h3>
            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-200">Description</th>
                    <th class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-200">Hours</th>
                    <th class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-200">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="earning in nonTaxableEarnings"
                    :key="earning.id"
                    class="border-t border-gray-100 dark:border-gray-700"
                  >
                    <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ earning.description ?? formatEarningType(earning.type) }}</td>
                    <td class="px-4 py-2 text-right text-gray-500 dark:text-gray-400">
                      {{ earning.hours != null ? Number(earning.hours).toFixed(2) : '—' }}
                    </td>
                    <td class="px-4 py-2 text-right text-gray-900 dark:text-white">{{ formatPeso(earning.amount) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Gross Pay summary -->
          <div class="flex justify-end">
            <div class="w-64 rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-sm dark:border-gray-700 dark:bg-gray-700">
              <div class="flex justify-between font-semibold">
                <span class="text-gray-700 dark:text-gray-300">Gross Pay</span>
                <span class="text-gray-900 dark:text-white">{{ formatPeso(current.gross_pay) }}</span>
              </div>
            </div>
          </div>

          <!-- Deductions -->
          <div>
            <h3 class="mb-2 font-semibold text-gray-900 dark:text-white">Deductions</h3>
            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-200">Description</th>
                    <th class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-200">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="deduction in current.deductions"
                    :key="deduction.id"
                    class="border-t border-gray-100 dark:border-gray-700"
                  >
                    <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                      {{ deduction.description ?? formatDeductionType(deduction.type) }}
                    </td>
                    <td class="px-4 py-2 text-right text-red-600">{{ formatPeso(deduction.amount) }}</td>
                  </tr>
                  <tr class="border-t-2 border-gray-300 bg-gray-50 font-semibold dark:border-gray-600 dark:bg-gray-700">
                    <td class="px-4 py-2 text-gray-900 dark:text-white">Total Deductions</td>
                    <td class="px-4 py-2 text-right text-red-600">{{ formatPeso(current.total_deductions) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Net Pay -->
        <div class="rounded-lg bg-green-50 p-6 text-center dark:bg-green-900/20">
          <p class="text-sm font-medium uppercase tracking-wide text-green-700 dark:text-green-400">Net Pay</p>
          <p class="mt-1 text-4xl font-bold text-green-700 dark:text-green-400">{{ formatPeso(current.net_pay) }}</p>
          <p class="mt-2 text-xs text-green-600 dark:text-green-500">
            {{ formatDate(current.period.start_date) }} – {{ formatDate(current.period.end_date) }}
          </p>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import axios from 'axios'
import { Calendar, Download, FileText, Loader2 } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import Layout from '@/components/Layout.vue'

interface Earning {
  id: number
  type: string
  amount: number
  hours: number | null
  description: string | null
  is_taxable: boolean
}

interface Deduction {
  id: number
  type: string
  amount: number
  description: string | null
}

interface PayslipData {
  id: number
  basic_pay: number
  gross_pay: number
  total_deductions: number
  net_pay: number
  days_worked: number
  days_absent: number
  total_hours: number
  minutes_late: number
  earnings: Earning[]
  deductions: Deduction[]
  period: {
    id: number
    start_date: string
    end_date: string
    cutoff_start_date: string | null
    cutoff_end_date: string | null
    pay_date: string
  }
  employee: {
    first_name: string
    last_name: string
    middle_name: string | null
    employee_id: string
    sss_number: string | null
    philhealth_number: string | null
    pagibig_number: string | null
    tin: string | null
    department: { name: string } | null
    position: { name: string } | null
    company: { name: string } | null
  }
}

interface Period {
  payroll_item_id: number
  period_id: number
  start_date: string
  end_date: string
  pay_date: string
  label: string
}

const props = defineProps<{
  periods: Period[]
  latestPayslip: PayslipData | null
}>()

const selectedItemId = ref<number | null>(props.latestPayslip?.id ?? null)
const current = ref<PayslipData | null>(props.latestPayslip)
const loading = ref(false)

const taxableEarnings = computed(() => current.value?.earnings.filter((e) => e.is_taxable) ?? [])
const nonTaxableEarnings = computed(() => current.value?.earnings.filter((e) => !e.is_taxable) ?? [])

const onPeriodChange = async () => {
  if (!selectedItemId.value) return
  loading.value = true
  try {
    const { data } = await axios.get<PayslipData>(`/api/payroll/my-payslips/${selectedItemId.value}`)
    current.value = data
  } catch {
    // Keep current payslip displayed on error
  } finally {
    loading.value = false
  }
}

const formatDate = (date: string) =>
  new Date(date + 'T00:00:00').toLocaleDateString('en-PH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })

const formatPeso = (amount: number) =>
  new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', currencyDisplay: 'narrowSymbol' }).format(Number(amount))

const earningLabels: Record<string, string> = {
  basic: 'Basic Pay',
  overtime: 'Overtime',
  overtime_weekday: 'Weekday Overtime (125%)',
  overtime_weekend: 'Rest Day Overtime (150%)',
  overtime_holiday: 'Holiday Overtime (200%)',
  night_differential: 'Night Differential',
  regular_holiday: 'Regular Holiday Pay',
  special_holiday: 'Special Holiday Pay',
  rest_day: 'Rest Day Pay',
  thirteenth_month: '13th Month Pay',
  allowance_rice: 'Rice Allowance',
  allowance_transport: 'Transportation Allowance',
  allowance_clothing: 'Clothing Allowance',
  allowance_meal: 'Meal Allowance',
  allowance_other: 'Other Allowance',
  other: 'Other Earnings',
}

const deductionLabels: Record<string, string> = {
  sss: 'SSS Contribution',
  philhealth: 'PhilHealth Premium',
  pagibig: 'Pag-IBIG Contribution',
  withholding_tax: 'Withholding Tax (TRAIN Law)',
  absence: 'Absence Deduction',
  tardiness: 'Tardiness',
  sss_loan: 'SSS Loan',
  pagibig_loan: 'Pag-IBIG Loan',
  company_loan: 'Company Loan',
  other: 'Other Deductions',
}

const formatEarningType = (type: string) => earningLabels[type] ?? type
const formatDeductionType = (type: string) => deductionLabels[type] ?? type
</script>
