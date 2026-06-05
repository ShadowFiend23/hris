<template>
  <Layout>
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900">HR Settings</h1>
      <p class="mt-1 text-gray-600">Manage leave types, payroll schedules, and other HR configurations.</p>
    </div>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200">
      <nav class="flex gap-6">
        <Link
          href="/hr-settings/leave-types"
          class="border-b-2 border-blue-600 pb-3 text-sm font-medium text-blue-600"
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
          class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700"
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

    <!-- Tab: Leave Types header -->
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-900">Leave Types</h2>
        <p class="text-gray-600 mt-1">Manage paid leave types for your organization</p>
      </div>
      <button
        class="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors flex items-center gap-2"
        @click="showAddForm = true"
      >
        <Plus :size="20" />
        Add Leave Type
      </button>
    </div>

    <!-- Flash message -->
    <div v-if="flashSuccess" class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2">
      <CheckCircle :size="18" class="text-green-600 shrink-0" />
      {{ flashSuccess }}
    </div>

    <!-- Add Form -->
    <div v-if="showAddForm" class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">New Leave Type</h2>
      <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="submitAdd">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
          <input
            v-model="addForm.name"
            type="text"
            placeholder="e.g. Vacation Leave"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
            required
          />
          <p v-if="addErrors.name" class="mt-1 text-xs text-red-600">{{ addErrors.name }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
          <input
            v-model="addForm.code"
            type="text"
            placeholder="e.g. VL"
            maxlength="10"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
            required
          />
          <p v-if="addErrors.code" class="mt-1 text-xs text-red-600">{{ addErrors.code }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Days Per Year</label>
          <input
            v-model.number="addForm.days_per_year"
            type="number"
            min="1"
            max="365"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
            required
          />
          <p v-if="addErrors.days_per_year" class="mt-1 text-xs text-red-600">{{ addErrors.days_per_year }}</p>
        </div>
        <div class="flex items-center gap-6 pt-6">
          <label class="flex items-center gap-2 cursor-pointer">
            <input v-model="addForm.is_paid" type="checkbox" class="rounded" />
            <span class="text-sm text-gray-700">Paid Leave</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input v-model="addForm.requires_approval" type="checkbox" class="rounded" />
            <span class="text-sm text-gray-700">Requires Approval</span>
          </label>
        </div>
        <div class="md:col-span-2 flex gap-3 justify-end">
          <button type="button" class="px-4 py-2 text-gray-600 hover:text-gray-900 font-medium" @click="showAddForm = false">
            Cancel
          </button>
          <button
            type="submit"
            :disabled="addForm.processing"
            class="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors"
          >
            {{ addForm.processing ? 'Saving...' : 'Save Leave Type' }}
          </button>
        </div>
      </form>
    </div>

    <!-- Leave Types Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days/Year</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approval</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-if="props.leaveTypes.length === 0">
            <td colspan="7" class="px-6 py-12 text-center text-gray-400">No leave types configured yet.</td>
          </tr>
          <tr v-for="lt in props.leaveTypes" :key="lt.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 font-medium text-gray-900">
              <span v-if="editingId !== lt.id">{{ lt.name }}</span>
              <input
                v-else
                v-model="editForm.name"
                class="border border-gray-300 rounded px-2 py-1 text-sm w-full"
              />
            </td>
            <td class="px-6 py-4 text-gray-600">
              <span v-if="editingId !== lt.id">{{ lt.code }}</span>
              <input v-else v-model="editForm.code" maxlength="10" class="border border-gray-300 rounded px-2 py-1 text-sm w-20" />
            </td>
            <td class="px-6 py-4 text-gray-600">
              <span v-if="editingId !== lt.id">{{ lt.days_per_year }}</span>
              <input v-else v-model.number="editForm.days_per_year" type="number" min="1" max="365" class="border border-gray-300 rounded px-2 py-1 text-sm w-20" />
            </td>
            <td class="px-6 py-4">
              <span v-if="editingId !== lt.id" :class="lt.is_paid ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'" class="text-xs px-2 py-1 rounded-full font-medium">
                {{ lt.is_paid ? 'Paid' : 'Unpaid' }}
              </span>
              <select v-else v-model="editForm.is_paid" class="border border-gray-300 rounded px-2 py-1 text-sm">
                <option :value="true">Paid</option>
                <option :value="false">Unpaid</option>
              </select>
            </td>
            <td class="px-6 py-4">
              <span v-if="editingId !== lt.id" :class="lt.requires_approval ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'" class="text-xs px-2 py-1 rounded-full font-medium">
                {{ lt.requires_approval ? 'Required' : 'Auto' }}
              </span>
              <select v-else v-model="editForm.requires_approval" class="border border-gray-300 rounded px-2 py-1 text-sm">
                <option :value="true">Required</option>
                <option :value="false">Auto</option>
              </select>
            </td>
            <td class="px-6 py-4">
              <span v-if="editingId !== lt.id" :class="lt.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="text-xs px-2 py-1 rounded-full font-medium">
                {{ lt.is_active ? 'Active' : 'Inactive' }}
              </span>
              <select v-else v-model="editForm.is_active" class="border border-gray-300 rounded px-2 py-1 text-sm">
                <option :value="true">Active</option>
                <option :value="false">Inactive</option>
              </select>
            </td>
            <td class="px-6 py-4 text-right">
              <div v-if="editingId !== lt.id" class="flex items-center justify-end gap-2">
                <button
                  class="text-blue-600 hover:text-blue-800 p-1 rounded"
                  title="Edit"
                  @click="startEdit(lt)"
                >
                  <Pencil :size="16" />
                </button>
                <button
                  v-if="lt.is_active"
                  class="text-red-500 hover:text-red-700 p-1 rounded"
                  title="Deactivate"
                  @click="confirmDeactivate(lt)"
                >
                  <Trash2 :size="16" />
                </button>
              </div>
              <div v-else class="flex items-center justify-end gap-2">
                <button class="text-green-600 hover:text-green-800 p-1 rounded" title="Save" @click="submitEdit(lt.id)">
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

    <!-- Deactivate Confirmation Modal -->
    <div v-if="deactivatingLeaveType" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
      <div class="bg-white rounded-xl shadow-xl p-6 max-w-md w-full mx-4">
        <div class="flex items-start gap-4">
          <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center shrink-0">
            <AlertTriangle class="w-5 h-5 text-red-600" />
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Deactivate Leave Type</h3>
            <p class="text-sm text-gray-600 mt-1">
              Are you sure you want to deactivate <strong>{{ deactivatingLeaveType.name }}</strong>?
              Existing balances will be preserved. Employees won't be able to request this leave type.
            </p>
          </div>
        </div>
        <div class="flex gap-3 justify-end mt-6">
          <button class="px-4 py-2 text-gray-600 hover:text-gray-900 font-medium" @click="deactivatingLeaveType = null">
            Cancel
          </button>
          <button
            :disabled="destroyForm.processing"
            class="bg-red-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-red-700 disabled:opacity-50"
            @click="submitDeactivate"
          >
            {{ destroyForm.processing ? 'Deactivating...' : 'Deactivate' }}
          </button>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { AlertTriangle, CheckCircle, Pencil, Plus, Trash2, X } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import Layout from '@/components/Layout.vue'

interface LeaveType {
  id: number
  name: string
  code: string
  days_per_year: number
  is_paid: boolean
  requires_approval: boolean
  is_active: boolean
}

const props = defineProps<{
  leaveTypes: LeaveType[]
}>()

const page = usePage()
const flashSuccess = computed(() => (page.props.flash as any)?.success ?? null)

// Add form
const showAddForm = ref(false)
const addForm = useForm({
  name: '',
  code: '',
  days_per_year: 15,
  is_paid: true,
  requires_approval: true,
})
const addErrors = computed(() => addForm.errors)

function submitAdd(): void {
  addForm.post(route('hr-settings.leave-types.store'), {
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
  code: '',
  days_per_year: 15,
  is_paid: true,
  requires_approval: true,
  is_active: true,
})

function startEdit(lt: LeaveType): void {
  editingId.value = lt.id
  editForm.name = lt.name
  editForm.code = lt.code
  editForm.days_per_year = lt.days_per_year
  editForm.is_paid = lt.is_paid
  editForm.requires_approval = lt.requires_approval
  editForm.is_active = lt.is_active
}

function cancelEdit(): void {
  editingId.value = null
  editForm.clearErrors()
}

function submitEdit(id: number): void {
  editForm.put(route('hr-settings.leave-types.update', id), {
    onSuccess: () => {
      editingId.value = null
    },
  })
}

// Deactivate
const deactivatingLeaveType = ref<LeaveType | null>(null)
const destroyForm = useForm({})

function confirmDeactivate(lt: LeaveType): void {
  deactivatingLeaveType.value = lt
}

function submitDeactivate(): void {
  if (!deactivatingLeaveType.value) return
  destroyForm.delete(route('hr-settings.leave-types.destroy', deactivatingLeaveType.value.id), {
    onSuccess: () => {
      deactivatingLeaveType.value = null
    },
  })
}
</script>
