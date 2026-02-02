<template>
  <div class="space-y-6">
    <!-- Report Filters -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Generate Reports</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
          <select v-model="reportFilters.type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="attendance">Attendance Report</option>
            <option value="overtime">Overtime Report</option>
            <option value="leave">Leave Report</option>
            <option value="summary">Summary Report</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Period</label>
          <select v-model="reportFilters.period" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
            <option value="yearly">Yearly</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
          <input v-model="reportFilters.startDate" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
          <input v-model="reportFilters.endDate" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
        </div>
      </div>

      <div class="flex gap-2">
        <button @click="generateReport" class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors">
          Generate Report
        </button>
        <button @click="downloadReport" class="px-6 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors flex items-center gap-2">
          <Download :size="18" />
          Download
        </button>
      </div>
    </div>

    <!-- Attendance Report -->
    <div v-if="activeReport === 'attendance'" class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Attendance Report</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-blue-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Days Present</p>
          <p class="text-2xl font-bold text-blue-600">18</p>
        </div>
        <div class="bg-amber-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Days Late</p>
          <p class="text-2xl font-bold text-amber-600">2</p>
        </div>
        <div class="bg-red-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Days Absent</p>
          <p class="text-2xl font-bold text-red-600">1</p>
        </div>
        <div class="bg-green-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Total Hours</p>
          <p class="text-2xl font-bold text-green-600">152h</p>
        </div>
        <div class="bg-purple-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Attendance %</p>
          <p class="text-2xl font-bold text-purple-600">94.7%</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="text-left py-3 px-4 font-medium text-gray-900">Date</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Clock In</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Clock Out</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Hours</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="record in attendanceData" :key="record.id" class="border-b border-gray-200 hover:bg-gray-50">
              <td class="py-3 px-4 text-gray-900">{{ record.date }}</td>
              <td class="py-3 px-4 text-gray-600">{{ record.clockIn }}</td>
              <td class="py-3 px-4 text-gray-600">{{ record.clockOut }}</td>
              <td class="py-3 px-4 text-gray-900">{{ record.hours }}h</td>
              <td class="py-3 px-4">
                <span :class="[
                  'inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium',
                  record.status === 'Present' ? 'bg-green-100 text-green-700' :
                  record.status === 'Late' ? 'bg-amber-100 text-amber-700' :
                  'bg-red-100 text-red-700'
                ]">
                  {{ record.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Overtime Report -->
    <div v-if="activeReport === 'overtime'" class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Overtime Report</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Total Overtime</p>
          <p class="text-2xl font-bold text-blue-600">52h</p>
        </div>
        <div class="bg-green-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Approved</p>
          <p class="text-2xl font-bold text-green-600">48h</p>
        </div>
        <div class="bg-amber-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Pending</p>
          <p class="text-2xl font-bold text-amber-600">4h</p>
        </div>
        <div class="bg-purple-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Est. Compensation</p>
          <p class="text-2xl font-bold text-purple-600">$1,440</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="text-left py-3 px-4 font-medium text-gray-900">Date</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Hours</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Type</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Reason</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Status</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Compensation</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="record in overtimeData" :key="record.id" class="border-b border-gray-200 hover:bg-gray-50">
              <td class="py-3 px-4 text-gray-900">{{ record.date }}</td>
              <td class="py-3 px-4 text-gray-900 font-medium">{{ record.hours }}h</td>
              <td class="py-3 px-4 text-gray-600">{{ record.type }}</td>
              <td class="py-3 px-4 text-gray-600">{{ record.reason }}</td>
              <td class="py-3 px-4">
                <span :class="[
                  'inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium',
                  record.status === 'Approved' ? 'bg-green-100 text-green-700' :
                  record.status === 'Pending' ? 'bg-amber-100 text-amber-700' :
                  'bg-red-100 text-red-700'
                ]">
                  {{ record.status }}
                </span>
              </td>
              <td class="py-3 px-4 text-gray-900">{{ record.compensation }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Leave Report -->
    <div v-if="activeReport === 'leave'" class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Leave Report</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Total Allocated</p>
          <p class="text-2xl font-bold text-blue-600">30</p>
          <p class="text-xs text-gray-500 mt-1">days</p>
        </div>
        <div class="bg-orange-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Used</p>
          <p class="text-2xl font-bold text-orange-600">15</p>
          <p class="text-xs text-gray-500 mt-1">days</p>
        </div>
        <div class="bg-green-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Remaining</p>
          <p class="text-2xl font-bold text-green-600">15</p>
          <p class="text-xs text-gray-500 mt-1">days</p>
        </div>
        <div class="bg-purple-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Approval Rate</p>
          <p class="text-2xl font-bold text-purple-600">100%</p>
          <p class="text-xs text-gray-500 mt-1">approved</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="text-left py-3 px-4 font-medium text-gray-900">Type</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Allocated</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Used</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Remaining</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Approval Rate</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="leave in leaveData" :key="leave.type" class="border-b border-gray-200 hover:bg-gray-50">
              <td class="py-3 px-4 text-gray-900 font-medium">{{ leave.type }}</td>
              <td class="py-3 px-4 text-gray-600">{{ leave.allocated }}</td>
              <td class="py-3 px-4 text-gray-600">{{ leave.used }}</td>
              <td class="py-3 px-4 text-gray-900">{{ leave.remaining }}</td>
              <td class="py-3 px-4">
                <span :class="[
                  'inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium',
                  leave.approvalRate === '100%' ? 'bg-green-100 text-green-700' :
                  'bg-amber-100 text-amber-700'
                ]">
                  {{ leave.approvalRate }}
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
import { Download } from 'lucide-vue-next'

const activeReport = ref('attendance')

const reportFilters = ref({
  type: 'attendance',
  period: 'monthly',
  startDate: '',
  endDate: ''
})

const attendanceData = ref([
  { id: 1, date: 'Dec 20, 2024', clockIn: '08:30 AM', clockOut: '05:15 PM', hours: 8.75, status: 'Present' },
  { id: 2, date: 'Dec 19, 2024', clockIn: '08:15 AM', clockOut: '05:00 PM', hours: 8.75, status: 'Present' },
  { id: 3, date: 'Dec 18, 2024', clockIn: '08:45 AM', clockOut: '05:30 PM', hours: 8.75, status: 'Late' },
  { id: 4, date: 'Dec 17, 2024', clockIn: '08:00 AM', clockOut: '05:00 PM', hours: 9, status: 'Present' },
  { id: 5, date: 'Dec 16, 2024', clockIn: '-', clockOut: '-', hours: 0, status: 'Absent' },
])

const overtimeData = ref([
  {
    id: 1,
    date: 'Dec 18, 2024',
    hours: 2,
    type: 'Weekday',
    reason: 'Critical bug fix',
    status: 'Approved',
    compensation: '$60'
  },
  {
    id: 2,
    date: 'Dec 17, 2024',
    hours: 3,
    type: 'Weekday',
    reason: 'Project deadline',
    status: 'Approved',
    compensation: '$90'
  },
  {
    id: 3,
    date: 'Dec 14, 2024',
    hours: 2,
    type: 'Weekend',
    reason: 'Server maintenance',
    status: 'Pending',
    compensation: '$80'
  },
])

const leaveData = ref([
  { type: 'Vacation', allocated: 20, used: 8, remaining: 12, approvalRate: '100%' },
  { type: 'Sick Leave', allocated: 10, used: 2, remaining: 8, approvalRate: '100%' },
  { type: 'Personal', allocated: 5, used: 3, remaining: 2, approvalRate: '100%' },
  { type: 'Maternity', allocated: 90, used: 0, remaining: 90, approvalRate: '0%' },
])

const generateReport = () => {
  activeReport.value = reportFilters.value.type
  console.log('Report generated with filters:', reportFilters.value)
}

const downloadReport = () => {
  console.log('Downloading report:', reportFilters.value)
  alert('Report downloaded as PDF')
}
</script>
