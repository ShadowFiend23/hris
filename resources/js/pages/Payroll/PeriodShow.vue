<template>
  <Layout>
    <!-- Back + Header -->
    <div class="mb-6">
      <Link href="/payroll/periods" class="mb-3 inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900">
        <ChevronLeft :size="16" />
        Back to Periods
      </Link>
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">
            {{ formatDate(period.start_date) }} – {{ formatDate(period.end_date) }}
          </h1>
          <p class="mt-1 text-gray-600">Pay date: {{ formatDate(period.pay_date) }}</p>
        </div>
        <div class="flex items-center gap-3">
          <span :class="['rounded-full px-4 py-1.5 text-sm font-semibold', statusColor(period.status)]">
            {{ period.status }}
          </span>
          <button
            v-if="canRun && period.status === 'draft'"
            @click="runPeriod"
            :disabled="running"
            class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
          >
            <Loader2 v-if="running" :size="16" class="animate-spin" />
            <Play v-else :size="16" />
            Run Payroll
          </button>
          <button
            v-if="canRun && period.status === 'processing'"
            @click="finalizePeriod"
            :disabled="finalizing"
            class="flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50"
          >
            <Loader2 v-if="finalizing" :size="16" class="animate-spin" />
            <CheckCircle v-else :size="16" />
            Finalize
          </button>
        </div>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
      <div class="rounded-lg border border-gray-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Employees</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ period.items.length }}</p>
      </div>
      <div class="rounded-lg border border-gray-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total Gross</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ formatPeso(totalGross) }}</p>
      </div>
      <div class="rounded-lg border border-gray-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total Deductions</p>
        <p class="mt-1 text-2xl font-bold text-red-600">{{ formatPeso(totalDeductions) }}</p>
      </div>
      <div class="rounded-lg border border-gray-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total Net Pay</p>
        <p class="mt-1 text-2xl font-bold text-green-600">{{ formatPeso(totalNet) }}</p>
      </div>
    </div>

    <!-- Items Table -->
    <div class="rounded-lg border border-gray-200 bg-white">
      <!-- Empty State -->
      <div v-if="period.items.length === 0" class="p-12 text-center">
        <Users class="mx-auto text-gray-400" :size="48" />
        <h3 class="mt-4 text-lg font-medium text-gray-900">No payroll items yet</h3>
        <p class="mt-1 text-gray-600">Run payroll to generate employee payslips.</p>
      </div>

      <template v-else>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="border-b border-gray-200 bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Employee</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Days</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Absent</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Basic Pay</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Gross Pay</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Deductions</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Net Pay</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in paginatedItems"
                :key="item.id"
                class="border-b border-gray-100 hover:bg-gray-50"
              >
                <td class="px-6 py-4 text-sm">
                  <div class="font-medium text-gray-900">
                    {{ item.employee.first_name }} {{ item.employee.last_name }}
                  </div>
                  <div class="text-xs text-gray-500">{{ item.employee.employee_id }}</div>
                </td>
                <td class="px-6 py-4 text-right text-sm text-gray-700">{{ item.days_worked }}</td>
                <td class="px-6 py-4 text-right text-sm text-red-600">{{ item.days_absent }}</td>
                <td class="px-6 py-4 text-right text-sm text-gray-700">{{ formatPeso(item.basic_pay) }}</td>
                <td class="px-6 py-4 text-right text-sm text-gray-700">{{ formatPeso(item.gross_pay) }}</td>
                <td class="px-6 py-4 text-right text-sm text-red-600">{{ formatPeso(item.total_deductions) }}</td>
                <td class="px-6 py-4 text-right text-sm font-semibold text-green-700">{{ formatPeso(item.net_pay) }}</td>
                <td class="px-6 py-4 text-sm">
                  <Link
                    :href="`/payroll/payslips/${item.id}`"
                    class="rounded px-3 py-1 text-xs font-medium text-blue-600 border border-blue-200 hover:bg-blue-50"
                  >
                    Payslip
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
          <!-- Pagination -->
          <div class="flex items-center border-t border-gray-200 bg-white px-6 py-4">
            <div class="flex w-1/3 items-center gap-2">
              <span class="text-sm text-gray-600">Per page:</span>
              <select
                v-model="perPage"
                @change="changePerPage"
                class="rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option v-for="n in [5, 10, 25, 50]" :key="n" :value="n">{{ n }}</option>
              </select>
            </div>
            <div class="flex w-1/3 justify-center gap-2">
              <button
                @click="currentPage--"
                :disabled="currentPage <= 1"
                class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400"
                v-html="'&laquo;'"
              />
              <button
                v-for="p in totalPages"
                :key="p"
                @click="currentPage = p"
                :class="[
                  'rounded-lg px-3 py-1 text-sm font-medium transition-colors',
                  currentPage === p ? 'bg-blue-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50',
                ]"
              >{{ p }}</button>
              <button
                @click="currentPage++"
                :disabled="currentPage >= totalPages"
                class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400"
                v-html="'&raquo;'"
              />
            </div>
            <div class="flex w-1/3 justify-end">
              <p class="text-sm text-gray-600">{{ period.items.length }} total entries</p>
            </div>
          </div>
        </div>
      </template>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import { ChevronLeft, Play, CheckCircle, Loader2, Users } from 'lucide-vue-next'
import Layout from '@/components/Layout.vue'

interface Employee {
  id: number
  first_name: string
  last_name: string
  employee_id: string
}

interface PayrollItem {
  id: number
  employee: Employee
  basic_pay: number
  gross_pay: number
  total_deductions: number
  net_pay: number
  days_worked: number
  days_absent: number
  minutes_late: number
  status: string
}

interface PayrollPeriod {
  id: number
  start_date: string
  end_date: string
  pay_date: string
  status: string
  items: PayrollItem[]
}

const props = defineProps<{
  period: PayrollPeriod
}>()

const page = usePage()
const permissions = computed<string[]>(() => (page.props.auth as any)?.permissions ?? [])
const canRun = computed(() => permissions.value.includes('payroll.run'))

const running = ref(false)
const finalizing = ref(false)

const perPage = ref(5)
const currentPage = ref(1)
const totalPages = computed(() => Math.ceil(props.period.items.length / perPage.value))
const paginatedItems = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return props.period.items.slice(start, start + perPage.value)
})
const changePerPage = () => { currentPage.value = 1 }

const totalGross = computed(() => props.period.items.reduce((sum, i) => sum + Number(i.gross_pay), 0))
const totalDeductions = computed(() => props.period.items.reduce((sum, i) => sum + Number(i.total_deductions), 0))
const totalNet = computed(() => props.period.items.reduce((sum, i) => sum + Number(i.net_pay), 0))

const runPeriod = () => {
  running.value = true
  router.post(`/payroll/periods/${props.period.id}/run`, {}, {
    onFinish: () => { running.value = false },
  })
}

const finalizePeriod = () => {
  finalizing.value = true
  router.post(`/payroll/periods/${props.period.id}/finalize`, {}, {
    onFinish: () => { finalizing.value = false },
  })
}

const formatDate = (date: string) =>
  new Date(date).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })

const formatPeso = (amount: number) =>
  new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', currencyDisplay: 'narrowSymbol' }).format(Number(amount))

const statusColor = (status: string) =>
  ({
    draft: 'bg-gray-100 text-gray-700',
    processing: 'bg-blue-100 text-blue-700',
    finalized: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
  })[status] ?? 'bg-gray-100 text-gray-700'
</script>
