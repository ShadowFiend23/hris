<template>
  <Layout>
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">HR Settings</h1>
      <p class="mt-1 text-gray-600 dark:text-gray-400">Manage leave types, payroll schedules, and other HR configurations.</p>
    </div>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
      <nav class="flex gap-6">
        <Link
          href="/hr-settings/employee-settings"
          class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
        >
          Employee Settings
        </Link>
        <Link
          href="/hr-settings/leave-types"
          class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
        >
          Timekeeping Settings
        </Link>
        <Link
          href="/hr-settings/payroll"
          class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
        >
          Payroll Settings
        </Link>
        <Link
          href="/hr-settings/allowance-types"
          class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
        >
          Allowance Settings
        </Link>
        <Link
          href="/hr-settings/loan-types"
          class="border-b-2 border-blue-600 pb-3 text-sm font-medium text-blue-600 dark:border-blue-400 dark:text-blue-400"
        >
          Loan Settings
        </Link>
        <Link
          href="/hr-settings/holidays"
          class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
        >
          Holidays
        </Link>
      </nav>
    </div>

    <!-- Section header -->
    <div class="mb-6">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Loan Settings</h2>
      <p class="mt-1 text-gray-600 dark:text-gray-400">
        Configure loan availability and define the loan types offered to employees.
      </p>
    </div>

    <!-- Flash message -->
    <div
      v-if="flashSuccess"
      class="mb-6 flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 dark:border-green-700 dark:bg-green-900/30 dark:text-green-300"
    >
      <CheckCircle :size="18" class="shrink-0 text-green-600 dark:text-green-400" />
      {{ flashSuccess }}
    </div>

    <!-- Loans Toggle Card -->
    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-base font-semibold text-gray-900 dark:text-white">Enable Loans</h3>
          <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
            Allow employees to apply for loans. When disabled, the loan types below are hidden from employees.
          </p>
        </div>
        <button
          type="button"
          :disabled="toggleForm.processing"
          class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-gray-800"
          :class="props.loansEnabled ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-600'"
          role="switch"
          :aria-checked="props.loansEnabled"
          @click="submitToggle"
        >
          <span
            class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
            :class="props.loansEnabled ? 'translate-x-5' : 'translate-x-0'"
          />
        </button>
      </div>
    </div>

    <!-- Loan Types section — only visible when loans are enabled -->
    <template v-if="props.loansEnabled">
      <!-- Sub-header with Add button -->
      <div class="mb-3 flex items-center justify-between">
        <div>
          <h3 class="text-base font-semibold text-gray-900 dark:text-white">Loan Types</h3>
          <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
            The three default types (SSS, Pag-IBIG, Company) are available for all companies.
          </p>
        </div>
        <button
          class="flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2 font-medium text-white transition-colors hover:bg-blue-700"
          @click="showAddForm = true"
        >
          <Plus :size="18" />
          Add Loan Type
        </button>
      </div>
      <!-- Search -->
      <div class="mb-3 flex justify-end">
        <input v-model="loanSearch" type="text" placeholder="Search..." class="w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400" />
      </div>

      <!-- Add Form -->
      <div v-if="showAddForm" class="mb-6 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
        <h4 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">New Loan Type</h4>
        <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="submitAdd">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
            <input
              v-model="addForm.name"
              type="text"
              placeholder="e.g. Calamity Loan"
              class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              required
            />
            <p v-if="addErrors.name" class="mt-1 text-xs text-red-600">{{ addErrors.name }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
              Code
              <span class="font-normal text-gray-500 dark:text-gray-400">(lowercase, underscores only)</span>
            </label>
            <input
              v-model="addForm.code"
              type="text"
              placeholder="e.g. calamity_loan"
              maxlength="20"
              class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              required
            />
            <p v-if="addErrors.code" class="mt-1 text-xs text-red-600">{{ addErrors.code }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
              Max Amount (₱)
              <span class="font-normal text-gray-500 dark:text-gray-400">(optional)</span>
            </label>
            <input
              v-model.number="addForm.max_amount"
              type="number"
              min="0"
              step="0.01"
              placeholder="e.g. 50000"
              class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            />
            <p v-if="addErrors.max_amount" class="mt-1 text-xs text-red-600">{{ addErrors.max_amount }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
              Description
              <span class="font-normal text-gray-500 dark:text-gray-400">(optional)</span>
            </label>
            <input
              v-model="addForm.description"
              type="text"
              placeholder="Brief description"
              class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            />
            <p v-if="addErrors.description" class="mt-1 text-xs text-red-600">{{ addErrors.description }}</p>
          </div>
          <div class="flex justify-end gap-3 md:col-span-2">
            <button
              type="button"
              class="px-4 py-2 font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
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
      <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <table class="w-full text-sm">
          <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Code</th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Max Amount</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Description</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Scope</th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-if="props.loanTypes.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">No loan types configured yet.</td>
            </tr>
            <tr v-else-if="filteredLoanTypes.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">No loan types match your search.</td>
            </tr>
            <tr v-for="lt in paginatedLoanTypes" :key="lt.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
              <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                <span v-if="editingId !== lt.id">{{ lt.name }}</span>
                <input
                  v-else
                  v-model="editForm.name"
                  class="w-full rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                />
              </td>
              <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs dark:bg-gray-700 dark:text-gray-300">{{ lt.code }}</code>
              </td>
              <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-300">
                <span v-if="editingId !== lt.id">{{ lt.max_amount !== null ? formatCurrency(lt.max_amount) : '—' }}</span>
                <input
                  v-else
                  v-model.number="editForm.max_amount"
                  type="number"
                  min="0"
                  step="0.01"
                  class="w-28 rounded border border-gray-300 px-2 py-1 text-right text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                />
              </td>
              <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                <span v-if="editingId !== lt.id">{{ lt.description ?? '—' }}</span>
                <input
                  v-else
                  v-model="editForm.description"
                  type="text"
                  class="w-full rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                />
              </td>
              <td class="px-6 py-4">
                <span
                  :class="lt.company_id ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'"
                  class="rounded-full px-2 py-0.5 text-xs font-medium"
                >
                  {{ lt.company_id ? 'Company' : 'Default' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <div v-if="lt.company_id" class="flex items-center justify-end gap-2">
                  <template v-if="editingId !== lt.id">
                    <button class="rounded p-1 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200" title="Edit" @click="startEdit(lt)">
                      <Pencil :size="16" />
                    </button>
                    <button class="rounded p-1 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-200" title="Remove" @click="deactivatingType = lt">
                      <Trash2 :size="16" />
                    </button>
                  </template>
                  <template v-else>
                    <button class="rounded p-1 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200" title="Save" @click="submitEdit(lt.id)">
                      <CheckCircle :size="16" />
                    </button>
                    <button class="rounded p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" title="Cancel" @click="cancelEdit">
                      <X :size="16" />
                    </button>
                  </template>
                </div>
                <span v-else class="text-xs text-gray-400 dark:text-gray-500">Default</span>
              </td>
            </tr>
          </tbody>
        </table>
        <!-- Loan Types Pagination -->
        <div v-if="props.loanTypes.length > 0" class="flex items-center border-t border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
          <div class="flex w-1/3 items-center gap-2">
            <span class="text-sm text-gray-600 dark:text-gray-400">Per page:</span>
            <select v-model="loanPerPage" @change="changeLoanPerPage" class="rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
              <option v-for="n in [5, 10, 25, 50]" :key="n" :value="n">{{ n }}</option>
            </select>
          </div>
          <div class="flex w-1/3 justify-center gap-2">
            <button @click="loanPage--" :disabled="loanPage <= 1" class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700" v-html="'&laquo;'" />
            <button v-for="p in loanTotalPages" :key="p" @click="loanPage = p" :class="['rounded-lg px-3 py-1 text-sm font-medium transition-colors', loanPage === p ? 'bg-blue-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700']">{{ p }}</button>
            <button @click="loanPage++" :disabled="loanPage >= loanTotalPages" class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700" v-html="'&raquo;'" />
          </div>
          <div class="flex w-1/3 justify-end">
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ loanSearch ? `${filteredLoanTypes.length} of ${props.loanTypes.length}` : props.loanTypes.length }} loan types</p>
          </div>
        </div>
      </div>
    </template>

    <!-- Disabled state message -->
    <div
      v-else
      class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-6 py-12 text-center dark:border-gray-600 dark:bg-gray-800/50"
    >
      <ToggleLeft :size="40" class="mx-auto mb-3 text-gray-400 dark:text-gray-500" />
      <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Loans are currently disabled for this company.</p>
      <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Toggle the switch above to enable loans and configure loan types.</p>
    </div>

    <!-- Deactivate Confirm Modal -->
    <div v-if="deactivatingType" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
      <div class="mx-4 w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
        <div class="flex items-start gap-4">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
            <AlertTriangle class="h-5 w-5 text-red-600 dark:text-red-400" />
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Remove Loan Type</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
              Remove <strong>{{ deactivatingType.name }}</strong>? It will no longer appear in the loan type dropdown.
              Existing employee loans will be preserved.
            </p>
          </div>
        </div>
        <div class="mt-6 flex justify-end gap-3">
          <button class="px-4 py-2 font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" @click="deactivatingType = null">
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
import { computed, ref, watch } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { AlertTriangle, CheckCircle, Pencil, Plus, ToggleLeft, Trash2, X } from 'lucide-vue-next'
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
  loansEnabled: boolean
}>()

const page = usePage()
const flashSuccess = computed(() => (page.props.flash as any)?.success ?? null)

// Search & Pagination
const loanSearch = ref('')
const loanPage = ref(1)
const loanPerPage = ref(5)
const filteredLoanTypes = computed(() => {
  const q = loanSearch.value.toLowerCase()
  if (!q) { return props.loanTypes }
  return props.loanTypes.filter(lt =>
    lt.name.toLowerCase().includes(q) ||
    lt.code.toLowerCase().includes(q) ||
    (lt.description ?? '').toLowerCase().includes(q),
  )
})
const loanTotalPages = computed(() => Math.max(1, Math.ceil(filteredLoanTypes.value.length / loanPerPage.value)))
const paginatedLoanTypes = computed(() => {
  const start = (loanPage.value - 1) * loanPerPage.value
  return filteredLoanTypes.value.slice(start, start + loanPerPage.value)
})
const changeLoanPerPage = () => { loanPage.value = 1 }
watch(loanSearch, () => { loanPage.value = 1 })

const toggleForm = useForm({})

const submitToggle = () => {
  toggleForm.patch('/hr-settings/loan-settings/toggle')
}

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
