<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
      <div class="flex items-center justify-between mb-5">
        <h3 class="text-lg font-semibold text-gray-900">Adjust Attendance</h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <X class="w-5 h-5" />
        </button>
      </div>

      <p class="text-sm text-gray-600 mb-4">
        Adjusting attendance for <span class="font-medium text-gray-900">{{ member.name }}</span>
      </p>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Clock In <span class="text-red-500">*</span></label>
          <input
            v-model="form.clock_in"
            type="datetime-local"
            required
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <p v-if="errors.clock_in" class="text-xs text-red-600 mt-1">{{ errors.clock_in }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Clock Out</label>
          <input
            v-model="form.clock_out"
            type="datetime-local"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <p v-if="errors.clock_out" class="text-xs text-red-600 mt-1">{{ errors.clock_out }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Reason <span class="text-red-500">*</span></label>
          <textarea
            v-model="form.adjustment_reason"
            rows="3"
            required
            placeholder="Explain the reason for this adjustment..."
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
          />
          <p v-if="errors.adjustment_reason" class="text-xs text-red-600 mt-1">{{ errors.adjustment_reason }}</p>
        </div>

        <div v-if="serverError" class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2">{{ serverError }}</div>

        <div class="flex gap-3 pt-2">
          <button
            type="button"
            @click="$emit('close')"
            class="flex-1 border border-gray-300 text-gray-700 rounded-lg py-2 text-sm font-medium hover:bg-gray-50 transition-colors"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="submitting"
            class="flex-1 bg-blue-600 text-white rounded-lg py-2 text-sm font-medium hover:bg-blue-700 transition-colors disabled:opacity-50"
          >
            {{ submitting ? 'Saving...' : 'Save Adjustment' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { X } from 'lucide-vue-next'

interface TeamMember {
  employee_id: number
  name: string
  attendance_record_id: number | null
  clock_in: string | null
  clock_out: string | null
}

const props = defineProps<{ member: TeamMember }>()
const emit = defineEmits<{ close: []; adjusted: [] }>()

const toDatetimeLocal = (val: string | null) => {
  if (!val) return ''
  return new Date(val).toISOString().slice(0, 16)
}

const form = ref({
  clock_in: toDatetimeLocal(props.member.clock_in),
  clock_out: toDatetimeLocal(props.member.clock_out),
  adjustment_reason: '',
})

const errors = ref<Record<string, string>>({})
const serverError = ref('')
const submitting = ref(false)

const submit = async () => {
  errors.value = {}
  serverError.value = ''
  submitting.value = true

  try {
    const res = await fetch(`/api/timekeeping/attendance/${props.member.attendance_record_id}/adjust`, {
      method: 'PATCH',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
      },
      credentials: 'same-origin',
      body: JSON.stringify(form.value),
    })

    if (res.status === 422) {
      const json = await res.json()
      errors.value = Object.fromEntries(
        Object.entries(json.errors ?? {}).map(([k, v]) => [k, (v as string[])[0]]),
      )
      return
    }

    if (!res.ok) {
      const json = await res.json().catch(() => ({}))
      serverError.value = json.message ?? 'An error occurred. Please try again.'
      return
    }

    emit('adjusted')
  } catch (e) {
    serverError.value = 'Network error. Please try again.'
  } finally {
    submitting.value = false
  }
}
</script>
