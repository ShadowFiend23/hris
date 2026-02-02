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

    <!-- Module Status Section -->
    <div class="mb-8 p-6 bg-white rounded-lg border border-gray-200">
      <h2 class="text-lg font-bold text-gray-900 mb-4">Available Modules</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div 
          v-for="module in modules" 
          :key="module.code"
          :class="[
            'p-4 rounded-lg border-2 transition-all',
            module.enabled 
              ? 'border-green-200 bg-green-50' 
              : 'border-gray-200 bg-gray-50 opacity-60'
          ]"
        >
          <div class="flex items-start justify-between mb-2">
            <div>
              <h3 class="font-semibold text-gray-900">{{ module.name }}</h3>
              <p class="text-sm text-gray-600">{{ module.code }}</p>
            </div>
            <span v-if="module.enabled" class="bg-green-200 text-green-800 text-xs px-2 py-1 rounded-full">Active</span>
            <span v-else class="bg-red-200 text-red-800 text-xs px-2 py-1 rounded-full flex items-center gap-1">
              <Lock :size="12" /> Locked
            </span>
          </div>
          <ModuleLockBadge :moduleName="module.name" :isLocked="!module.enabled" />
        </div>
      </div>
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
          <span class="text-3xl">{{ card.icon }}</span>
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
          <h4 class="text-2xl font-bold text-gray-900">310</h4>
          <p class="text-green-600 text-sm font-medium">+2.5%</p>
        </div>
        <div class="space-y-3">
          <div v-for="(dept, index) in departmentData" :key="index">
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
                  width: `${(dept.value / 150) * 100}%`,
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
            <p class="text-blue-600 text-2xl font-bold mt-2">+15</p>
            <p class="text-green-600 text-sm font-medium">+5.8%</p>
          </div>
        </div>
        <div class="flex items-end justify-between h-64 gap-2">
          <div v-for="(data, index) in hiresVsDeparturesData" :key="index" class="flex-1 flex flex-col items-center gap-2">
            <div class="flex gap-1 h-40 items-end">
              <div
                class="bg-blue-600 rounded-t w-3"
                :style="{ height: `${(data.hires / 20) * 100}%` }"
              />
              <div
                class="bg-red-600 rounded-t w-3"
                :style="{ height: `${(data.departures / 20) * 100}%` }"
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
import { Lock } from 'lucide-vue-next'
import { useModuleAccess } from '@/composables/useModuleAccess'
import { useApi } from '@/composables/useApi'

const { request } = useApi()
const page = usePage()
const { getEnabledModules } = useModuleAccess()

const timeRange = ref('30days')
const activeTab = ref('hr')

const modules = computed(() => page.props.modules || [])

const kpiCards = computed(() => {
  const enabledModuleCount = modules.value.filter((m: any) => m.enabled).length
  return [
    {
      label: 'Total Employees',
      value: '1,250',
      icon: 'í±¥',
    },
    {
      label: 'Active Modules',
      value: enabledModuleCount.toString(),
      icon: 'í³¦',
    },
    {
      label: 'Attendance Today',
      value: '98.2%',
      change: '+1.2%',
      icon: 'í³Š',
    },
    {
      label: 'Pending Leaves',
      value: '12',
      icon: 'í³',
    },
    {
      label: 'Payroll Cycle',
      value: 'In Progress',
      icon: 'í²°',
    },
  ]
})

const tabs = [
  { id: 'hr', label: 'HR Analytics' },
  { id: 'timekeeping', label: 'Timekeeping' },
  { id: 'payroll', label: 'Payroll' },
]

const hiresVsDeparturesData = [
  { month: 'Jan', hires: 10, departures: 5 },
  { month: 'Feb', hires: 15, departures: 8 },
  { month: 'Mar', hires: 12, departures: 6 },
  { month: 'Apr', hires: 18, departures: 7 },
  { month: 'May', hires: 20, departures: 10 },
  { month: 'Jun', hires: 16, departures: 9 },
]

const departmentData = [
  { name: 'SALES', value: 85 },
  { name: 'ENG', value: 120 },
  { name: 'MKTG', value: 45 },
  { name: 'OPS', value: 65 },
  { name: 'HR', value: 15 },
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

// Example function to fetch dashboard data from backend
const fetchDashboardData = async () => {
  try {
    // const data = await request('/dashboard')
    // Update state with data
    console.log('Dashboard data would be fetched here')
  } catch (err) {
    console.error('Failed to fetch dashboard data:', err)
  }
}
</script>
