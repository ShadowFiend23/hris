<template>
  <Layout>
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ isAdmin ? 'Employee Loans' : 'My Loans' }}</h1>
        <p class="mt-1 text-gray-600">
          {{ isAdmin ? 'Manage SSS, Pag-IBIG, and company loan deductions for all employees.' : 'View your active loan balances and deduction schedule.' }}
        </p>
      </div>
      <button
        v-if="isAdmin"
        @click="openCreate"
        class="flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2 font-medium text-white transition-colors hover:bg-blue-700"
      >
        <Plus :size="20" />
        Add Loan
      </button>
    </div>

    <!-- Flash -->
    <div
      v-if="$page.props.flash?.success"
      class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
    >
      {{ $page.props.flash.success }}
    </div>

    <!-- Summary Cards (Admin) -->
    <div v-if="isAdmin" class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div class="rounded-lg border border-gray-200 bg-white p-6">
        <p class="text-sm font-medium text-gray-500">Active Loans</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ summary.active_count }}</p>
        <p class="mt-1 text-sm text-gray-500">Currently active</p>
      </div>
      <div class="rounded-lg border border-gray-200 bg-white p-6">
        <p class="text-sm font-medium text-gray-500">Total Outstanding Balance</p>
        <p class="mt-2 text-2xl font-bold text-red-600">{{ formatPeso(summary.total_balance) }}</p>
        <p class="mt-1 text-sm text-gray-500">Across all active loans</p>
      </div>
      <div class="rounded-lg border border-gray-200 bg-white p-6">
        <p class="text-sm font-medium text-gray-500">Total Monthly Deductions</p>
        <p class="mt-2 text-2xl font-bold text-amber-600">{{ formatPeso(summary.total_monthly_deduction) }}</p>
        <p class="mt-1 text-sm text-gray-500">Per payroll cycle</p>
      </div>
      <div class="rounded-lg border border-gray-200 bg-white p-6">
        <p class="text-sm font-medium text-gray-500">Employees with Loans</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ (summary as AdminSummary).employee_count }}</p>
        <p class="mt-1 text-sm text-gray-500">Active borrowers</p>
      </div>
    </div>

    <!-- Summary Cards (Employee) -->
    <div v-else class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
      <div class="rounded-lg border border-gray-200 bg-white p-6">
        <p class="text-sm font-medium text-gray-500">Active Loans</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ summary.active_count }}</p>
      </div>
      <div class="rounded-lg border border-gray-200 bg-white p-6">
        <p class="text-sm font-medium text-gray-500">Total Outstanding Balance</p>
        <p class="mt-2 text-2xl font-bold text-red-600">{{ formatPeso(summary.total_balance) }}</p>
      </div>
      <div class="rounded-lg border border-gray-200 bg-white p-6">
        <p class="text-sm font-medium text-gray-500">Monthly Deduction</p>
        <p class="mt-2 text-2xl font-bold text-amber-600">{{ formatPeso(summary.total_monthly_deduction) }}</p>
        <p class="mt-1 text-sm text-gray-500">Deducted from your payslip</p>
      </div>
    </div>

    <!-- Create / Edit Modal (admin only) -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
      @click.self="closeModal"
    >
      <div class="mx-4 w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
        <h2 class="mb-4 text-xl font-bold text-gray-900">
          {{ editingLoan ? 'Edit Loan' : 'Add Loan' }}
        </h2>
        <form @submit.prevent="submitForm">
          <div class="space-y-4">
            <div class="relative" ref="autocompleteWrap">
              <label class="mb-1 block text-sm font-medium text-gray-700">Employee</label>
              <div class="relative">
                <input
                  v-model="employeeQuery"
                  type="text"
                  placeholder="Name or ID…"
                  autocomplete="off"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  @input="onEmployeeInput"
                  @focus="showSuggestions = suggestions.length > 0"
                  @keydown.escape="showSuggestions = false"
                  @keydown.down.prevent="highlightNext"
                  @keydown.up.prevent="highlightPrev"
                  @keydown.enter.prevent="selectHighlighted"
                />
                <button v-if="selectedEmployeeSuggestion" @click="clearEmployee" type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                  <X :size="14" />
                </button>
                <Search v-else :size="14" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
              </div>

              <div v-if="suggestionsLoading" class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-400 shadow-lg">
                Searching…
              </div>
              <ul
                v-else-if="showSuggestions && suggestions.length > 0"
                class="absolute z-20 mt-1 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg"
                style="max-height: 220px"
              >
                <li
                  v-for="(emp, idx) in suggestions"
                  :key="emp.id"
                  @mousedown.prevent="selectEmployee(emp)"
                  :class="['cursor-pointer px-3 py-2 text-sm', idx === highlightedIndex ? 'bg-blue-50 text-blue-700' : 'text-gray-800 hover:bg-gray-50']"
                >
                  <span class="font-medium">{{ emp.last_name }},</span> {{ emp.first_name }}
                  <span class="ml-1 text-xs text-gray-400">{{ emp.employee_id }}</span>
                </li>
              </ul>
              <div
                v-else-if="showSuggestions && employeeQuery.length >= 2"
                class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-500 shadow-lg"
              >
                No employees found
              </div>
              <p v-if="form.errors.employee_id" class="mt-1 text-xs text-red-600">{{ form.errors.employee_id }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Loan Type</label>
              <select
                v-model="form.type"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option v-for="lt in loanTypes" :key="lt.id" :value="lt.code">{{ lt.name }}</option>
              </select>
              <p v-if="form.errors.type" class="mt-1 text-xs text-red-600">{{ form.errors.type }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Start Date</label>
                <input
                  v-model="form.start_date"
                  type="date"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                />
                <p v-if="form.errors.start_date" class="mt-1 text-xs text-red-600">{{ form.errors.start_date }}</p>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">
                  End Date <span class="font-normal text-gray-500">(optional)</span>
                </label>
                <input
                  v-model="form.end_date"
                  type="date"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <p v-if="form.errors.end_date" class="mt-1 text-xs text-red-600">{{ form.errors.end_date }}</p>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Principal</label>
                <input
                  v-model.number="form.principal"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                />
                <p v-if="form.errors.principal" class="mt-1 text-xs text-red-600">{{ form.errors.principal }}</p>
              </div>
              <div>
                <div class="mb-1 flex items-center gap-2">
                  <label class="block text-sm font-medium text-gray-700">Monthly Amortization</label>
                  <span v-if="autoAmortization !== null && !amortizationManuallyEdited" class="rounded bg-blue-50 px-1.5 py-0.5 text-xs text-blue-600">auto</span>
                </div>
                <input
                  v-model.number="form.monthly_amortization"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                  @input="onAmortizationInput"
                />
                <p v-if="form.errors.monthly_amortization" class="mt-1 text-xs text-red-600">
                  {{ form.errors.monthly_amortization }}
                </p>
              </div>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">
                Notes <span class="font-normal text-gray-500">(optional)</span>
              </label>
              <textarea
                v-model="form.notes"
                rows="2"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <p v-if="form.errors.notes" class="mt-1 text-xs text-red-600">{{ form.errors.notes }}</p>
            </div>
          </div>

          <div class="mt-6 flex justify-end gap-3">
            <button
              type="button"
              @click="closeModal"
              class="rounded-lg border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
            >
              <Loader2 v-if="form.processing" :size="16" class="animate-spin" />
              {{ editingLoan ? 'Update' : 'Add Loan' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div
      v-if="deletingLoan"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
      @click.self="deletingLoan = null"
    >
      <div class="mx-4 w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-center gap-3">
          <div class="rounded-full bg-red-100 p-2">
            <AlertTriangle class="text-red-600" :size="24" />
          </div>
          <h3 class="text-lg font-bold text-gray-900">Delete Loan</h3>
        </div>
        <p class="mb-6 text-gray-600">
          Delete the loan for
          <strong>{{ deletingLoan.employee?.first_name }} {{ deletingLoan.employee?.last_name }}</strong>?
          This cannot be undone.
        </p>
        <div class="flex justify-end gap-3">
          <button
            @click="deletingLoan = null"
            class="rounded-lg border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            @click="confirmDelete"
            class="rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700"
          >
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Admin Loans Table -->
    <div v-if="isAdmin" class="rounded-lg border border-gray-200 bg-white">
      <div v-if="loans.data.length === 0" class="p-12 text-center">
        <CreditCard class="mx-auto text-gray-400" :size="48" />
        <h3 class="mt-4 text-lg font-medium text-gray-900">No loans recorded</h3>
        <p class="mt-1 text-gray-600">Add employee loans to have them automatically deducted from payroll.</p>
      </div>

      <template v-else>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="border-b border-gray-200 bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Employee</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Type</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Principal</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Balance</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Monthly</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Start Date</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">End Date</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="loan in loans.data"
                :key="loan.id"
                class="border-b border-gray-100 hover:bg-gray-50"
              >
                <td class="px-6 py-4 text-sm">
                  <div class="font-medium text-gray-900">
                    {{ loan.employee?.first_name }} {{ loan.employee?.last_name }}
                  </div>
                  <div class="text-xs text-gray-500">{{ loan.employee?.employee_id }}</div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">{{ loanTypeLabel(loan.type) }}</td>
                <td class="px-6 py-4 text-right text-sm text-gray-700">{{ formatPeso(loan.principal) }}</td>
                <td class="px-6 py-4 text-right text-sm font-medium" :class="Number(loan.balance) > 0 ? 'text-red-600' : 'text-green-600'">
                  {{ formatPeso(loan.balance) }}
                </td>
                <td class="px-6 py-4 text-right text-sm text-gray-700">{{ formatPeso(loan.monthly_amortization) }}</td>
                <td class="px-6 py-4 text-sm text-gray-700">{{ formatDate(loan.start_date) }}</td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ loan.end_date ? formatDate(loan.end_date) : '—' }}</td>
                <td class="px-6 py-4 text-sm">
                  <span :class="['rounded-full px-3 py-1 text-xs font-semibold', loanStatusColor(loan.status)]">
                    {{ loan.status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm">
                  <div class="flex items-center gap-2">
                    <button
                      @click="openEdit(loan)"
                      class="rounded border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50"
                    >
                      Edit
                    </button>
                    <button
                      @click="deletingLoan = loan"
                      class="rounded border border-red-200 px-3 py-1 text-xs font-medium text-red-600 hover:bg-red-50"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="flex items-center border-t border-gray-200 bg-white px-6 py-4">
          <div class="flex w-1/3 items-center gap-2">
            <span class="text-sm text-gray-600">Per page:</span>
            <select
              v-model="perPage"
              @change="changePerPage"
              class="rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option v-for="n in [5, 10, 25, 50]" :key="n" :value="n">{{ n }}</option>
            </select>
          </div>
          <div class="flex w-1/3 justify-center gap-2">
            <Link
              v-for="link in loans.links"
              :key="link.label"
              :href="link.url ?? '#'"
              :class="[
                'rounded-lg px-3 py-1 text-sm font-medium transition-colors',
                link.active ? 'bg-blue-600 text-white' : link.url ? 'border border-gray-300 text-gray-700 hover:bg-gray-50' : 'cursor-not-allowed border border-gray-200 text-gray-400',
              ]"
              v-html="link.label"
              :preserve-scroll="true"
            />
          </div>
          <div class="flex w-1/3 justify-end">
            <p class="text-sm text-gray-600">{{ loans.total }} total records</p>
          </div>
        </div>
      </template>
    </div>

    <!-- Employee Loans Table -->
    <div v-else class="rounded-lg border border-gray-200 bg-white">
      <div v-if="loans.data.length === 0" class="p-12 text-center">
        <CreditCard class="mx-auto text-gray-400" :size="48" />
        <h3 class="mt-4 text-lg font-medium text-gray-900">No loans on record</h3>
        <p class="mt-1 text-gray-600">You currently have no active or past loans.</p>
      </div>

      <template v-else>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="border-b border-gray-200 bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Loan Type</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Principal</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Balance</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Monthly Deduction</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Start Date</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">End Date</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Notes</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="loan in loans.data"
                :key="loan.id"
                class="border-b border-gray-100 hover:bg-gray-50"
              >
                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ loanTypeLabel(loan.type) }}</td>
                <td class="px-6 py-4 text-right text-sm text-gray-700">{{ formatPeso(loan.principal) }}</td>
                <td class="px-6 py-4 text-right text-sm font-medium" :class="Number(loan.balance) > 0 ? 'text-red-600' : 'text-green-600'">
                  {{ formatPeso(loan.balance) }}
                </td>
                <td class="px-6 py-4 text-right text-sm text-gray-700">{{ formatPeso(loan.monthly_amortization) }}</td>
                <td class="px-6 py-4 text-sm text-gray-700">{{ formatDate(loan.start_date) }}</td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ loan.end_date ? formatDate(loan.end_date) : '—' }}</td>
                <td class="px-6 py-4 text-sm">
                  <span :class="['rounded-full px-3 py-1 text-xs font-semibold', loanStatusColor(loan.status)]">
                    {{ loan.status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ loan.notes ?? '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="flex items-center justify-between border-t border-gray-200 bg-white px-6 py-4">
          <div class="flex items-center gap-2">
            <span class="text-sm text-gray-600">Per page:</span>
            <select
              v-model="perPage"
              @change="changePerPage"
              class="rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option v-for="n in [5, 10, 25, 50]" :key="n" :value="n">{{ n }}</option>
            </select>
          </div>
          <div class="flex gap-2">
            <Link
              v-for="link in loans.links"
              :key="link.label"
              :href="link.url ?? '#'"
              :class="[
                'rounded-lg px-3 py-1 text-sm font-medium transition-colors',
                link.active ? 'bg-blue-600 text-white' : link.url ? 'border border-gray-300 text-gray-700 hover:bg-gray-50' : 'cursor-not-allowed border border-gray-200 text-gray-400',
              ]"
              v-html="link.label"
              :preserve-scroll="true"
            />
          </div>
          <p class="text-sm text-gray-600">{{ loans.total }} loan(s)</p>
        </div>
      </template>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { ref, watch, computed, onMounted } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import { Plus, Loader2, AlertTriangle, CreditCard, Search, X } from 'lucide-vue-next'
import Layout from '@/components/Layout.vue'

interface Employee {
  id: number
  first_name: string
  last_name: string
  employee_id: string
}

interface Loan {
  id: number
  employee?: Employee
  type: string
  principal: number
  balance: number
  monthly_amortization: number
  start_date: string
  end_date: string | null
  status: string
  notes: string | null
}

interface PaginatedLoans {
  data: Loan[]
  from: number | null
  to: number | null
  total: number
  per_page: number
  links: { label: string; url: string | null; active: boolean }[]
}

interface AdminSummary {
  active_count: number
  total_balance: number
  total_monthly_deduction: number
  employee_count: number
}

interface EmployeeSummary {
  active_count: number
  total_balance: number
  total_monthly_deduction: number
}

interface LoanType {
  id: number
  name: string
  code: string
}

const props = defineProps<{
  loans: PaginatedLoans
  summary: AdminSummary | EmployeeSummary
  is_admin: boolean
  loan_types?: LoanType[]
}>()

const isAdmin = props.is_admin
const loanTypes = props.loan_types ?? []

const perPage = ref<number>(props.loans.per_page ?? 10)

const changePerPage = () => {
  router.get('/loans', { per_page: perPage.value, page: 1 }, { preserveState: true, preserveScroll: true })
}

const showModal = ref(false)
const editingLoan = ref<Loan | null>(null)
const deletingLoan = ref<Loan | null>(null)

// Employee autocomplete
interface EmployeeSuggestion { id: number; employee_id: string; first_name: string; last_name: string }
const employeeQuery = ref('')
const selectedEmployeeSuggestion = ref<EmployeeSuggestion | null>(null)
const suggestions = ref<EmployeeSuggestion[]>([])
const showSuggestions = ref(false)
const suggestionsLoading = ref(false)
const highlightedIndex = ref(-1)
const autocompleteWrap = ref<HTMLElement | null>(null)
let debounceTimer: ReturnType<typeof setTimeout>

const onEmployeeInput = () => {
  selectedEmployeeSuggestion.value = null
  form.employee_id = null
  clearTimeout(debounceTimer)
  if (employeeQuery.value.length < 2) {
    suggestions.value = []
    showSuggestions.value = false
    return
  }
  suggestionsLoading.value = true
  showSuggestions.value = true
  debounceTimer = setTimeout(fetchSuggestions, 280)
}

const fetchSuggestions = async () => {
  try {
    const res = await fetch(`/api/core/employees?search=${encodeURIComponent(employeeQuery.value)}&is_active=1`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
    if (!res.ok) { return }
    const json = await res.json()
    const list: any[] = json.data ?? json
    suggestions.value = list
      .map((e: any) => ({ id: e.id, employee_id: e.employee_id, first_name: e.first_name, last_name: e.last_name }))
      .sort((a, b) => a.last_name.localeCompare(b.last_name))
      .slice(0, 8)
    highlightedIndex.value = -1
  } finally {
    suggestionsLoading.value = false
  }
}

const selectEmployee = (emp: EmployeeSuggestion) => {
  selectedEmployeeSuggestion.value = emp
  form.employee_id = emp.id
  employeeQuery.value = `${emp.last_name}, ${emp.first_name}`
  showSuggestions.value = false
  suggestions.value = []
}

const clearEmployee = () => {
  selectedEmployeeSuggestion.value = null
  form.employee_id = null
  employeeQuery.value = ''
  suggestions.value = []
  showSuggestions.value = false
}

const highlightNext = () => { if (highlightedIndex.value < suggestions.value.length - 1) { highlightedIndex.value++ } }
const highlightPrev = () => { if (highlightedIndex.value > 0) { highlightedIndex.value-- } }
const selectHighlighted = () => {
  if (highlightedIndex.value >= 0 && suggestions.value[highlightedIndex.value]) {
    selectEmployee(suggestions.value[highlightedIndex.value])
  }
}

const resetAutocomplete = () => {
  selectedEmployeeSuggestion.value = null
  employeeQuery.value = ''
  suggestions.value = []
  showSuggestions.value = false
  highlightedIndex.value = -1
}

const form = useForm({
  employee_id: null as number | null,
  type: loanTypes[0]?.code ?? '',
  principal: null as number | null,
  monthly_amortization: null as number | null,
  start_date: '',
  end_date: '',
  notes: '',
})

const autoAmortization = computed(() => {
  if (!form.principal || !form.start_date || !form.end_date) { return null }
  const start = new Date(form.start_date)
  const end = new Date(form.end_date)
  if (end <= start) { return null }
  const months = (end.getFullYear() - start.getFullYear()) * 12 + (end.getMonth() - start.getMonth()) + 1
  if (months <= 0) { return null }
  return Math.round((form.principal / months) * 100) / 100
})

const amortizationManuallyEdited = ref(false)

watch(autoAmortization, (val) => {
  if (!amortizationManuallyEdited.value && val !== null) {
    form.monthly_amortization = val
  }
})

const onAmortizationInput = () => {
  amortizationManuallyEdited.value = true
}

const openCreate = () => {
  amortizationManuallyEdited.value = false
  editingLoan.value = null
  form.reset()
  resetAutocomplete()
  showModal.value = true
}

const openEdit = (loan: Loan) => {
  amortizationManuallyEdited.value = true
  editingLoan.value = loan
  form.employee_id = loan.employee?.id ?? null
  form.type = loan.type
  form.principal = loan.principal
  form.monthly_amortization = loan.monthly_amortization
  form.start_date = loan.start_date
  form.end_date = loan.end_date ?? ''
  form.notes = loan.notes ?? ''
  resetAutocomplete()
  if (loan.employee) {
    selectedEmployeeSuggestion.value = loan.employee as EmployeeSuggestion
    employeeQuery.value = `${loan.employee.last_name}, ${loan.employee.first_name}`
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingLoan.value = null
  form.reset()
  resetAutocomplete()
}

const submitForm = () => {
  if (editingLoan.value) {
    form.put(`/loans/${editingLoan.value.id}`, { onSuccess: closeModal })
  } else {
    form.post('/loans', { onSuccess: closeModal })
  }
}

const confirmDelete = () => {
  if (!deletingLoan.value) { return }
  router.delete(`/loans/${deletingLoan.value.id}`, {
    onSuccess: () => { deletingLoan.value = null },
  })
}

const formatDate = (date: string) =>
  new Date(date).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })

const formatPeso = (amount: number) =>
  new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', currencyDisplay: 'narrowSymbol' }).format(Number(amount))

const loanTypeLabel = (type: string) => loanTypes.find((lt) => lt.code === type)?.name ?? type

const loanStatusColor = (status: string) =>
  ({
    active: 'bg-blue-100 text-blue-700',
    completed: 'bg-green-100 text-green-700',
    defaulted: 'bg-red-100 text-red-700',
  })[status] ?? 'bg-gray-100 text-gray-700'

onMounted(() => {
  document.addEventListener('click', (e) => {
    if (autocompleteWrap.value && !autocompleteWrap.value.contains(e.target as Node)) {
      showSuggestions.value = false
    }
  })
})
</script>
