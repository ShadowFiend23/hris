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
          class="border-b-2 border-blue-600 pb-3 text-sm font-medium text-blue-600"
        >
          Loan Types
        </Link>
      </nav>
    </div>

    <!-- Tab header -->
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-900">Loan Types</h2>
        <p class="mt-1 text-gray-600">
          Define loan types available for employees. The three default types (SSS, Pag-IBIG, Company) are available for all companies.
        </p>
      </div>
      <button
        class="flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2 font-medium text-white transition-colors hover:bg-blue-700"
        @click="showAddForm = true"
      >
        <Plus :size="20" />
        Add Loan Type
      </button>
    </div>

    <!-- Flash message -->
    <div
      v-if="flashSuccess"
      class="mb-6 flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800"
    >
      <CheckCircle :size="18" class="shrink-0 text-green-600" />
      {{ flashSuccess }}
    </div>

    <!-- Add Form -->
    <div v-if="showAddForm" class="mb-6 rounded-lg border border-gray-200 bg-white p-6">
      <h2 class="mb-4 text-lg font-semibold text-gray-900">New Loan Type</h2>
      <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="submitAdd">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Name</label>
          <input
            v-model="addForm.name"
            type="text"
            placeholder="e.g. Calamity Loan"
            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            required
          />
          <p v-if="addErrors.name" class="mt-1 text-xs text-red-600">{{ addErrors.name }}</p>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">
            Code
            <span class="font-normal text-gray-500">(lowercase, underscores only)</span>
          </label>
          <input
            v-model="addForm.code"
            type="text"
            placeholder="e.g. calamity_loan"
            maxlength="20"
            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            required
          />
          <p v-if="addErrors.code" class="mt-1 text-xs text-red-600">{{ addErrors.code }}</p>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">
            Max Amount (₱)
            <span class="font-normal text-gray-500">(optional)</span>
          </label>
          <input
            v-model.number="addForm.max_amount"
            type="number"
            min="0"
            step="0.01"
            placeholder="e.g. 50000"
            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <p v-if="addErrors.max_amount" class="mt-1 text-xs text-red-600">{{ addErrors.max_amount }}</p>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">
            Description
            <span class="font-normal text-gray-500">(optional)</span>
          </label>
          <input
            v-model="addForm.description"
            type="text"
            placeholder="Brief description"
            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <p v-if="addErrors.description" class="mt-1 text-xs text-red-600">{{ addErrors.description }}</p>
        </div>
        <div class="flex justify-end gap-3 md:col-span-2">
          <button
            type="button"
            class="px-4 py-2 font-medium text-gray-600 hover:text-gray-900"
            @click="showAddForm = false"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="addForm.processing"
            class="rounded-lg bg-blue-600 px-6 py-2 font-medium text-white transition-colors hover:bg-blue-700 disabled:opacity-50"
          >
            {{ addForm.processing ? 'Saving...' : 'Save Loan Type' }}
          </button>
        </div>
      </form>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
      <table class="w-full text-sm">
        <thead class="border-b border-gray-200 bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Code</th>
            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Max Amount</th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Description</th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Scope</th>
            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-if="props.loanTypes.length === 0">
            <td colspan="6" class="px-6 py-12 text-center text-gray-400">No loan types configured yet.</td>
          </tr>
          <tr v-for="lt in props.loanTypes" :key="lt.id" class="transition-colors hover:bg-gray-50">
            <td class="px-6 py-4 font-medium text-gray-900">
              <span v-if="editingId !== lt.id">{{ lt.name }}</span>
              <input
                v-else
                v-model="editForm.name"
                class="w-full rounded border border-gray-300 px-2 py-1 text-sm"
              />
            </td>
            <td class="px-6 py-4 text-gray-500">
              <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs">{{ lt.code }}</code>
            </td>
            <td class="px-6 py-4 text-right text-gray-700">
              <span v-if="editingId !== lt.id">{{ lt.max_amount !== null ? formatCurrency(lt.max_amount) : '—' }}</span>
              <input
                v-else
                v-model.number="editForm.max_amount"
                type="number"
                min="0"
                step="0.01"
                class="w-28 rounded border border-gray-300 px-2 py-1 text-right text-sm"
              />
            </td>
            <td class="px-6 py-4 text-gray-500">
              <span v-if="editingId !== lt.id">{{ lt.description ?? '—' }}</span>
              <input
                v-else
                v-model="editForm.description"
                type="text"
                class="w-full rounded border border-gray-300 px-2 py-1 text-sm"
              />
            </td>
            <td class="px-6 py-4">
              <span
                :class="lt.company_id ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'"
                class="rounded-full px-2 py-0.5 text-xs font-medium"
              >
                {{ lt.company_id ? 'Company' : 'Default' }}
              </span>
            </td>
            <td class="px-6 py-4 text-right">
              <div v-if="lt.company_id" class="flex items-center justify-end gap-2">
                <template v-if="editingId !== lt.id">
                  <button class="rounded p-1 text-blue-600 hover:text-blue-800" title="Edit" @click="startEdit(lt)">
                    <Pencil :size="16" />
                  </button>
                  <button class="rounded p-1 text-red-500 hover:text-red-700" title="Remove" @click="deactivatingType = lt">
                    <Trash2 :size="16" />
                  </button>
                </template>
                <template v-else>
                  <button class="rounded p-1 text-green-600 hover:text-green-800" title="Save" @click="submitEdit(lt.id)">
                    <CheckCircle :size="16" />
                  </button>
                  <button class="rounded p-1 text-gray-500 hover:text-gray-700" title="Cancel" @click="cancelEdit">
                    <X :size="16" />
                  </button>
                </template>
              </div>
              <span v-else class="text-xs text-gray-400">Default</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Deactivate Confirm Modal -->
    <div v-if="deactivatingType" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
      <div class="mx-4 w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
        <div class="flex items-start gap-4">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
            <AlertTriangle class="h-5 w-5 text-red-600" />
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Remove Loan Type</h3>
            <p class="mt-1 text-sm text-gray-600">
              Remove <strong>{{ deactivatingType.name }}</strong>? It will no longer appear in the loan type dropdown.
              Existing employee loans will be preserved.
            </p>
          </div>
        </div>
        <div class="mt-6 flex justify-end gap-3">
          <button class="px-4 py-2 font-medium text-gray-600 hover:text-gray-900" @click="deactivatingType = null">
            Cancel
          </button>
          <button
            :disabled="destroyForm.processing"
            class="rounded-lg bg-red-600 px-4 py-2 font-medium text-white hover:bg-red-700 disabled:opacity-50"
            @click="submitDeactivate"
          >
            {{ destroyForm.processing ? 'Removing...' : 'Remove' }}
          </button>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { AlertTriangle, CheckCircle, Pencil, Plus, Trash2, X } from 'lucide-vue-next'
import Layout from '@/components/Layout.vue'

interface LoanTypeItem {
  id: number
  code: string
  name: string
  description: string | null
  max_amount: number | null
  is_active: boolean
  company_id: number | null
}

const props = defineProps<{
  loanTypes: LoanTypeItem[]
}>()

const page = usePage()
const flashSuccess = computed(() => (page.props.flash as any)?.success ?? null)

const showAddForm = ref(false)
const addForm = useForm({
  name: '',
  code: '',
  description: '',
  max_amount: null as number | null,
})
const addErrors = ref<Record<string, string>>({})

const submitAdd = () => {
  addErrors.value = {}
  addForm.post('/hr-settings/loan-types', {
    onSuccess: () => {
      showAddForm.value = false
      addForm.reset()
    },
    onError: (errors) => {
      addErrors.value = errors
    },
  })
}

const editingId = ref<number | null>(null)
const editForm = useForm({
  name: '',
  description: '',
  max_amount: null as number | null,
})

const startEdit = (lt: LoanTypeItem) => {
  editingId.value = lt.id
  editForm.name = lt.name
  editForm.description = lt.description ?? ''
  editForm.max_amount = lt.max_amount
}

const cancelEdit = () => {
  editingId.value = null
  editForm.reset()
}

const submitEdit = (id: number) => {
  editForm.put(`/hr-settings/loan-types/${id}`, {
    onSuccess: () => cancelEdit(),
  })
}

const deactivatingType = ref<LoanTypeItem | null>(null)
const destroyForm = useForm({})

const submitDeactivate = () => {
  if (!deactivatingType.value) { return }
  destroyForm.delete(`/hr-settings/loan-types/${deactivatingType.value.id}`, {
    onSuccess: () => { deactivatingType.value = null },
  })
}

const formatCurrency = (amount: number) =>
  new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', currencyDisplay: 'narrowSymbol' }).format(amount)
</script>
