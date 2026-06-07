<template>
  <Layout>
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900">App Settings</h1>
      <p class="mt-1 text-gray-600">Manage leave types, payroll schedules, and other HR configurations.</p>
    </div>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200">
      <nav class="flex gap-6">
        <Link
          href="/app-settings/employee-settings"
          class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700"
        >
          Employee Settings
        </Link>
        <Link
          href="/app-settings/leave-types"
          class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700"
        >
          Timekeeping Settings
        </Link>
        <Link
          href="/app-settings/payroll"
          class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700"
        >
          Payroll Settings
        </Link>
        <Link
          href="/app-settings/allowance-types"
          class="border-b-2 border-blue-600 pb-3 text-sm font-medium text-blue-600"
        >
          Allowance Settings
        </Link>
        <Link
          href="/app-settings/loan-types"
          class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700"
        >
          Loan Settings
        </Link>
        <Link
          href="/app-settings/holidays"
          class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700"
        >
          Holidays
        </Link>
        <Link
          href="/app-settings/contribution-settings"
          class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700"
        >
          Contribution Settings
        </Link>
      </nav>
    </div>

    <!-- Tab header -->
    <div class="mb-3 flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-900">Allowance Types</h2>
        <p class="mt-1 text-gray-600">Define the allowance types available for employees in your company.</p>
      </div>
      <button
        class="flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2 font-medium text-white transition-colors hover:bg-blue-700"
        @click="showAddForm = true"
      >
        <Plus :size="20" />
        Add Allowance Type
      </button>
    </div>
    <!-- Search -->
    <div class="mb-4 flex justify-end">
      <input v-model="allowanceSearch" type="text" placeholder="Search..." class="w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
      <table class="w-full text-sm">
        <thead class="border-b border-gray-200 bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Code</th>
            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Default Amount</th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Taxable</th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-if="props.allowanceTypes.length === 0">
            <td colspan="6" class="px-6 py-12 text-center text-gray-400">No allowance types configured yet.</td>
          </tr>
          <tr v-else-if="filteredAllowanceTypes.length === 0">
            <td colspan="6" class="px-6 py-12 text-center text-gray-400">No allowance types match your search.</td>
          </tr>
          <tr v-for="at in paginatedAllowanceTypes" :key="at.id" class="transition-colors hover:bg-gray-50">
            <td class="px-6 py-4 font-medium text-gray-900">
              <span v-if="editingId !== at.id">{{ at.name }}</span>
              <input
                v-else
                v-model="editForm.name"
                class="w-full rounded border border-gray-300 px-2 py-1 text-sm"
              />
            </td>
            <td class="px-6 py-4 text-gray-500">
              <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs">{{ at.code }}</code>
            </td>
            <td class="px-6 py-4 text-right text-gray-700">
              <span v-if="editingId !== at.id">{{ at.default_amount !== null ? formatCurrency(at.default_amount) : '—' }}</span>
              <input
                v-else
                v-model.number="editForm.default_amount"
                type="number"
                min="0"
                step="0.01"
                class="w-28 rounded border border-gray-300 px-2 py-1 text-right text-sm"
              />
            </td>
            <td class="px-6 py-4">
              <span v-if="editingId !== at.id" :class="at.is_taxable ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'" class="rounded-full px-2 py-0.5 text-xs font-medium">
                {{ at.is_taxable ? 'Taxable' : 'Non-Taxable' }}
              </span>
              <select v-else v-model="editForm.is_taxable" class="rounded border border-gray-300 px-2 py-1 text-sm">
                <option :value="false">Non-Taxable</option>
                <option :value="true">Taxable</option>
              </select>
            </td>
            <td class="px-6 py-4">
              <span v-if="editingId !== at.id" :class="at.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="rounded-full px-2 py-0.5 text-xs font-medium">
                {{ at.is_active ? 'Active' : 'Inactive' }}
              </span>
              <select v-else v-model="editForm.is_active" class="rounded border border-gray-300 px-2 py-1 text-sm">
                <option :value="true">Active</option>
                <option :value="false">Inactive</option>
              </select>
            </td>
            <td class="px-6 py-4 text-right">
              <div v-if="editingId !== at.id" class="flex items-center justify-end gap-2">
                <button class="rounded p-1 text-blue-600 hover:text-blue-800" title="Edit" @click="startEdit(at)">
                  <Pencil :size="16" />
                </button>
                <button
                  class="rounded p-1 text-red-500 hover:text-red-700"
                  title="Delete"
                  @click="deactivatingType = at"
                >
                  <Trash2 :size="16" />
                </button>
              </div>
              <div v-else class="flex items-center justify-end gap-2">
                <button class="rounded p-1 text-green-600 hover:text-green-800" title="Save" @click="submitEdit(at.id)">
                  <CheckCircle :size="16" />
                </button>
                <button class="rounded p-1 text-gray-500 hover:text-gray-700" title="Cancel" @click="cancelEdit">
                  <X :size="16" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <!-- Allowance Pagination -->
      <div v-if="props.allowanceTypes.length > 0" class="flex items-center border-t border-gray-200 bg-white px-6 py-4">
        <div class="flex w-1/3 items-center gap-2">
          <span class="text-sm text-gray-600">Per page:</span>
          <select v-model="allowancePerPage" @change="changeAllowancePerPage" class="rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option v-for="n in [5, 10, 25, 50]" :key="n" :value="n">{{ n }}</option>
          </select>
        </div>
        <div class="flex w-1/3 justify-center gap-2">
          <button @click="allowancePage--" :disabled="allowancePage <= 1" class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400" v-html="'&laquo;'" />
          <button v-for="p in allowanceTotalPages" :key="p" @click="allowancePage = p" :class="['rounded-lg px-3 py-1 text-sm font-medium transition-colors', allowancePage === p ? 'bg-blue-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50']">{{ p }}</button>
          <button @click="allowancePage++" :disabled="allowancePage >= allowanceTotalPages" class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400" v-html="'&raquo;'" />
        </div>
        <div class="flex w-1/3 justify-end">
          <p class="text-sm text-gray-600">{{ allowanceSearch ? `${filteredAllowanceTypes.length} of ${props.allowanceTypes.length}` : props.allowanceTypes.length }} allowance types</p>
        </div>
      </div>
    </div>

    <!-- Add Allowance Type Modal -->
    <div v-if="showAddForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="closeAddModal">
      <div class="mx-4 w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
        <div class="mb-5 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">New Allowance Type</h2>
          <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" @click="closeAddModal">
            <X :size="20" />
          </button>
        </div>
        <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="submitAdd">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
            <input
              v-model="addForm.name"
              type="text"
              placeholder="e.g. Gas Allowance"
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
              placeholder="e.g. gas"
              maxlength="50"
              class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              required
              @input="codeManuallyEdited = true"
            />
            <p v-if="addErrors.code" class="mt-1 text-xs text-red-600">{{ addErrors.code }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
              Default Amount (₱)
              <span class="font-normal text-gray-500 dark:text-gray-400">(optional)</span>
            </label>
            <input
              v-model.number="addForm.default_amount"
              type="number"
              min="0"
              step="0.01"
              placeholder="e.g. 1500"
              class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            />
            <p v-if="addErrors.default_amount" class="mt-1 text-xs text-red-600">{{ addErrors.default_amount }}</p>
          </div>
          <div class="flex items-center pt-5">
            <label class="flex cursor-pointer items-center gap-2">
              <input v-model="addForm.is_taxable" type="checkbox" class="rounded" />
              <span class="text-sm text-gray-700 dark:text-gray-300">Taxable benefit</span>
            </label>
          </div>
          <div class="flex justify-end gap-3 md:col-span-2">
            <button
              type="button"
              class="px-4 py-2 font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
              @click="closeAddModal"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="addForm.processing"
              class="rounded-lg bg-blue-600 px-6 py-2 font-medium text-white transition-colors hover:bg-blue-700 disabled:opacity-50"
            >
              {{ addForm.processing ? 'Saving...' : 'Save Allowance Type' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Deactivate Confirm Modal -->
    <div v-if="deactivatingType" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
      <div class="mx-4 w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
        <div class="flex items-start gap-4">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
            <AlertTriangle class="h-5 w-5 text-red-600" />
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Delete Allowance Type</h3>
            <p class="mt-1 text-sm text-gray-600">
              Delete <strong>{{ deactivatingType.name }}</strong>? This action cannot be undone.
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
            {{ destroyForm.processing ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { AlertTriangle, CheckCircle, Pencil, Plus, Trash2, X } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import Layout from '@/components/Layout.vue'

interface AllowanceTypeItem {
  id: number
  code: string
  name: string
  default_amount: number | null
  is_taxable: boolean
  is_active: boolean
}

const props = defineProps<{
  allowanceTypes: AllowanceTypeItem[]
}>()

// Search & Pagination
const allowanceSearch = ref('')
const allowancePage = ref(1)
const allowancePerPage = ref(5)
const filteredAllowanceTypes = computed(() => {
  const q = allowanceSearch.value.toLowerCase()
  if (!q) { return props.allowanceTypes }
  return props.allowanceTypes.filter(at =>
    at.name.toLowerCase().includes(q) || at.code.toLowerCase().includes(q),
  )
})
const allowanceTotalPages = computed(() => Math.max(1, Math.ceil(filteredAllowanceTypes.value.length / allowancePerPage.value)))
const paginatedAllowanceTypes = computed(() => {
  const start = (allowancePage.value - 1) * allowancePerPage.value
  return filteredAllowanceTypes.value.slice(start, start + allowancePerPage.value)
})
const changeAllowancePerPage = () => { allowancePage.value = 1 }
watch(allowanceSearch, () => { allowancePage.value = 1 })

// Add form
const showAddForm = ref(false)
const codeManuallyEdited = ref(false)
const addForm = useForm({
  code: '',
  name: '',
  default_amount: null as number | null,
  is_taxable: false,
})
const addErrors = ref<Record<string, string>>({})

function generateCode(name: string): string {
  return name.toLowerCase().replace(/[^a-z0-9\s]/g, '').trim().replace(/\s+/g, '_')
}

watch(() => addForm.name, (name) => {
  if (!codeManuallyEdited.value) {
    addForm.code = generateCode(name)
  }
})

const closeAddModal = () => {
  showAddForm.value = false
  codeManuallyEdited.value = false
  addForm.reset()
  addErrors.value = {}
}

const submitAdd = () => {
  addErrors.value = {}
  addForm.post('/app-settings/allowance-types', {
    onSuccess: () => closeAddModal(),
    onError: (errors) => {
      addErrors.value = errors
    },
  })
}

// Edit form
const editingId = ref<number | null>(null)
const editForm = useForm({
  name: '',
  default_amount: null as number | null,
  is_taxable: false,
  is_active: true,
})

const startEdit = (at: AllowanceTypeItem) => {
  editingId.value = at.id
  editForm.name = at.name
  editForm.default_amount = at.default_amount
  editForm.is_taxable = at.is_taxable
  editForm.is_active = at.is_active
}

const cancelEdit = () => {
  editingId.value = null
  editForm.reset()
}

const submitEdit = (id: number) => {
  editForm
    .transform(data => ({
      ...data,
      is_taxable: data.is_taxable ? 1 : 0,
      is_active: data.is_active ? 1 : 0,
    }))
    .put(`/app-settings/allowance-types/${id}`, {
      onSuccess: () => cancelEdit(),
    })
}

// Deactivate
const deactivatingType = ref<AllowanceTypeItem | null>(null)
const destroyForm = useForm({})

const submitDeactivate = () => {
  if (!deactivatingType.value) { return }
  destroyForm.delete(`/app-settings/allowance-types/${deactivatingType.value.id}`, {
    onSuccess: () => { deactivatingType.value = null },
  })
}

const formatCurrency = (amount: number) =>
  new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', currencyDisplay: 'narrowSymbol' }).format(amount)
</script>
