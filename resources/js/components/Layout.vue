<template>
  <div class="flex h-screen bg-gray-50">
    <!-- Sidebar -->
    <div
      :class="[
        'fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-gray-200 transition-transform md:relative md:translate-x-0',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
      ]"
    >
      <div class="flex flex-col h-full">
        <!-- Logo Section -->
        <div class="flex items-center gap-2 px-6 py-4 border-b border-gray-200">
          <div class="w-8 h-8 bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg flex items-center justify-center text-white font-bold">
            HR
          </div>
          <div class="flex-1">
            <div class="text-sm font-semibold text-gray-900">HR Core</div>
            <div class="text-xs text-gray-500">Management System</div>
          </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-3 py-6 space-y-2 overflow-y-auto">
          <Link
            v-for="item in filteredNavigationItems"
            :key="item.href"
            :href="item.href"
            :class="[
              'flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-colors relative group',
              isActive(item.href)
                ? 'bg-blue-50 text-blue-600'
                : 'text-gray-700 hover:bg-gray-50',
              !item.enabled ? 'opacity-50 cursor-not-allowed' : ''
            ]"
            :disabled="!item.enabled"
          >
            <component :is="item.icon" :size="20" />
            <span>{{ item.label }}</span>
            <Lock v-if="!item.enabled" :size="16" class="ml-auto text-red-500" />
            <!-- Tooltip for locked modules -->
            <div v-if="!item.enabled" class="absolute left-full ml-2 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
              Module Locked
            </div>
          </Link>
        </nav>

        <!-- Footer -->
        <div class="border-t border-gray-200 p-4">
          <div class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-gray-50">
            <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-amber-500 rounded-full flex items-center justify-center text-white font-semibold text-sm">
              HR
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-sm font-medium text-gray-900 truncate">
                HR Admin
              </div>
              <div class="text-xs text-gray-500 truncate">
                admin@company.com
              </div>
            </div>
          </div>
          <div class="space-y-2 mt-4">
            <button class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg">
              Settings
            </button>
            <button class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg">
              Logout
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Mobile sidebar backdrop -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-30 bg-black/50 md:hidden"
      @click="sidebarOpen = false"
    />

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- License Expiry Notice -->
      <div class="bg-white border-b border-gray-200">
        <div class="px-6 py-2">
          <LicenseExpiryNotice />
        </div>
      </div>

      <!-- Header -->
      <header class="bg-white border-b border-gray-200 px-6 py-[calc(0.34rem*4)] flex items-center justify-between">
        <button
          @click="sidebarOpen = !sidebarOpen"
          class="md:hidden text-gray-700"
        >
          <Menu v-if="!sidebarOpen" :size="24" />
          <X v-else :size="24" />
        </button>
        <div class="flex-1" />
        <div class="flex items-center gap-4">
          <button class="text-gray-600 hover:text-gray-900">
            Ì¥î
          </button>
          <button class="text-gray-600 hover:text-gray-900">
            ‚öôÔ∏è
          </button>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 overflow-auto">
        <div class="p-6 md:p-8">
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Menu, X, BarChart3, Users, Clock, DollarSign, Lock } from 'lucide-vue-next'
import LicenseExpiryNotice from '@/components/LicenseExpiryNotice.vue'

const sidebarOpen = ref(true);
const page = usePage()

const baseNavigationItems = [
  { label: 'Dashboard', href: '/', icon: BarChart3, moduleCode: 'hris' },
  { label: 'Employees', href: '/employees', icon: Users, moduleCode: 'hris' },
  { label: 'Timekeeping', href: '/timekeeping', icon: Clock, moduleCode: 'timekeeping' },
  { label: 'Payroll', href: '/payroll', icon: DollarSign, moduleCode: 'payroll' },
  { label: 'Licenses', href: '/license/licenses', icon: Lock, moduleCode: null },
]

const filteredNavigationItems = computed(() => {
  const modules = page.props.modules || []
  
  return baseNavigationItems.map(item => {
    if (!item.moduleCode) {
      // Always show Licenses link
      return { ...item, enabled: true }
    }
    
    const moduleEnabled = modules.some((m: any) => m.code === item.moduleCode && m.enabled)
    return { ...item, enabled: moduleEnabled }
  })
})

const isActive = computed(() =>{
  return (href: string) => page.url === href
})
</script>
