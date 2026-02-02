<template>
  <div class="space-y-6">
    <!-- Leave Balance Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div v-for="leave in leaveTypes" :key="leave.type" class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-sm font-medium text-gray-600 mb-2">{{ leave.type }}</p>
        <div class="flex items-baseline gap-2">
          <span class="text-2xl font-bold text-gray-900">{{ leave.balance }}</span>
          <span class="text-sm text-gray-500">/ {{ leave.total }} days</span>
        </div>
        <div class="mt-3 w-full bg-gray-200 rounded-full h-2">
          <div 
            class="h-2 rounded-full transition-all"
            :class="leave.color"
            :style="{ width: `${(leave.balance / leave.total) * 100}%` }"
          />
        </div>
      </div>
    </div>

    <!-- New Leave Request -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Request New Leave</h3>
      
      <form @submit.prevent="submitLeaveRequest" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Leave Type</label>
            <select v-model="leaveForm.type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              <option value="">Select leave type</option>
              <option value="vacation">Vacation</option>
              <option value="sick">Sick Leave</option>
              <option value="personal">Personal Leave</option>
              <option value="maternity">Maternity Leave</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Number of Days</label>
            <input v-model.number="leaveForm.days" type="number" min="1" max="30" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="1" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
            <input v-model="leaveForm.startDate" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
            <input v-model="leaveForm.endDate" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Reason</label>
          <textarea v-model="leaveForm.reason" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Provide reason for leave request" />
        </div>

        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors">
          Submit Leave Request
        </button>
      </form>
    </div>

    <!-- Leave Requests History -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Leave Requests</h3>
      
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="text-left py-3 px-4 font-medium text-gray-900">Type</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Start Date</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">End Date</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Days</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Status</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Approved By</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="request in leaveRequests" :key="request.id" class="border-b border-gray-200 hover:bg-gray-50">
              <td class="py-3 px-4 text-gray-900 font-medium">{{ request.type }}</td>
              <td class="py-3 px-4 text-gray-600">{{ request.startDate }}</td>
              <td class="py-3 px-4 text-gray-600">{{ request.endDate }}</td>
              <td class="py-3 px-4 text-gray-900">{{ request.days }}</td>
              <td class="py-3 px-4">
                <span :class="[
                  'inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium',
                  request.status === 'Approved' ? 'bg-green-100 text-green-700' :
                  request.status === 'Pending' ? 'bg-amber-100 text-amber-700' :
                  'bg-red-100 text-red-700'
                ]">
                  <span :class="[
                    'w-2 h-2 rounded-full',
                    request.status === 'Approved' ? 'bg-green-600' :
                    request.status === 'Pending' ? 'bg-amber-600' :
                    'bg-red-600'
                  ]" />
                  {{ request.status }}
                </span>
              </td>
              <td class="py-3 px-4 text-gray-600">{{ request.approvedBy || '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const leaveForm = ref({
  type: '',
  days: 1,
  startDate: '',
  endDate: '',
  reason: ''
})

const leaveTypes = ref([
  { type: 'Vacation', balance: 12, total: 20, color: 'bg-blue-500' },
  { type: 'Sick Leave', balance: 5, total: 10, color: 'bg-red-500' },
  { type: 'Personal', balance: 3, total: 5, color: 'bg-amber-500' },
  { type: 'Maternity', balance: 30, total: 90, color: 'bg-pink-500' }
])

const leaveRequests = ref([
  {
    id: 1,
    type: 'Vacation',
    startDate: 'Dec 23, 2024',
    endDate: 'Dec 27, 2024',
    days: 5,
    status: 'Approved',
    approvedBy: 'John Manager'
  },
  {
    id: 2,
    type: 'Sick Leave',
    startDate: 'Dec 15, 2024',
    endDate: 'Dec 15, 2024',
    days: 1,
    status: 'Approved',
    approvedBy: 'John Manager'
  },
  {
    id: 3,
    type: 'Personal',
    startDate: 'Jan 5, 2025',
    endDate: 'Jan 6, 2025',
    days: 2,
    status: 'Pending',
    approvedBy: ''
  }
])

const submitLeaveRequest = () => {
  if (!leaveForm.value.type || !leaveForm.value.startDate || !leaveForm.value.endDate) {
    alert('Please fill in all required fields')
    return
  }
  
  // In a real app, this would call an API
  console.log('Leave request submitted:', leaveForm.value)
  alert('Leave request submitted successfully!')
  leaveForm.value = { type: '', days: 1, startDate: '', endDate: '', reason: '' }
}
</script>
