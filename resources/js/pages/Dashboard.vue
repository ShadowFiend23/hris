<template>
  <Layout>
    <!-- Page Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">
        Analytics Dashboard
      </h1>
      <p class="text-gray-600">
        Overview of HR, Timekeeping, and Payroll metrics.
      </p>
    </div>

    <!-- Time Range Selector -->
    <div class="mb-6 flex items-center justify-between">
      <div class="flex gap-2">
        <button
          v-for="range in ['7days', '30days', '90days']"
          :key="range"
          @click="timeRange = range"
          :class="[
            'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
            timeRange === range
              ? 'bg-blue-600 text-white'
              : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50',
          ]"
        >
          {{ getRangeLabel(range) }}
        </button>
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
      <div
        v-for="(card, index) in kpiCards"
        :key="index"
        class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition-shadow"
      >
        <div class="flex items-start justify-between">
          <div>
            <p class="text-gray-600 text-sm font-medium">{{ card.label }}</p>
            <h3 class="text-2xl font-bold text-gray-900 mt-2">
              {{ card.value }}
            </h3>
            <p v-if="card.change" class="text-green-600 text-sm font-medium mt-1">
              {{ card.change }}
            </p>
          </div>
          <div class="p-2 bg-blue-50 rounded-lg">
            <Users v-if="card.icon === 'users'" :size="24" class="text-blue-600" />
            <LayoutGrid v-else-if="card.icon === 'modules'" :size="24" class="text-blue-600" />
            <UserCheck v-else-if="card.icon === 'attendance'" :size="24" class="text-blue-600" />
            <CalendarDays v-else-if="card.icon === 'leaves'" :size="24" class="text-blue-600" />
            <PhilippinePeso v-else-if="card.icon === 'payroll'" :size="24" class="text-blue-600" />
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Headcount by Department -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-6">
          Headcount by Department
        </h3>
        <div class="mb-4">
          <h4 class="text-2xl font-bold text-gray-900">{{ totalHeadcount }}</h4>
          <p class="text-gray-500 text-sm">active employees</p>
        </div>
        <div v-if="departmentHeadcount.length === 0" class="text-center py-4 text-gray-500 text-sm">
          No department data available
        </div>
        <div v-else class="space-y-3">
          <div v-for="(dept, index) in departmentHeadcount" :key="index">
            <div class="flex justify-between items-center mb-1">
              <span class="text-sm font-medium text-gray-700">
                {{ dept.name }}
              </span>
              <span class="text-sm text-gray-600">{{ dept.value }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div
                class="bg-blue-600 h-2 rounded-full"
                :style="{
                  width: totalHeadcount > 0 ? `${(dept.value / totalHeadcount) * 100}%` : '0%',
                }"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Hires vs. Departures -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-start justify-between mb-6">
          <div>
            <h3 class="text-lg font-bold text-gray-900">
              Hires vs. Departures
            </h3>
            <p class="text-blue-600 text-2xl font-bold mt-2">
              {{ netHires >= 0 ? '+' : '' }}{{ netHires }}
            </p>
            <p class="text-gray-500 text-sm">net hires (last 6 months)</p>
          </div>
        </div>
        <div class="flex items-end justify-between h-64 gap-2">
          <div v-for="(data, index) in monthlyMovement" :key="index" class="flex-1 flex flex-col items-center gap-2">
            <div class="flex gap-1 h-40 items-end">
              <div
                class="bg-blue-600 rounded-t w-3"
                :style="{ height: maxMovement > 0 ? `${(data.hires / maxMovement) * 100}%` : '0%' }"
              />
              <div
                class="bg-red-600 rounded-t w-3"
                :style="{ height: maxMovement > 0 ? `${(data.departures / maxMovement) * 100}%` : '0%' }"
              />
            </div>
            <span class="text-xs text-gray-600">{{ data.month }}</span>
          </div>
        </div>
        <div class="flex gap-4 justify-center mt-6 text-sm">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 bg-blue-600 rounded" />
            <span>Hires</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 bg-red-600 rounded" />
            <span>Departures</span>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Layout from '@/components/Layout.vue'
import ModuleLockBadge from '@/components/ModuleLockBadge.vue'
import { Lock, Users, LayoutGrid, UserCheck, CalendarDays, PhilippinePeso } from 'lucide-vue-next'
import { useModuleAccess } from '@/composables/useModuleAccess'

interface Stats {
  total_employees: number
  total_departments: number
  attendance_today_pct: number
  pending_leaves: number
  payroll_status: string
}

interface DepartmentHeadcount {
  name: string
  value: number
}

interface MonthlyMovement {
  month: string
  hires: number
  departures: number
}

interface Props {
  stats: Stats
  department_headcount: DepartmentHeadcount[]
  monthly_movement: MonthlyMovement[]
}

const props = defineProps<Props>()

const page = usePage()
const { getEnabledModules } = useModuleAccess()

const timeRange = ref('30days')
const activeTab = ref('hr')

const modules = computed(() => page.props.modules || [])

const departmentHeadcount = computed(() => props.department_headcount ?? [])
const monthlyMovement = computed(() => props.monthly_movement ?? [])

const totalHeadcount = computed(() =>
  departmentHeadcount.value.reduce((sum, d) => sum + d.value, 0),
)

const netHires = computed(() =>
  monthlyMovement.value.reduce((sum, m) => sum + m.hires - m.departures, 0),
)

const maxMovement = computed(() =>
  Math.max(...monthlyMovement.value.flatMap((m) => [m.hires, m.departures]), 1),
)

const kpiCards = computed(() => {
  const enabledModuleCount = modules.value.filter((m: any) => m.enabled).length
  return [
    {
      label: 'Total Employees',
      value: props.stats?.total_employees != null ? props.stats.total_employees.toLocaleString() : '—',
      icon: 'users',
    },
    {
      label: 'Active Modules',
      value: enabledModuleCount.toString(),
      icon: 'modules',
    },
    {
      label: 'Attendance Today',
      value: props.stats?.attendance_today_pct != null ? `${props.stats.attendance_today_pct}%` : '—',
      icon: 'attendance',
    },
    {
      label: 'Pending Leaves',
      value: props.stats?.pending_leaves != null ? props.stats.pending_leaves.toString() : '—',
      icon: 'leaves',
    },
    {
      label: 'Payroll Cycle',
      value: props.stats?.payroll_status ?? '—',
      icon: 'payroll',
    },
  ]
})

const tabs = [
  { id: 'hr', label: 'HR Analytics' },
  { id: 'timekeeping', label: 'Timekeeping' },
  { id: 'payroll', label: 'Payroll' },
]

const getRangeLabel = (range: string) => {
  switch (range) {
    case '7days':
      return 'Last 7 Days'
    case '30days':
      return 'Last 30 Days'
    case '90days':
      return 'Last 90 Days'
    default:
      return range
  }
}
</script>
