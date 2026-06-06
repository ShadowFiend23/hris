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
        <nav class="flex-1 px-3 py-6 space-y-2 overflow-hidden">
          <template v-for="item in filteredNavigationItems" :key="item.href">
            <Link
              v-if="item.visible !== false && item.enabled"
              :href="item.href"
              :class="[
                'flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-colors relative group',
                isActive(item.href, (item as any).activeOn)
                  ? 'bg-blue-50 text-blue-600'
                  : 'text-gray-700 hover:bg-gray-50',
              ]"
            >
              <component :is="item.icon" :size="20" />
              <span>{{ item.label }}</span>
            </Link>
            <div
              v-else-if="item.visible !== false && !item.enabled"
              :class="[
                'flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-colors relative group',
                'text-gray-400 cursor-not-allowed opacity-50'
              ]"
            >
              <component :is="item.icon" :size="20" />
              <span>{{ item.label }}</span>
              <Lock :size="16" class="ml-auto text-red-500" />
              <!-- Tooltip for locked modules -->
              <div class="absolute left-full ml-2 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                Module Locked
              </div>
            </div>
          </template>
        </nav>
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
      <!-- <div class="bg-white border-b border-gray-200">
        <div class="px-6 py-2">
          <LicenseExpiryNotice />
        </div>
      </div> -->

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
        <div class="flex items-center gap-3">
          <!-- Bell notifications -->
          <div class="relative">
            <button
              @click="toggleNotifications"
              class="relative p-2 text-gray-500 hover:text-gray-900 rounded-lg hover:bg-gray-100 transition-colors"
            >
              <Bell :size="20" />
              <span
                v-if="unreadCount > 0"
                class="absolute top-1 right-1 w-4 h-4 bg-red-500 rounded-full flex items-center justify-center text-white text-[10px] font-bold"
              >{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
            </button>
            <!-- Notification dropdown -->
            <div
              v-if="notificationsOpen"
              class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-gray-200 z-50"
            >
              <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                <span class="font-semibold text-gray-800 text-sm">Notifications</span>
                <span v-if="unreadCount > 0" class="text-xs text-gray-400">{{ unreadCount }} unread</span>
              </div>
              <div class="max-h-72 overflow-y-auto">
                <div v-if="notificationsLoading" class="p-4 text-center text-sm text-gray-400">Loading...</div>
                <div v-else-if="!notifications.length" class="p-6 text-center text-sm text-gray-400">No notifications.</div>
                <div
                  v-else
                  v-for="n in notifications"
                  :key="n.id"
                  class="px-4 py-3 border-b border-gray-50 last:border-0 hover:bg-gray-50 transition-colors"
                >
                  <div class="flex gap-2 items-start">
                    <span :class="notificationIcon(n.type)" class="mt-0.5 shrink-0">
                      <CheckCircle2 v-if="n.type === 'success'" :size="15" />
                      <AlertCircle v-else-if="n.type === 'error'" :size="15" />
                      <AlertTriangle v-else-if="n.type === 'warning'" :size="15" />
                      <Info v-else :size="15" />
                    </span>
                    <div>
                      <p class="text-sm font-medium text-gray-800">{{ n.title }}</p>
                      <p class="text-xs text-gray-500 mt-0.5">{{ n.message }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Profile circle -->
          <div class="relative">
            <button
              @click="toggleProfile"
              class="w-9 h-9 rounded-full bg-blue-600 flex items-center justify-center text-white text-sm font-semibold hover:bg-blue-700 transition-colors"
            >{{ userInitials }}</button>
            <!-- Profile dropdown -->
            <div
              v-if="profileOpen"
              class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-lg border border-gray-200 z-50"
            >
              <div class="px-4 py-3 border-b border-gray-100">
                <p class="font-medium text-sm text-gray-900 truncate">{{ userName }}</p>
                <p class="text-xs text-gray-500 truncate">{{ userEmail }}</p>
              </div>
              <div class="py-1">
                <Link
                  v-if="employeeId"
                  :href="`/employees/${employeeId}`"
                  class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                  @click="profileOpen = false"
                >
                  <UserCircle :size="15" /> My Profile
                </Link>
                <Link
                  href="/settings/profile"
                  class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                  @click="profileOpen = false"
                >
                  <Settings :size="15" /> Settings
                </Link>
                <button
                  @click="logout"
                  class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                >
                  <LogOut :size="15" /> Logout
                </button>
              </div>
            </div>
          </div>
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
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import {
  AlertCircle,
  AlertTriangle,
  BarChart3,
  Bell,
  CheckCircle2,
  Clock,
  CreditCard,
  PhilippinePeso,
  FileText,
  Info,
  Lock,
  LogOut,
  Menu,
  Settings,
  UserCircle,
  Users,
  X,
} from 'lucide-vue-next'
import LicenseExpiryNotice from '@/components/LicenseExpiryNotice.vue'

const sidebarOpen = ref(true);
const page = usePage()

const isAdmin = computed(() => (page.props.auth as any)?.isAdmin === true)
const isEmployee = computed(() => !(page.props.auth as any)?.isAdmin && !(page.props.auth as any)?.isManager)
const employeeId = computed(() => (page.props.auth as any)?.employeeId ?? null)
const userName = computed(() => (page.props.auth as any)?.user?.name ?? 'User')
const userEmail = computed(() => (page.props.auth as any)?.user?.email ?? '')
const userInitials = computed(() => {
  const name = userName.value
  const parts = name.trim().split(' ')
  return parts.length >= 2
    ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
    : name.slice(0, 2).toUpperCase()
})

// Notifications
const notificationsOpen = ref(false)
const notificationsLoading = ref(false)
const notifications = ref<any[]>([])
const unreadCount = computed(() => notifications.value.filter((n) => !n.read).length)

const fetchNotifications = async () => {
  notificationsLoading.value = true
  try {
    const res = await fetch('/api/core/notifications', {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
    if (res.ok) {
      const json = await res.json()
      notifications.value = json.data ?? []
    }
  } catch (e) {
    console.error('Failed to load notifications:', e)
  } finally {
    notificationsLoading.value = false
  }
}

const toggleNotifications = () => {
  notificationsOpen.value = !notificationsOpen.value
  profileOpen.value = false
  if (notificationsOpen.value) {
    fetchNotifications()
  }
}

const notificationIcon = (type: string) => {
  const map: Record<string, string> = {
    success: 'text-green-500',
    error: 'text-red-500',
    warning: 'text-amber-500',
    info: 'text-blue-500',
  }
  return map[type] ?? 'text-gray-400'
}

// Profile
const profileOpen = ref(false)

const toggleProfile = () => {
  profileOpen.value = !profileOpen.value
  notificationsOpen.value = false
}

const logout = () => {
  router.post('/logout')
}

// Close dropdowns when clicking outside
const handleOutsideClick = (e: MouseEvent) => {
  const target = e.target as HTMLElement
  if (!target.closest('.relative')) {
    notificationsOpen.value = false
    profileOpen.value = false
  }
}

onMounted(() => document.addEventListener('click', handleOutsideClick))
onUnmounted(() => document.removeEventListener('click', handleOutsideClick))

const baseNavigationItems = [
  { label: 'Dashboard', href: '/', icon: BarChart3, moduleCode: 'hris' },
  { label: 'Employees', href: '/employees', icon: Users, moduleCode: 'hris' },
  { label: 'Timekeeping', href: '/timekeeping', icon: Clock, moduleCode: 'timekeeping' },
  { label: 'Payroll', href: '/payroll', icon: PhilippinePeso, moduleCode: 'payroll' },
  { label: 'My Payslips', href: '/payroll/my-payslips', icon: FileText, moduleCode: 'payroll', employeeOnly: true },
  { label: 'Loans', href: '/loans', icon: CreditCard, moduleCode: 'payroll', activeOn: ['/loans'], loansOnly: true },
  { label: 'HR Settings', href: '/hr-settings/leave-types', icon: Settings, moduleCode: 'timekeeping', adminOnly: true, activeOn: ['/hr-settings'] },
  { label: 'Licenses', href: '/license/licenses', icon: Lock, moduleCode: null },
]

const filteredNavigationItems = computed(() => {
  const modules = page.props.modules || []
  const loansEnabled = (page.props as any).loansEnabled !== false

  // If no modules passed, default all to enabled (for pages that don't pass modules)
  if (modules.length === 0) {
    return baseNavigationItems
      .filter(item => {
        if (item.label === 'Licenses') return isAdmin.value
        if ((item as any).adminOnly) return isAdmin.value
        if ((item as any).employeeOnly) return isEmployee.value
        if ((item as any).loansOnly && !loansEnabled) return false
        if (item.label === 'Employees' && isEmployee.value) return false
        if (item.label === 'Payroll' && isEmployee.value) return false
        return true
      })
      .map(item => ({ ...item, visible: true, enabled: true }))
  }

  return baseNavigationItems.map(item => {
    if (!item.moduleCode) {
      // Licenses: admin only — hide entirely for non-admins
      if (item.label === 'Licenses') {
        return { ...item, visible: isAdmin.value, enabled: isAdmin.value }
      }
      return { ...item, visible: true, enabled: true }
    }

    // Admin-only items (HR Settings etc.)
    if ((item as any).adminOnly && !isAdmin.value) {
      return { ...item, visible: false, enabled: false }
    }

    // Loans hidden when loans are disabled for the company
    if ((item as any).loansOnly && !loansEnabled) {
      return { ...item, visible: false, enabled: false }
    }

    const moduleEnabled = modules.some((m: any) => m.code === item.moduleCode && m.enabled)
    // Employees nav and admin Payroll dashboard hidden for employee role
    if (item.label === 'Employees' && isEmployee.value) {
      return { ...item, visible: false, enabled: false }
    }
    if (item.label === 'Payroll' && isEmployee.value) {
      return { ...item, visible: false, enabled: false }
    }
    // My Payslips visible only to employees
    if ((item as any).employeeOnly && !isEmployee.value) {
      return { ...item, visible: false, enabled: false }
    }
    return { ...item, visible: true, enabled: moduleEnabled }
  })
})

const isActive = (href: string, activeOn?: string[]): boolean => {
  const currentPath = page.url.split('?')[0] // Remove query params

  if (activeOn) {
    return activeOn.some(prefix => currentPath === prefix || currentPath.startsWith(prefix + '/'))
  }

  if (href === '/') {
    // Dashboard is active on exact match or /dashboard
    return currentPath === '/' || currentPath === '/dashboard'
  }

  // For other routes, check if current path starts with the href
  return currentPath === href || currentPath.startsWith(href + '/')
}
</script>
