<template>
  <Layout>
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Payroll</h1>
        <p class="mt-1 text-gray-600">Manage payroll periods, payslips, and settings.</p>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <div class="rounded-lg border border-gray-200 bg-white p-6">
        <div class="flex items-center justify-between">
          <p class="text-sm font-medium text-gray-500">Total Periods</p>
          <div class="rounded-lg bg-blue-50 p-2">
            <Calendar :size="18" class="text-blue-600" />
          </div>
        </div>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.total_periods }}</p>
        <p class="mt-1 text-sm text-amber-600">{{ stats.draft_periods }} draft</p>
      </div>

      <div class="rounded-lg border border-gray-200 bg-white p-6">
        <div class="flex items-center justify-between">
          <p class="text-sm font-medium text-gray-500">Last Payroll Net Pay</p>
          <div class="rounded-lg bg-green-50 p-2">
            <PhilippinePeso :size="18" class="text-green-600" />
          </div>
        </div>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ formatPeso(stats.last_net_pay) }}</p>
        <p class="mt-1 text-sm text-gray-500">
          {{ stats.last_pay_date ? formatDate(stats.last_pay_date) : 'No payroll run yet' }}
          <span v-if="stats.last_employee_count"> · {{ stats.last_employee_count }} employees</span>
        </p>
      </div>

      <div class="rounded-lg border border-gray-200 bg-white p-6">
        <div class="flex items-center justify-between">
          <p class="text-sm font-medium text-gray-500">Active Loans</p>
          <div class="rounded-lg bg-amber-50 p-2">
            <CreditCard :size="18" class="text-amber-600" />
          </div>
        </div>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.active_loans }}</p>
        <p class="mt-1 text-sm text-gray-500">Active loan records</p>
      </div>
    </div>

    <!-- Recent Periods -->
    <div class="mb-8 rounded-lg border border-gray-200 bg-white">
      <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
        <h2 class="text-base font-semibold text-gray-900">Recent Payroll Periods</h2>
        <Link href="/payroll/periods" class="text-sm font-medium text-blue-600 hover:underline">View all →</Link>
      </div>
      <div v-if="recentPeriods.length === 0" class="px-6 py-10 text-center text-sm text-gray-500">
        No payroll periods yet.
      </div>
      <table v-else class="w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Period</th>
            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Pay Date</th>
            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
            <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Employees</th>
            <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Total Net Pay</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="period in recentPeriods"
            :key="period.id"
            class="border-t border-gray-100 hover:bg-gray-50"
          >
            <td class="px-6 py-3 text-sm text-gray-900">
              {{ formatDate(period.start_date) }} – {{ formatDate(period.end_date) }}
            </td>
            <td class="px-6 py-3 text-sm text-gray-700">{{ formatDate(period.pay_date) }}</td>
            <td class="px-6 py-3 text-sm">
              <span :class="statusClass(period.status)" class="rounded-full px-2 py-0.5 text-xs font-semibold">
                {{ period.status }}
              </span>
            </td>
            <td class="px-6 py-3 text-right text-sm text-gray-700">{{ period.employee_count }}</td>
            <td class="px-6 py-3 text-right text-sm font-medium text-gray-900">
              {{ formatPeso(period.total_net) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

  </Layout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import { Calendar, PhilippinePeso } from 'lucide-vue-next'
import Layout from '@/components/Layout.vue'

interface Stats {
  total_periods: number
  draft_periods: number
  last_net_pay: number
  last_employee_count: number
  last_pay_date: string | null
  active_loans: number
}

interface RecentPeriod {
  id: number
  start_date: string
  end_date: string
  pay_date: string
  status: string
  employee_count: number
  total_net: number
}

defineProps<{
  stats: Stats
  recent_periods: RecentPeriod[]
}>()

const page = usePage()
const permissions = computed<string[]>(() => (page.props.auth as any)?.permissions ?? [])

const recentPeriods = computed(() => {
  const props = page.props as any
  return (props.recent_periods ?? []) as RecentPeriod[]
})

const formatDate = (date: string) =>
  new Date(date).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })

const formatPeso = (amount: number) =>
  new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', currencyDisplay: 'narrowSymbol' }).format(Number(amount))

const statusClass = (status: string) => {
  switch (status) {
    case 'finalized': return 'bg-green-100 text-green-800'
    case 'processing': return 'bg-blue-100 text-blue-800'
    case 'draft': return 'bg-gray-100 text-gray-700'
    default: return 'bg-gray-100 text-gray-700'
  }
}
</script>
