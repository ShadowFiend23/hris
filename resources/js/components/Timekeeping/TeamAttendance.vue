<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-semibold text-gray-900">Team Attendance</h2>
      <button
        @click="fetchTeamAttendance"
        class="text-sm text-blue-600 hover:underline"
      >
        Refresh
      </button>
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 5" :key="i" class="h-14 bg-gray-100 rounded animate-pulse" />
    </div>

    <!-- Empty state -->
    <div v-else-if="!team.length" class="text-center py-16 text-gray-400">
      <Users class="w-12 h-12 mx-auto mb-3 opacity-40" />
      <p class="font-medium">No direct reports found.</p>
      <p class="text-sm mt-1">Make sure employees are assigned to you as their supervisor.</p>
    </div>

    <!-- Table -->
    <div v-else class="overflow-x-auto rounded-lg border border-gray-200">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clock In</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clock Out</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Hours</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="member in team" :key="member.employee_id" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <div class="font-medium text-gray-900 text-sm">{{ member.name }}</div>
              <div class="text-xs text-gray-500">{{ member.department }}</div>
            </td>
            <td class="px-4 py-3">
              <span :class="statusBadgeClass(member.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                {{ formatStatus(member.status) }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ formatTime(member.clock_in) }}</td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ formatTime(member.clock_out) }}</td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ member.total_hours != null ? `${Number(member.total_hours).toFixed(2)}h` : '—' }}</td>
            <td class="px-4 py-3">
              <button
                v-if="member.attendance_record_id"
                @click="openAdjust(member)"
                class="text-xs text-blue-600 hover:underline"
              >
                Adjust
              </button>
              <span v-else class="text-xs text-gray-400">No record</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Adjust Modal -->
    <AdjustAttendanceModal
      v-if="adjustTarget"
      :member="adjustTarget"
      @close="adjustTarget = null"
      @adjusted="onAdjusted"
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { Users } from 'lucide-vue-next'
import AdjustAttendanceModal from './AdjustAttendanceModal.vue'

interface TeamMember {
  employee_id: number
  employee_code: string
  name: string
  department: string | null
  position: string | null
  attendance_record_id: number | null
  clock_in: string | null
  clock_out: string | null
  total_hours: number | null
  status: string
  adjusted_by: number | null
  adjusted_at: string | null
  adjustment_reason: string | null
}

const team = ref<TeamMember[]>([])
const loading = ref(true)
const adjustTarget = ref<TeamMember | null>(null)

const fetchTeamAttendance = async () => {
  loading.value = true
  try {
    const res = await fetch('/api/timekeeping/attendance/team', {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
    if (res.ok) {
      const json = await res.json()
      team.value = json.data ?? []
    }
  } catch (e) {
    console.error('Failed to load team attendance:', e)
  } finally {
    loading.value = false
  }
}

const openAdjust = (member: TeamMember) => {
  adjustTarget.value = member
}

const onAdjusted = () => {
  adjustTarget.value = null
  fetchTeamAttendance()
}

const formatTime = (datetime: string | null) => {
  if (!datetime) return '—'
  return new Date(datetime).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

const formatStatus = (status: string) => {
  const map: Record<string, string> = {
    present: 'Present',
    late: 'Late',
    absent: 'Absent',
    half_day: 'Half Day',
    on_leave: 'On Leave',
    no_record: 'No Record',
  }
  return map[status] ?? status
}

const statusBadgeClass = (status: string) => {
  const map: Record<string, string> = {
    present: 'bg-green-100 text-green-700',
    late: 'bg-amber-100 text-amber-700',
    absent: 'bg-red-100 text-red-700',
    half_day: 'bg-blue-100 text-blue-700',
    on_leave: 'bg-purple-100 text-purple-700',
    no_record: 'bg-gray-100 text-gray-600',
  }
  return map[status] ?? 'bg-gray-100 text-gray-600'
}

onMounted(fetchTeamAttendance)
</script>
