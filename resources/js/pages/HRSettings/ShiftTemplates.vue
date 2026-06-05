<template>
  <Layout>
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900">HR Settings</h1>
      <p class="mt-1 text-gray-600">Manage leave types, shift templates, and other HR configurations.</p>
    </div>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200">
      <nav class="flex gap-6">
        <Link
          href="/hr-settings/leave-types"
          class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700"
        >
          Leave Types
        </Link>
        <Link
          href="/hr-settings/payroll"
          class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700"
        >
          Payroll Settings
        </Link>
        <Link
          href="/hr-settings/allowance-types"
          class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700"
        >
          Allowance Types
        </Link>
        <Link
          href="/hr-settings/shift-templates"
          class="border-b-2 border-blue-600 pb-3 text-sm font-medium text-blue-600"
        >
          Shift Templates
        </Link>
        <Link
          href="/hr-settings/holidays"
          class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700"
        >
          Holidays
        </Link>
        <Link
          href="/hr-settings/loan-types"
          class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700"
        >
          Loan Types
        </Link>
      </nav>
    </div>

    <!-- Section header -->
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-900">Shift Templates</h2>
        <p class="text-gray-600 mt-1">Configure work shift schedules including lunch-break windows for split-shift DTR tracking</p>
      </div>
      <button
        class="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors flex items-center gap-2"
        @click="showAddForm = true"
      >
        <Plus :size="20" />
        Add Shift Template
      </button>
    </div>

    <!-- Flash message -->
    <div v-if="flashSuccess" class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2">
      <CheckCircle :size="18" class="text-green-600 shrink-0" />
      {{ flashSuccess }}
    </div>

    <!-- Add Form -->
    <div v-if="showAddForm" class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">New Shift Template</h2>
      <form class="grid grid-cols-1 md:grid-cols-3 gap-4" @submit.prevent="submitAdd">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
          <input
            v-model="addForm.name"
            type="text"
            placeholder="e.g. Day Shift"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
            required
          />
          <p v-if="addForm.errors.name" class="mt-1 text-xs text-red-600">{{ addForm.errors.name }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
          <input
            v-model="addForm.start_time"
            type="time"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
            required
          />
          <p v-if="addForm.errors.start_time" class="mt-1 text-xs text-red-600">{{ addForm.errors.start_time }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
          <input
            v-model="addForm.end_time"
            type="time"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
            required
          />
          <p v-if="addForm.errors.end_time" class="mt-1 text-xs text-red-600">{{ addForm.errors.end_time }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Break Duration (minutes)</label>
          <input
            v-model.number="addForm.break_duration"
            type="number"
            min="0"
            max="180"
            placeholder="e.g. 60"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Break Start Time
            <span class="text-gray-400 text-xs">(for split-shift DTR)</span>
          </label>
          <input
            v-model="addForm.break_start_time"
            type="time"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
          />
          <p v-if="addForm.errors.break_start_time" class="mt-1 text-xs text-red-600">{{ addForm.errors.break_start_time }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Break End Time
            <span class="text-gray-400 text-xs">(for split-shift DTR)</span>
          </label>
          <input
            v-model="addForm.break_end_time"
            type="time"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
          />
          <p v-if="addForm.errors.break_end_time" class="mt-1 text-xs text-red-600">{{ addForm.errors.break_end_time }}</p>
        </div>

        <div class="md:col-span-3 flex gap-3 justify-end">
          <button type="button" class="px-4 py-2 text-gray-600 hover:text-gray-900 font-medium" @click="cancelAdd">
            Cancel
          </button>
          <button
            type="submit"
            :disabled="addForm.processing"
            class="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors"
          >
            {{ addForm.processing ? 'Saving...' : 'Save Shift Template' }}
          </button>
        </div>
      </form>
    </div>

    <!-- Shift Templates Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Break</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Break Window</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-if="props.shiftTemplates.length === 0">
            <td colspan="7" class="px-6 py-12 text-center text-gray-400">No shift templates configured yet.</td>
          </tr>
          <tr v-for="st in props.shiftTemplates" :key="st.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 font-medium text-gray-900">
              <span v-if="editingId !== st.id">{{ st.name }}</span>
              <input v-else v-model="editForm.name" class="border border-gray-300 rounded px-2 py-1 text-sm w-full" />
            </td>
            <td class="px-6 py-4 text-gray-600">
              <span v-if="editingId !== st.id">{{ formatTime(st.start_time) }}</span>
              <input v-else v-model="editForm.start_time" type="time" class="border border-gray-300 rounded px-2 py-1 text-sm" />
            </td>
            <td class="px-6 py-4 text-gray-600">
              <span v-if="editingId !== st.id">{{ formatTime(st.end_time) }}</span>
              <input v-else v-model="editForm.end_time" type="time" class="border border-gray-300 rounded px-2 py-1 text-sm" />
            </td>
            <td class="px-6 py-4 text-gray-600">
              <span v-if="editingId !== st.id">{{ st.break_duration ? st.break_duration + ' min' : '—' }}</span>
              <input v-else v-model.number="editForm.break_duration" type="number" min="0" max="180" class="border border-gray-300 rounded px-2 py-1 text-sm w-20" />
            </td>
            <td class="px-6 py-4 text-gray-600">
              <span v-if="editingId !== st.id">
                <span v-if="st.break_start_time && st.break_end_time" class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded-full">
                  {{ formatTime(st.break_start_time) }} – {{ formatTime(st.break_end_time) }}
                </span>
                <span v-else class="text-gray-400">—</span>
              </span>
              <div v-else class="flex items-center gap-1">
                <input v-model="editForm.break_start_time" type="time" class="border border-gray-300 rounded px-2 py-1 text-sm w-28" />
                <span class="text-gray-400">–</span>
                <input v-model="editForm.break_end_time" type="time" class="border border-gray-300 rounded px-2 py-1 text-sm w-28" />
              </div>
            </td>
            <td class="px-6 py-4">
              <span
                :class="st.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                class="text-xs px-2 py-1 rounded-full font-medium"
              >
                {{ st.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-6 py-4 text-right">
              <div v-if="editingId !== st.id" class="flex items-center justify-end gap-2">
                <button class="text-blue-600 hover:text-blue-800 p-1 rounded" title="Edit" @click="startEdit(st)">
                  <Pencil :size="16" />
                </button>
                <button
                  v-if="st.is_active"
                  class="text-red-500 hover:text-red-700 p-1 rounded"
                  title="Deactivate"
                  @click="confirmDeactivate(st)"
                >
                  <Trash2 :size="16" />
                </button>
              </div>
              <div v-else class="flex items-center justify-end gap-2">
                <button class="text-green-600 hover:text-green-800 p-1 rounded" title="Save" @click="submitEdit(st.id)">
                  <CheckCircle :size="16" />
                </button>
                <button class="text-gray-500 hover:text-gray-700 p-1 rounded" title="Cancel" @click="cancelEdit">
                  <X :size="16" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Deactivate Confirmation -->
    <div
      v-if="deactivatingTemplate"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
    >
      <div class="bg-white rounded-xl p-6 max-w-sm w-full mx-4 shadow-xl">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Deactivate Shift Template</h3>
        <p class="text-gray-600 mb-6">
          Are you sure you want to deactivate <strong>{{ deactivatingTemplate.name }}</strong>?
          Employees currently assigned to this shift will not be affected immediately.
        </p>
        <div class="flex gap-3 justify-end">
          <button class="px-4 py-2 text-gray-600 hover:text-gray-900 font-medium" @click="deactivatingTemplate = null">
            Cancel
          </button>
          <button
            :disabled="destroyForm.processing"
            class="bg-red-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-red-700 disabled:opacity-50"
            @click="submitDeactivate"
          >
            Deactivate
          </button>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import Layout from '@/components/Layout.vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { CheckCircle, Pencil, Plus, Trash2, X } from 'lucide-vue-next'
import { computed, ref } from 'vue'

interface ShiftTemplate {
  id: number
  name: string
  start_time: string
  end_time: string
  break_duration: number | null
  break_start_time: string | null
  break_end_time: string | null
  is_active: boolean
}

const props = defineProps<{
  shiftTemplates: ShiftTemplate[]
}>()

const page = usePage()
const flashSuccess = computed(() => (page.props.flash as any)?.success ?? null)

function formatTime(time: string | null): string {
  if (!time) return '—'
  // Time may come as "HH:MM:SS" or "HH:MM"
  return time.substring(0, 5)
}

// Add form
const showAddForm = ref(false)
const addForm = useForm({
  name: '',
  start_time: '',
  end_time: '',
  break_duration: null as number | null,
  break_start_time: '',
  break_end_time: '',
})

function cancelAdd(): void {
  showAddForm.value = false
  addForm.reset()
  addForm.clearErrors()
}

function submitAdd(): void {
  addForm.post(route('hr-settings.shift-templates.store'), {
    onSuccess: () => {
      showAddForm.value = false
      addForm.reset()
    },
  })
}

// Edit inline
const editingId = ref<number | null>(null)
const editForm = useForm({
  name: '',
  start_time: '',
  end_time: '',
  break_duration: null as number | null,
  break_start_time: '',
  break_end_time: '',
  is_active: true,
})

function startEdit(st: ShiftTemplate): void {
  editingId.value = st.id
  editForm.name = st.name
  editForm.start_time = st.start_time ? st.start_time.substring(0, 5) : ''
  editForm.end_time = st.end_time ? st.end_time.substring(0, 5) : ''
  editForm.break_duration = st.break_duration
  editForm.break_start_time = st.break_start_time ? st.break_start_time.substring(0, 5) : ''
  editForm.break_end_time = st.break_end_time ? st.break_end_time.substring(0, 5) : ''
  editForm.is_active = st.is_active
}

function cancelEdit(): void {
  editingId.value = null
  editForm.clearErrors()
}

function submitEdit(id: number): void {
  editForm.put(route('hr-settings.shift-templates.update', id), {
    onSuccess: () => {
      editingId.value = null
    },
  })
}

// Deactivate
const deactivatingTemplate = ref<ShiftTemplate | null>(null)
const destroyForm = useForm({})

function confirmDeactivate(st: ShiftTemplate): void {
  deactivatingTemplate.value = st
}

function submitDeactivate(): void {
  if (!deactivatingTemplate.value) return
  destroyForm.delete(route('hr-settings.shift-templates.destroy', deactivatingTemplate.value.id), {
    onSuccess: () => {
      deactivatingTemplate.value = null
    },
  })
}
</script>
