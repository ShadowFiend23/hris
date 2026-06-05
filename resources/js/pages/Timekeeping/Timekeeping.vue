<template>
  <Layout>
    <div class="w-full">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Timekeeping</h1>
        <p class="text-gray-600">
          {{ isAdmin ? 'Manage employee attendance, leave approvals, shifts, and overtime.' : 'Manage attendance, leave, schedules, and overtime' }}
        </p>
      </div>

      <!-- Summary Cards -->
      <TimekeepingSummary />

      <!-- Tabs for different sections -->
      <div class="mt-8">
        <div class="border-b border-gray-200">
          <nav class="flex gap-8" aria-label="Tabs">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                'px-1 py-4 font-medium border-b-2 transition-colors',
                activeTab === tab.id
                  ? 'border-blue-600 text-blue-600'
                  : 'border-transparent text-gray-600 hover:text-gray-900'
              ]"
            >
              {{ tab.label }}
            </button>
          </nav>
        </div>

        <!-- Tab Content -->
        <div class="mt-6 w-full">
          <template v-if="activeTab === 'attendance'">
            <AdminAttendance v-if="isAdmin" />
            <AttendanceTracking v-else />
          </template>
          <template v-else-if="activeTab === 'leave'">
            <AdminLeaveManagement v-if="isAdmin" />
            <LeaveManagement v-else />
          </template>
          <template v-else-if="activeTab === 'shift'">
            <AdminShiftScheduling v-if="isAdmin" />
            <ShiftScheduling v-else />
          </template>
          <template v-else-if="activeTab === 'overtime'">
            <AdminOvertimeManagement v-if="isAdmin" />
            <OvertimeManagement v-else />
          </template>
          <TimekeepingReports v-else-if="activeTab === 'reports'" />
          <TeamAttendance v-else-if="activeTab === 'team'" />
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Layout from '@/components/Layout.vue'
import TimekeepingSummary from '@/components/Timekeeping/TimekeepingSummary.vue'
import AttendanceTracking from '@/components/Timekeeping/AttendanceTracking.vue'
import LeaveManagement from '@/components/Timekeeping/LeaveManagement.vue'
import ShiftScheduling from '@/components/Timekeeping/ShiftScheduling.vue'
import OvertimeManagement from '@/components/Timekeeping/OvertimeManagement.vue'
import TimekeepingReports from '@/components/Timekeeping/TimekeepingReports.vue'
import TeamAttendance from '@/components/Timekeeping/TeamAttendance.vue'
import AdminAttendance from '@/components/Timekeeping/AdminAttendance.vue'
import AdminLeaveManagement from '@/components/Timekeeping/AdminLeaveManagement.vue'
import AdminShiftScheduling from '@/components/Timekeeping/AdminShiftScheduling.vue'
import AdminOvertimeManagement from '@/components/Timekeeping/AdminOvertimeManagement.vue'

const page = usePage()
const isAdmin = computed(() => (page.props.auth as any)?.isAdmin === true)
const isManager = computed(() => (page.props.auth as any)?.isManager === true)
const showTeamTab = computed(() => isManager.value && !isAdmin.value)

const activeTab = ref('attendance')

const tabs = computed(() => {
  const base = [
    { id: 'attendance', label: 'Attendance' },
    { id: 'leave', label: 'Leave Management' },
    { id: 'shift', label: 'Shift Scheduling' },
    { id: 'overtime', label: 'Overtime' },
    { id: 'reports', label: 'Reports' },
  ]
  // Managers (non-admin) get a Team tab for their direct reports
  if (showTeamTab.value) {
    base.push({ id: 'team', label: 'Team' })
  }
  return base
})
</script>
