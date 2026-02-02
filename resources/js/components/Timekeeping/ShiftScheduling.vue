<template>
  <div class="space-y-6">
    <!-- Schedule Overview -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Weekly Schedule</h3>
      
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="text-left py-3 px-4 font-medium text-gray-900">Day</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Shift</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Start Time</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">End Time</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Duration</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="schedule in weeklySchedule" :key="schedule.id" class="border-b border-gray-200 hover:bg-gray-50">
              <td class="py-3 px-4 font-medium text-gray-900">{{ schedule.day }}</td>
              <td class="py-3 px-4 text-gray-600">{{ schedule.shift }}</td>
              <td class="py-3 px-4 text-gray-600">{{ schedule.startTime }}</td>
              <td class="py-3 px-4 text-gray-600">{{ schedule.endTime }}</td>
              <td class="py-3 px-4 text-gray-900">{{ schedule.duration }}h</td>
              <td class="py-3 px-4">
                <span :class="[
                  'inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium',
                  schedule.status === 'Scheduled' ? 'bg-blue-100 text-blue-700' :
                  schedule.status === 'Off' ? 'bg-gray-100 text-gray-700' :
                  'bg-green-100 text-green-700'
                ]">
                  <span :class="[
                    'w-2 h-2 rounded-full',
                    schedule.status === 'Scheduled' ? 'bg-blue-600' :
                    schedule.status === 'Off' ? 'bg-gray-600' :
                    'bg-green-600'
                  ]" />
                  {{ schedule.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Shift Swap Requests -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Shift Swap Requests</h3>
      
      <div class="space-y-4">
        <div v-for="swap in shiftSwaps" :key="swap.id" class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
          <div class="flex items-start justify-between">
            <div>
              <p class="font-medium text-gray-900">
                {{ swap.requestedShift }} → {{ swap.swappingWith }}
              </p>
              <p class="text-sm text-gray-600 mt-1">{{ swap.date }}</p>
              <p class="text-sm text-gray-500 mt-1">Requested by: {{ swap.requestedBy }}</p>
            </div>
            <span :class="[
              'inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium',
              swap.status === 'Approved' ? 'bg-green-100 text-green-700' :
              swap.status === 'Pending' ? 'bg-amber-100 text-amber-700' :
              'bg-red-100 text-red-700'
            ]">
              <span :class="[
                'w-2 h-2 rounded-full',
                swap.status === 'Approved' ? 'bg-green-600' :
                swap.status === 'Pending' ? 'bg-amber-600' :
                'bg-red-600'
              ]" />
              {{ swap.status }}
            </span>
          </div>
          <div v-if="swap.status === 'Pending'" class="flex gap-2 mt-4">
            <button class="px-3 py-1 text-sm bg-green-100 text-green-700 rounded hover:bg-green-200 font-medium transition-colors">
              Accept
            </button>
            <button class="px-3 py-1 text-sm bg-red-100 text-red-700 rounded hover:bg-red-200 font-medium transition-colors">
              Decline
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Request Shift Swap -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Request Shift Swap</h3>
      
      <form @submit.prevent="submitShiftSwap" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Your Shift</label>
            <select v-model="shiftSwapForm.yourShift" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              <option value="">Select your shift</option>
              <option value="morning">Morning (8 AM - 4 PM)</option>
              <option value="afternoon">Afternoon (4 PM - 12 AM)</option>
              <option value="night">Night (12 AM - 8 AM)</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
            <input v-model="shiftSwapForm.date" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Preferred Shift</label>
            <select v-model="shiftSwapForm.preferredShift" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              <option value="">Select preferred shift</option>
              <option value="morning">Morning (8 AM - 4 PM)</option>
              <option value="afternoon">Afternoon (4 PM - 12 AM)</option>
              <option value="night">Night (12 AM - 8 AM)</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Swap With Employee</label>
            <input v-model="shiftSwapForm.swapWith" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Employee name or ID" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Reason</label>
          <textarea v-model="shiftSwapForm.reason" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Provide reason for shift swap" />
        </div>

        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors">
          Request Shift Swap
        </button>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const weeklySchedule = ref([
  { id: 1, day: 'Monday', shift: 'Morning', startTime: '08:00 AM', endTime: '04:00 PM', duration: 8, status: 'Scheduled' },
  { id: 2, day: 'Tuesday', shift: 'Morning', startTime: '08:00 AM', endTime: '04:00 PM', duration: 8, status: 'Scheduled' },
  { id: 3, day: 'Wednesday', shift: 'Morning', startTime: '08:00 AM', endTime: '04:00 PM', duration: 8, status: 'Scheduled' },
  { id: 4, day: 'Thursday', shift: 'Afternoon', startTime: '04:00 PM', endTime: '12:00 AM', duration: 8, status: 'Scheduled' },
  { id: 5, day: 'Friday', shift: 'Afternoon', startTime: '04:00 PM', endTime: '12:00 AM', duration: 8, status: 'Scheduled' },
  { id: 6, day: 'Saturday', shift: 'Off', startTime: '-', endTime: '-', duration: 0, status: 'Off' },
  { id: 7, day: 'Sunday', shift: 'Off', startTime: '-', endTime: '-', duration: 0, status: 'Off' }
])

const shiftSwaps = ref([
  {
    id: 1,
    requestedShift: 'Monday Morning',
    swappingWith: 'Tuesday Morning',
    date: 'Dec 23, 2024',
    requestedBy: 'Sarah Johnson',
    status: 'Pending'
  },
  {
    id: 2,
    requestedShift: 'Friday Afternoon',
    swappingWith: 'Saturday Off',
    date: 'Dec 28, 2024',
    requestedBy: 'Mike Chen',
    status: 'Approved'
  }
])

const shiftSwapForm = ref({
  yourShift: '',
  date: '',
  preferredShift: '',
  swapWith: '',
  reason: ''
})

const submitShiftSwap = () => {
  if (!shiftSwapForm.value.yourShift || !shiftSwapForm.value.date || !shiftSwapForm.value.preferredShift) {
    alert('Please fill in all required fields')
    return
  }
  
  console.log('Shift swap requested:', shiftSwapForm.value)
  alert('Shift swap request submitted successfully!')
  shiftSwapForm.value = { yourShift: '', date: '', preferredShift: '', swapWith: '', reason: '' }
}
</script>
