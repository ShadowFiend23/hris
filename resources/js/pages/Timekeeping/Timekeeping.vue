<template>
  <Layout>
    <div class="w-full">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Timekeeping</h1>
        <p class="text-gray-600">Manage attendance, leave, schedules, and overtime</p>
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
          <AttendanceTracking v-if="activeTab === 'attendance'" />
          <LeaveManagement v-else-if="activeTab === 'leave'" />
          <ShiftScheduling v-else-if="activeTab === 'shift'" />
          <OvertimeManagement v-else-if="activeTab === 'overtime'" />
          <TimekeepingReports v-else-if="activeTab === 'reports'" />
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import Layout from '@/components/Layout.vue'
import TimekeepingSummary from '@/components/Timekeeping/TimekeepingSummary.vue'
import AttendanceTracking from '@/components/Timekeeping/AttendanceTracking.vue'
import LeaveManagement from '@/components/Timekeeping/LeaveManagement.vue'
import ShiftScheduling from '@/components/Timekeeping/ShiftScheduling.vue'
import OvertimeManagement from '@/components/Timekeeping/OvertimeManagement.vue'
import TimekeepingReports from '@/components/Timekeeping/TimekeepingReports.vue'

const activeTab = ref('attendance')

const tabs = [
  { id: 'attendance', label: 'Attendance' },
  { id: 'leave', label: 'Leave Management' },
  { id: 'shift', label: 'Shift Scheduling' },
  { id: 'overtime', label: 'Overtime' },
  { id: 'reports', label: 'Reports' },
]
</script>
