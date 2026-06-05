<template>
  <Layout>
    <!-- Back + Header -->
    <div class="mb-6">
      <Link
        :href="`/payroll/periods/${payrollItem.period.id}`"
        class="mb-3 inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900"
      >
        <ChevronLeft :size="16" />
        Back to Period
      </Link>
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Payslip</h1>
          <p class="mt-1 text-gray-600">
            {{ payrollItem.employee.first_name }} {{ payrollItem.employee.last_name }} ·
            {{ formatDate(payrollItem.period.start_date) }} – {{ formatDate(payrollItem.period.end_date) }}
          </p>
        </div>
        <a
          :href="`/payroll/payslips/${payrollItem.id}/download`"
          class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
        >
          <Download :size="16" />
          Download PDF
        </a>
      </div>
    </div>

    <!-- Payslip Card -->
    <div class="mx-auto max-w-3xl rounded-lg border border-gray-200 bg-white p-8 shadow-sm">
      <!-- Company Header -->
      <div class="mb-6 border-b border-gray-200 pb-6 text-center">
        <h2 class="text-xl font-bold text-gray-900">
          {{ payrollItem.employee.company?.name ?? 'Company' }}
        </h2>
        <p class="text-sm text-gray-600">Payslip</p>
        <p class="text-sm text-gray-600">
          Pay Period: {{ formatDate(payrollItem.period.start_date) }} – {{ formatDate(payrollItem.period.end_date) }}
        </p>
        <p v-if="payrollItem.period.cutoff_start_date" class="text-sm text-gray-600">
          Cut-off Period: {{ formatDate(payrollItem.period.cutoff_start_date) }} – {{ formatDate(payrollItem.period.cutoff_end_date!) }}
        </p>
        <p class="text-sm text-gray-600">Pay Date: {{ formatDate(payrollItem.period.pay_date) }}</p>
      </div>

      <!-- Employee Info -->
      <div class="mb-6 grid grid-cols-2 gap-4 text-sm">
        <div>
          <p class="font-semibold text-gray-900">
            {{ payrollItem.employee.last_name }}, {{ payrollItem.employee.first_name }}
            {{ payrollItem.employee.middle_name ?? '' }}
          </p>
          <p class="text-gray-600">{{ payrollItem.employee.employee_id }}</p>
          <p class="text-gray-600">{{ payrollItem.employee.department?.name ?? '—' }}</p>
          <p class="text-gray-600">{{ payrollItem.employee.position?.name ?? '—' }}</p>
        </div>
        <div class="text-right">
          <p class="text-gray-600">SSS: {{ payrollItem.employee.sss_number ?? '—' }}</p>
          <p class="text-gray-600">PhilHealth: {{ payrollItem.employee.philhealth_number ?? '—' }}</p>
          <p class="text-gray-600">Pag-IBIG: {{ payrollItem.employee.pagibig_number ?? '—' }}</p>
          <p class="text-gray-600">TIN: {{ payrollItem.employee.tin_number ?? '—' }}</p>
        </div>
      </div>

      <!-- Attendance Summary -->
      <div class="mb-6 overflow-hidden rounded-lg border border-gray-200">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left font-semibold text-gray-700">Days Worked</th>
              <th class="px-4 py-2 text-left font-semibold text-gray-700">Days Absent</th>
              <th class="px-4 py-2 text-left font-semibold text-gray-700">Hours</th>
              <th class="px-4 py-2 text-left font-semibold text-gray-700">Minutes Late</th>
            </tr>
          </thead>
          <tbody>
            <tr class="border-t border-gray-100">
              <td class="px-4 py-2 text-gray-900">{{ payrollItem.days_worked }}</td>
              <td class="px-4 py-2 text-red-600">{{ payrollItem.days_absent }}</td>
              <td class="px-4 py-2 text-gray-900">{{ payrollItem.total_hours }}</td>
              <td class="px-4 py-2 text-red-600">{{ payrollItem.minutes_late }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Earnings & Deductions -->
      <div class="mb-6 space-y-4">
        <!-- Taxable Earnings -->
        <div>
          <h3 class="mb-2 font-semibold text-gray-900">Taxable Earnings</h3>
          <div class="overflow-hidden rounded-lg border border-gray-200">
            <table class="w-full text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left font-medium text-gray-700">Description</th>
                  <th class="px-4 py-2 text-right font-medium text-gray-700">Hours</th>
                  <th class="px-4 py-2 text-right font-medium text-gray-700">Amount</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="earning in taxableEarnings"
                  :key="earning.id"
                  class="border-t border-gray-100"
                >
                  <td class="px-4 py-2 text-gray-700">{{ earning.description ?? formatEarningType(earning.type) }}</td>
                  <td class="px-4 py-2 text-right text-gray-500">{{ earning.hours != null ? earning.hours : '—' }}</td>
                  <td class="px-4 py-2 text-right text-gray-900">{{ formatPeso(earning.amount) }}</td>
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
          <h3 class="mb-2 font-semibold text-gray-900">Non-Taxable Benefits</h3>
          <div class="overflow-hidden rounded-lg border border-gray-200">
            <table class="w-full text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left font-medium text-gray-700">Description</th>
                  <th class="px-4 py-2 text-right font-medium text-gray-700">Hours</th>
                  <th class="px-4 py-2 text-right font-medium text-gray-700">Amount</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="earning in nonTaxableEarnings"
                  :key="earning.id"
                  class="border-t border-gray-100"
                >
                  <td class="px-4 py-2 text-gray-700">{{ earning.description ?? formatEarningType(earning.type) }}</td>
                  <td class="px-4 py-2 text-right text-gray-500">{{ earning.hours != null ? earning.hours : '—' }}</td>
                  <td class="px-4 py-2 text-right text-gray-900">{{ formatPeso(earning.amount) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Gross Pay summary row -->
        <div class="flex justify-end">
          <div class="w-64 rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-sm">
            <div class="flex justify-between font-semibold">
              <span class="text-gray-700">Gross Pay</span>
              <span class="text-gray-900">{{ formatPeso(payrollItem.gross_pay) }}</span>
            </div>
          </div>
        </div>

        <!-- Deductions -->
        <div>
          <h3 class="mb-2 font-semibold text-gray-900">Deductions</h3>
          <div class="overflow-hidden rounded-lg border border-gray-200">
            <table class="w-full text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left font-medium text-gray-700">Description</th>
                  <th class="px-4 py-2 text-right font-medium text-gray-700">Amount</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="deduction in payrollItem.deductions"
                  :key="deduction.id"
                  class="border-t border-gray-100"
                >
                  <td class="px-4 py-2 text-gray-700">{{ deduction.description ?? formatDeductionType(deduction.type) }}</td>
                  <td class="px-4 py-2 text-right text-red-600">{{ formatPeso(deduction.amount) }}</td>
                </tr>
                <tr class="border-t-2 border-gray-300 bg-gray-50 font-semibold">
                  <td class="px-4 py-2 text-gray-900">Total Deductions</td>
                  <td class="px-4 py-2 text-right text-red-600">{{ formatPeso(payrollItem.total_deductions) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Net Pay -->
      <div class="rounded-lg bg-green-50 p-6 text-center">
        <p class="text-sm font-medium uppercase tracking-wide text-green-700">Net Pay</p>
        <p class="mt-1 text-4xl font-bold text-green-700">{{ formatPeso(payrollItem.net_pay) }}</p>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { ChevronLeft, Download } from 'lucide-vue-next'
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

interface PayrollItem {
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
    pay_date: string
    cutoff_start_date: string | null
    cutoff_end_date: string | null
  }
  employee: {
    first_name: string
    last_name: string
    middle_name: string | null
    employee_id: string
    sss_number: string | null
    philhealth_number: string | null
    pagibig_number: string | null
    tin_number: string | null
    department: { name: string } | null
    position: { name: string } | null
    company: { name: string } | null
  }
}

const props = defineProps<{
  payrollItem: PayrollItem
}>()

const taxableEarnings = computed(() => props.payrollItem.earnings.filter((e) => e.is_taxable))
const nonTaxableEarnings = computed(() => props.payrollItem.earnings.filter((e) => !e.is_taxable))

const formatDate = (date: string) =>
  new Date(date + 'T00:00:00').toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })

const formatPeso = (amount: number) =>
  new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', currencyDisplay: 'narrowSymbol' }).format(Number(amount))

const earningLabels: Record<string, string> = {
  basic: 'Basic Pay',
  overtime: 'Overtime',
  overtime_weekday: 'Weekday Overtime (125%)',
  overtime_weekend: 'Rest Day Overtime (150%)',
  overtime_holiday: 'Holiday Overtime (200%)',
  night_differential: 'Night Differential',
  regular_holiday: 'Regular Holiday',
  special_holiday: 'Special Holiday',
  rest_day: 'Rest Day',
  thirteenth_month: '13th Month Pay',
  allowance_rice: 'Rice Allowance',
  allowance_transport: 'Transportation Allowance',
  allowance_clothing: 'Clothing Allowance',
  allowance_meal: 'Meal Allowance',
  allowance_gas: 'Gas Allowance',
  allowance_other: 'Other Allowance',
  other: 'Other',
}

const deductionLabels: Record<string, string> = {
  sss: 'SSS',
  philhealth: 'PhilHealth',
  pagibig: 'Pag-IBIG',
  withholding_tax: 'Withholding Tax',
  absence: 'Absence',
  tardiness: 'Tardiness',
  sss_loan: 'SSS Loan',
  pagibig_loan: 'Pag-IBIG Loan',
  company_loan: 'Company Loan',
  other: 'Other',
}

const formatEarningType = (type: string) => earningLabels[type] ?? type
const formatDeductionType = (type: string) => deductionLabels[type] ?? type
</script>
