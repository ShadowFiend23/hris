<template>
  <div class="space-y-6">
    <!-- Clock In/Out Section -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Today's Attendance</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="flex flex-col items-center p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg">
          <Clock class="w-8 h-8 text-blue-600 mb-3" />
          <p class="text-sm text-gray-600 mb-2">Clock In</p>
          <p class="text-2xl font-bold text-gray-900">{{ clockInTime }}</p>
          <p class="text-xs text-gray-500 mt-2">08:30 AM</p>
        </div>

        <div class="flex flex-col items-center p-6 bg-gradient-to-br from-amber-50 to-amber-100 rounded-lg">
          <Clock class="w-8 h-8 text-amber-600 mb-3" />
          <p class="text-sm text-gray-600 mb-2">Break Time</p>
          <p class="text-2xl font-bold text-gray-900">{{ breakTime }}</p>
          <p class="text-xs text-gray-500 mt-2">1 hour</p>
        </div>

        <div class="flex flex-col items-center p-6 bg-gradient-to-br from-green-50 to-green-100 rounded-lg">
          <Clock class="w-8 h-8 text-green-600 mb-3" />
          <p class="text-sm text-gray-600 mb-2">Hours Worked</p>
          <p class="text-2xl font-bold text-gray-900">{{ hoursWorked }}</p>
          <p class="text-xs text-gray-500 mt-2">so far today</p>
        </div>
      </div>

      <div class="mt-6 flex gap-3">
        <button class="flex-1 px-4 py-3 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors flex items-center justify-center gap-2">
          <CheckCircle2 :size="18" />
          Clock In
        </button>
        <button class="flex-1 px-4 py-3 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition-colors flex items-center justify-center gap-2">
          <XCircle :size="18" />
          Clock Out
        </button>
      </div>
    </div>

    <!-- Attendance History -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Attendance History</h3>
      
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="text-left py-3 px-4 font-medium text-gray-900">Date</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Clock In</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Clock Out</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Hours Worked</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="record in attendanceHistory" :key="record.id" class="border-b border-gray-200 hover:bg-gray-50">
              <td class="py-3 px-4 text-gray-900">{{ record.date }}</td>
              <td class="py-3 px-4 text-gray-600">{{ record.clockIn }}</td>
              <td class="py-3 px-4 text-gray-600">{{ record.clockOut }}</td>
              <td class="py-3 px-4 text-gray-900 font-medium">{{ record.hoursWorked }}h</td>
              <td class="py-3 px-4">
                <span :class="[
                  'inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium',
                  record.status === 'Present' ? 'bg-green-100 text-green-700' :
                  record.status === 'Late' ? 'bg-amber-100 text-amber-700' :
                  'bg-red-100 text-red-700'
                ]">
                  <span :class="[
                    'w-2 h-2 rounded-full',
                    record.status === 'Present' ? 'bg-green-600' :
                    record.status === 'Late' ? 'bg-amber-600' :
                    'bg-red-600'
                  ]" />
                  {{ record.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Clock, CheckCircle2, XCircle } from 'lucide-vue-next'

const clockInTime = ref('08:30 AM')
const breakTime = ref('1h')
const hoursWorked = ref('8.5h')

const attendanceHistory = ref([
  {
    id: 1,
    date: 'Dec 20, 2024',
    clockIn: '08:30 AM',
    clockOut: '05:15 PM',
    hoursWorked: 8.75,
    status: 'Present'
  },
  {
    id: 2,
    date: 'Dec 19, 2024',
    clockIn: '08:15 AM',
    clockOut: '05:00 PM',
    hoursWorked: 8.75,
    status: 'Present'
  },
  {
    id: 3,
    date: 'Dec 18, 2024',
    clockIn: '08:45 AM',
    clockOut: '05:30 PM',
    hoursWorked: 8.75,
    status: 'Late'
  },
  {
    id: 4,
    date: 'Dec 17, 2024',
    clockIn: '08:00 AM',
    clockOut: '05:00 PM',
    hoursWorked: 9,
    status: 'Present'
  },
  {
    id: 5,
    date: 'Dec 16, 2024',
    clockIn: '-',
    clockOut: '-',
    hoursWorked: 0,
    status: 'Absent'
  },
])
</script>
