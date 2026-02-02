<template>
  <div class="space-y-6">
    <!-- Overtime Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <p class="text-gray-600 text-sm font-medium">Overtime This Month</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">{{ overtimeThisMonth }}h</p>
        <p class="text-xs text-gray-500 mt-2">4 sessions</p>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <p class="text-gray-600 text-sm font-medium">Overtime This Year</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">{{ overtimeThisYear }}h</p>
        <p class="text-xs text-gray-500 mt-2">across 32 sessions</p>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <p class="text-gray-600 text-sm font-medium">Pending Approval</p>
        <p class="text-3xl font-bold text-amber-600 mt-2">{{ pendingApproval }}h</p>
        <p class="text-xs text-gray-500 mt-2">2 pending requests</p>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <p class="text-gray-600 text-sm font-medium">Approved & Paid</p>
        <p class="text-3xl font-bold text-green-600 mt-2">{{ approvedPaid }}h</p>
        <p class="text-xs text-gray-500 mt-2">this year</p>
      </div>
    </div>

    <!-- Request Overtime -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Request Overtime</h3>
      
      <form @submit.prevent="submitOvertimeRequest" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
            <input v-model="overtimeForm.date" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Hours</label>
            <input v-model.number="overtimeForm.hours" type="number" min="0.5" max="12" step="0.5" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="0" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
            <select v-model="overtimeForm.type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              <option value="">Select type</option>
              <option value="weekday">Weekday</option>
              <option value="weekend">Weekend</option>
              <option value="holiday">Holiday</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Reason/Project</label>
          <textarea v-model="overtimeForm.reason" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Describe the reason for overtime" />
        </div>

        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors">
          Submit Overtime Request
        </button>
      </form>
    </div>

    <!-- Overtime History -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Overtime History</h3>
      
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="text-left py-3 px-4 font-medium text-gray-900">Date</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Hours</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Type</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Reason</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Status</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Pay Rate</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="record in overtimeHistory" :key="record.id" class="border-b border-gray-200 hover:bg-gray-50">
              <td class="py-3 px-4 text-gray-900">{{ record.date }}</td>
              <td class="py-3 px-4 text-gray-900 font-medium">{{ record.hours }}h</td>
              <td class="py-3 px-4 text-gray-600">{{ record.type }}</td>
              <td class="py-3 px-4 text-gray-600">{{ record.reason }}</td>
              <td class="py-3 px-4">
                <span :class="[
                  'inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium',
                  record.status === 'Approved' ? 'bg-green-100 text-green-700' :
                  record.status === 'Pending' ? 'bg-amber-100 text-amber-700' :
                  'bg-red-100 text-red-700'
                ]">
                  <span :class="[
                    'w-2 h-2 rounded-full',
                    record.status === 'Approved' ? 'bg-green-600' :
                    record.status === 'Pending' ? 'bg-amber-600' :
                    'bg-red-600'
                  ]" />
                  {{ record.status }}
                </span>
              </td>
              <td class="py-3 px-4 text-gray-900">{{ record.payRate }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const overtimeThisMonth = ref(8.5)
const overtimeThisYear = ref(52)
const pendingApproval = ref(4)
const approvedPaid = ref(48)

const overtimeForm = ref({
  date: '',
  hours: 0,
  type: '',
  reason: ''
})

const overtimeHistory = ref([
  {
    id: 1,
    date: 'Dec 18, 2024',
    hours: 2,
    type: 'Weekday',
    reason: 'Critical bug fix',
    status: 'Approved',
    payRate: '1.5x'
  },
  {
    id: 2,
    date: 'Dec 17, 2024',
    hours: 3,
    type: 'Weekday',
    reason: 'Project deadline',
    status: 'Approved',
    payRate: '1.5x'
  },
  {
    id: 3,
    date: 'Dec 14, 2024',
    hours: 2,
    type: 'Weekend',
    reason: 'Server maintenance',
    status: 'Pending',
    payRate: '2x'
  },
  {
    id: 4,
    date: 'Dec 10, 2024',
    hours: 1.5,
    type: 'Weekday',
    reason: 'Feature implementation',
    status: 'Approved',
    payRate: '1.5x'
  },
  {
    id: 5,
    date: 'Dec 8, 2024',
    hours: 4,
    type: 'Weekend',
    reason: 'System upgrade',
    status: 'Pending',
    payRate: '2x'
  },
  {
    id: 6,
    date: 'Dec 5, 2024',
    hours: 2,
    type: 'Weekday',
    reason: 'Client meeting',
    status: 'Rejected',
    payRate: '-'
  }
])

const submitOvertimeRequest = () => {
  if (!overtimeForm.value.date || !overtimeForm.value.hours || !overtimeForm.value.type) {
    alert('Please fill in all required fields')
    return
  }
  
  console.log('Overtime request submitted:', overtimeForm.value)
  alert('Overtime request submitted successfully!')
  overtimeForm.value = { date: '', hours: 0, type: '', reason: '' }
}
</script>
