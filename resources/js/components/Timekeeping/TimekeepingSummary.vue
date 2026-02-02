<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-gray-600 text-sm font-medium">Attendance Today</p>
          <p class="text-3xl font-bold text-gray-900 mt-2">{{ attendanceToday }}%</p>
        </div>
        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
          <CheckCircle2 class="w-6 h-6 text-green-600" />
        </div>
      </div>
      <p class="text-xs text-gray-500 mt-4">On time today</p>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-gray-600 text-sm font-medium">Hours This Week</p>
          <p class="text-3xl font-bold text-gray-900 mt-2">{{ hoursThisWeek }}</p>
        </div>
        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
          <Clock class="w-6 h-6 text-blue-600" />
        </div>
      </div>
      <p class="text-xs text-gray-500 mt-4">out of 40 hours</p>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-gray-600 text-sm font-medium">Leave Balance</p>
          <p class="text-3xl font-bold text-gray-900 mt-2">{{ leaveBalance }}</p>
        </div>
        <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
          <Calendar class="w-6 h-6 text-amber-600" />
        </div>
      </div>
      <p class="text-xs text-gray-500 mt-4">days remaining</p>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-gray-600 text-sm font-medium">Overtime Hours</p>
          <p class="text-3xl font-bold text-gray-900 mt-2">{{ overtimeHours }}</p>
        </div>
        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
          <TrendingUp class="w-6 h-6 text-purple-600" />
        </div>
      </div>
      <p class="text-xs text-gray-500 mt-4">this month</p>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-gray-600 text-sm font-medium">Current Status</p>
          <p class="text-lg font-bold text-gray-900 mt-2" :class="statusColor">
            {{ currentStatus }}
          </p>
        </div>
        <div class="w-12 h-12 rounded-lg flex items-center justify-center" :class="statusBgColor">
          <Clock class="w-6 h-6" :class="statusIconColor" />
        </div>
      </div>
      <p class="text-xs text-gray-500 mt-4">since {{ clockInTime }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { CheckCircle2, Clock, Calendar, TrendingUp } from 'lucide-vue-next'

// Sample data - in real app would come from API
const attendanceToday = ref(98)
const hoursThisWeek = ref(38)
const leaveBalance = ref(12)
const overtimeHours = ref(4.5)
const clockInTime = ref('08:30 AM')

const currentStatus = computed(() => {
  return 'Clocked In'
})

const statusColor = computed(() => {
  return currentStatus.value === 'Clocked In' ? 'text-green-600' : 'text-gray-600'
})

const statusBgColor = computed(() => {
  return currentStatus.value === 'Clocked In' ? 'bg-green-100' : 'bg-gray-100'
})

const statusIconColor = computed(() => {
  return currentStatus.value === 'Clocked In' ? 'text-green-600' : 'text-gray-600'
})
</script>
