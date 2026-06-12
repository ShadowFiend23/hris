<template>
  <Layout>
    <!-- Page Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
        Analytics Dashboard
      </h1>
      <p class="text-gray-600 dark:text-gray-400">
        Overview of HR, Timekeeping, and Payroll metrics.
      </p>
    </div>

    <!-- Time Range Selector -->
    <div class="mb-6 flex items-center justify-between">
      <div class="flex gap-2">
        <button
          v-for="r in [7, 30, 90]"
          :key="r"
          @click="setRange(r)"
          :class="[
            'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
            range === r
              ? 'bg-blue-600 text-white'
              : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700',
          ]"
        >
          Last {{ r }} Days
        </button>
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
      <div
        v-for="(card, index) in kpiCards"
        :key="index"
        class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow"
      >
        <div class="flex items-start justify-between">
          <div>
            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">{{ card.label }}</p>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
              {{ card.value }}
            </h3>
            <p v-if="card.sub" class="text-gray-500 dark:text-gray-400 text-xs mt-1">
              {{ card.sub }}
            </p>
          </div>
          <div class="p-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
            <Users v-if="card.icon === 'users'" :size="24" class="text-blue-600 dark:text-blue-400" />
            <UserCheck v-else-if="card.icon === 'attendance'" :size="24" class="text-blue-600 dark:text-blue-400" />
            <ClipboardCheck v-else-if="card.icon === 'approvals'" :size="24" class="text-blue-600 dark:text-blue-400" />
            <TrendingDown v-else-if="card.icon === 'turnover'" :size="24" class="text-blue-600 dark:text-blue-400" />
            <PhilippinePeso v-else-if="card.icon === 'payroll'" :size="24" class="text-blue-600 dark:text-blue-400" />
          </div>
        </div>
      </div>
    </div>

    <!-- Attendance Trend -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-8">
      <div class="flex items-start justify-between mb-6">
        <div>
          <h3 class="text-lg font-bold text-gray-900 dark:text-white">Attendance Trend</h3>
          <p class="text-gray-500 dark:text-gray-400 text-sm">Daily present / late / absent — last {{ range }} days</p>
        </div>
      </div>
      <div v-if="attendanceTrend.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400 text-sm">
        No attendance data for this range.
      </div>
      <div v-else class="flex items-end gap-1 h-44 overflow-x-auto pb-1">
        <div
          v-for="(d, i) in attendanceTrend"
          :key="i"
          class="flex flex-col items-center gap-1 shrink-0"
          :style="{ width: `${Math.max(100 / attendanceTrend.length, 6)}%`, minWidth: '6px' }"
          :title="`${d.date} — present ${d.present}, late ${d.late}, absent ${d.absent}`"
        >
          <div class="flex flex-col justify-end w-full h-40">
            <div class="bg-red-500 rounded-t w-full" :style="{ height: barHeight(d.absent) }" />
            <div class="bg-amber-400 w-full" :style="{ height: barHeight(d.late) }" />
            <div class="bg-emerald-500 w-full" :style="{ height: barHeight(d.present) }" />
          </div>
          <span v-if="showLabel(i)" class="text-[10px] text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ d.date }}</span>
        </div>
      </div>
      <div class="flex gap-4 justify-center mt-4 text-sm text-gray-600 dark:text-gray-300">
        <div class="flex items-center gap-2"><div class="w-3 h-3 bg-emerald-500 rounded" /><span>Present</span></div>
        <div class="flex items-center gap-2"><div class="w-3 h-3 bg-amber-400 rounded" /><span>Late</span></div>
        <div class="flex items-center gap-2"><div class="w-3 h-3 bg-red-500 rounded" /><span>Absent</span></div>
      </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
      <!-- Headcount by Department -->
      <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">
          Headcount by Department
        </h3>
        <div class="mb-4">
          <h4 class="text-2xl font-bold text-gray-900 dark:text-white">{{ totalHeadcount }}</h4>
          <p class="text-gray-500 dark:text-gray-400 text-sm">active employees</p>
        </div>
        <div v-if="departmentHeadcount.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
          No department data available
        </div>
        <div v-else class="space-y-3">
          <div v-for="(dept, index) in departmentHeadcount" :key="index">
            <div class="flex justify-between items-center mb-1">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ dept.name }}</span>
              <span class="text-sm text-gray-600 dark:text-gray-400">{{ dept.value }}</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
              <div
                class="bg-blue-600 h-2 rounded-full"
                :style="{ width: totalHeadcount > 0 ? `${(Number(dept.value) / totalHeadcount) * 100}%` : '0%' }"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Hires vs. Departures -->
      <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-start justify-between mb-6">
          <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Hires vs. Departures</h3>
            <p class="text-blue-600 dark:text-blue-400 text-2xl font-bold mt-2">{{ stats.turnover_rate }}%</p>
            <p class="text-gray-500 dark:text-gray-400 text-sm">turnover rate (last 6 months)</p>
          </div>
          <div class="text-right">
            <p class="text-emerald-600 dark:text-emerald-400 text-sm font-medium">{{ netHires >= 0 ? '+' : '' }}{{ netHires }} net hires</p>
          </div>
        </div>
        <div class="flex items-end justify-between h-56 gap-2">
          <div v-for="(data, index) in monthlyMovement" :key="index" class="flex-1 flex flex-col items-center gap-2">
            <div class="flex gap-1 h-40 items-end">
              <div class="bg-blue-600 rounded-t w-3" :style="{ height: barHeightMovement(data.hires) }" />
              <div class="bg-red-500 rounded-t w-3" :style="{ height: barHeightMovement(data.departures) }" />
            </div>
            <span class="text-xs text-gray-600 dark:text-gray-400">{{ data.month }}</span>
          </div>
        </div>
        <div class="flex gap-4 justify-center mt-6 text-sm text-gray-600 dark:text-gray-300">
          <div class="flex items-center gap-2"><div class="w-3 h-3 bg-blue-600 rounded" /><span>Hires</span></div>
          <div class="flex items-center gap-2"><div class="w-3 h-3 bg-red-500 rounded" /><span>Departures</span></div>
        </div>
      </div>
    </div>

    <!-- Payroll + Approvals -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Payroll Summary -->
      <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Latest Payroll</h3>
        <div v-if="!payrollSummary" class="text-center py-8 text-gray-500 dark:text-gray-400 text-sm">
          No finalized payroll yet.
        </div>
        <div v-else>
          <div class="flex items-baseline justify-between mb-1">
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ payrollSummary.period }}</span>
            <span class="text-xs text-gray-400 dark:text-gray-500">Paid {{ payrollSummary.pay_date }}</span>
          </div>
          <h4 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">{{ peso(payrollSummary.net_total) }}</h4>
          <div class="grid grid-cols-3 gap-3 text-center">
            <div class="bg-gray-50 dark:bg-gray-700/40 rounded-lg p-3">
              <p class="text-xs text-gray-500 dark:text-gray-400">Headcount</p>
              <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ payrollSummary.headcount }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/40 rounded-lg p-3">
              <p class="text-xs text-gray-500 dark:text-gray-400">Gross</p>
              <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ peso(payrollSummary.gross_total) }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/40 rounded-lg p-3">
              <p class="text-xs text-gray-500 dark:text-gray-400">Deductions</p>
              <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ peso(payrollSummary.deductions_total) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Pending Approvals -->
      <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
          Pending Approvals
          <span v-if="stats.pending_approvals > 0" class="ml-1 text-sm font-semibold text-white bg-amber-500 rounded-full px-2 py-0.5">{{ stats.pending_approvals }}</span>
        </h3>
        <div v-if="stats.pending_approvals === 0" class="text-center py-8 text-gray-500 dark:text-gray-400 text-sm">
          Nothing awaiting approval. 🎉
        </div>
        <div v-else class="space-y-2">
          <div
            v-for="(item, i) in approvalRows"
            :key="i"
            class="flex items-center justify-between rounded-lg border border-gray-100 dark:border-gray-700 px-3 py-2"
          >
            <div class="min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ item.employee }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">{{ item.type }} · {{ item.date }}</p>
            </div>
            <span class="text-xs font-medium text-gray-600 dark:text-gray-300 shrink-0">{{ item.amount }}</span>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import Layout from '@/components/Layout.vue'
import { Users, UserCheck, ClipboardCheck, TrendingDown, PhilippinePeso } from 'lucide-vue-next'

interface Stats {
  total_employees: number
  total_departments: number
  attendance_today_pct: number
  pending_leaves: number
  pending_overtime: number
  pending_approvals: number
  turnover_rate: number
  payroll_status: string
}

interface DepartmentHeadcount { name: string; value: number }
interface MonthlyMovement { month: string; hires: number; departures: number }
interface AttendanceTrend { date: string; present: number; late: number; absent: number }
interface PayrollSummary {
  period: string
  pay_date: string
  headcount: number
  gross_total: number
  deductions_total: number
  net_total: number
}
interface ApprovalLeave { employee: string; type: string; date: string; days: number }
interface ApprovalOt { employee: string; type: string; date: string; hours: number }

interface Props {
  range: number
  stats: Stats
  department_headcount: DepartmentHeadcount[]
  monthly_movement: MonthlyMovement[]
  attendance_trend: AttendanceTrend[]
  payroll_summary: PayrollSummary | null
  pending_approvals: { leaves: ApprovalLeave[]; overtime: ApprovalOt[] }
}

const props = defineProps<Props>()

const range = computed(() => props.range ?? 30)
const stats = computed(() => props.stats)
const departmentHeadcount = computed(() => props.department_headcount ?? [])
const monthlyMovement = computed(() => props.monthly_movement ?? [])
const attendanceTrend = computed(() => props.attendance_trend ?? [])
const payrollSummary = computed(() => props.payroll_summary)

const totalHeadcount = computed(() =>
  departmentHeadcount.value.reduce((sum, d) => sum + Number(d.value), 0),
)

const netHires = computed(() =>
  monthlyMovement.value.reduce((sum, m) => sum + Number(m.hires) - Number(m.departures), 0),
)

const maxMovement = computed(() =>
  Math.max(...monthlyMovement.value.flatMap((m) => [Number(m.hires), Number(m.departures)]), 1),
)

const maxDayTotal = computed(() =>
  Math.max(
    ...attendanceTrend.value.map((d) => Number(d.present) + Number(d.late) + Number(d.absent)),
    1,
  ),
)

function barHeight(count: number): string {
  return `${(Number(count) / maxDayTotal.value) * 100}%`
}

function barHeightMovement(count: number): string {
  return `${(Number(count) / maxMovement.value) * 100}%`
}

// Avoid label crowding when the range is large.
function showLabel(index: number): boolean {
  const step = Math.ceil(attendanceTrend.value.length / 12)
  return index % step === 0
}

function peso(n: number): string {
  return '₱' + Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function setRange(r: number): void {
  router.get('/', { range: r }, {
    preserveScroll: true,
    preserveState: true,
    replace: true,
    only: ['range', 'stats', 'department_headcount', 'monthly_movement', 'attendance_trend', 'payroll_summary', 'pending_approvals'],
  })
}

const approvalRows = computed(() => {
  const leaves = (props.pending_approvals?.leaves ?? []).map((l) => ({
    employee: l.employee,
    type: l.type,
    date: l.date,
    amount: `${l.days} day(s)`,
  }))
  const ot = (props.pending_approvals?.overtime ?? []).map((o) => ({
    employee: o.employee,
    type: o.type,
    date: o.date,
    amount: `${o.hours} hr(s)`,
  }))
  return [...leaves, ...ot]
})

const kpiCards = computed(() => [
  {
    label: 'Total Employees',
    value: stats.value?.total_employees != null ? stats.value.total_employees.toLocaleString() : '—',
    sub: `${stats.value?.total_departments ?? 0} departments`,
    icon: 'users',
  },
  {
    label: 'Attendance Today',
    value: stats.value?.attendance_today_pct != null ? `${stats.value.attendance_today_pct}%` : '—',
    icon: 'attendance',
  },
  {
    label: 'Pending Approvals',
    value: stats.value?.pending_approvals != null ? stats.value.pending_approvals.toString() : '—',
    sub: `${stats.value?.pending_leaves ?? 0} leave · ${stats.value?.pending_overtime ?? 0} OT`,
    icon: 'approvals',
  },
  {
    label: 'Turnover (6 mo)',
    value: stats.value?.turnover_rate != null ? `${stats.value.turnover_rate}%` : '—',
    icon: 'turnover',
  },
  {
    label: 'Latest Net Pay',
    value: payrollSummary.value ? peso(payrollSummary.value.net_total) : stats.value?.payroll_status ?? '—',
    icon: 'payroll',
  },
])
</script>
