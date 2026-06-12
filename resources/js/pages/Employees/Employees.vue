<template>
  <Layout>
    <!-- Page Header -->
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Employees</h1>
        <p class="text-gray-600 mt-1">Manage your organization's employees</p>
      </div>
      <div class="flex items-center gap-3">
        <button
          v-if="canEdit"
          @click="openImportModal"
          class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
        >
          <Upload :size="16" />
          Import Employees
        </button>
        <Link
          href="/employees/create"
          class="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors flex items-center gap-2"
        >
          <Plus :size="20" />
          Add Employee
        </Link>
      </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
      <div class="flex flex-wrap gap-4">
        <!-- Search Input -->
        <div class="flex-1 min-w-64 relative">
          <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" :size="20" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by name, employee no, or email..."
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            @input="debouncedSearch"
          />
        </div>

        <!-- Department Filter -->
        <select
          v-model="selectedDepartment"
          @change="applyFilters"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
        >
          <option value="">All Departments</option>
          <option v-for="dept in departments" :key="dept.id" :value="dept.id">
            {{ dept.name }}
          </option>
        </select>

        <!-- Position Filter -->
        <select
          v-model="selectedPosition"
          @change="applyFilters"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
        >
          <option value="">All Positions</option>
          <option v-for="pos in filteredPositions" :key="pos.id" :value="pos.id">
            {{ pos.position_name }}
          </option>
        </select>

        <!-- Status Filter -->
        <select
          v-model="selectedStatus"
          @change="applyFilters"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
        >
          <option value="">All Statuses</option>
          <option v-for="status in employmentStatuses" :key="status.value" :value="status.value">
            {{ status.label }}
          </option>
        </select>

        <!-- Clear Filters -->
        <button
          v-if="hasActiveFilters"
          @click="clearFilters"
          class="px-4 py-2 text-gray-600 hover:text-gray-900 flex items-center gap-2"
        >
          <X :size="16" />
          Clear
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg border border-gray-200">
      <!-- Loading State -->
      <div v-if="isLoading" class="p-8 text-center">
        <Loader2 class="animate-spin mx-auto text-blue-600" :size="32" />
        <p class="text-gray-600 mt-2">Loading employees...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="employees.data?.length === 0" class="p-8 text-center">
        <Users class="mx-auto text-gray-400" :size="48" />
        <h3 class="text-lg font-medium text-gray-900 mt-4">No employees found</h3>
        <p class="text-gray-600 mt-1">
          {{ hasActiveFilters ? 'Try adjusting your filters' : 'Get started by adding your first employee' }}
        </p>
        <Link
          v-if="!hasActiveFilters"
          href="/employees/create"
          class="inline-flex items-center gap-2 mt-4 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
        >
          <Plus :size="16" />
          Add Employee
        </Link>
      </div>

      <!-- Table Content -->
      <template v-else>
        <div>
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-6 py-3 text-left">
                  <input
                    type="checkbox"
                    class="rounded border-gray-300"
                    :checked="allSelected"
                    @change="toggleSelectAll"
                  />
                </th>
                <th
                  class="px-6 py-3 text-left text-sm font-semibold text-gray-900 cursor-pointer hover:bg-gray-100"
                  @click="sortBy('employee_id')"
                >
                  <div class="flex items-center gap-1">
                    Employee No
                    <ArrowUpDown :size="14" class="text-gray-400" />
                  </div>
                </th>
                <th
                  class="px-6 py-3 text-left text-sm font-semibold text-gray-900 cursor-pointer hover:bg-gray-100"
                  @click="sortBy('first_name')"
                >
                  <div class="flex items-center gap-1">
                    Name
                    <ArrowUpDown :size="14" class="text-gray-400" />
                  </div>
                </th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">
                  Department
                </th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">
                  Position
                </th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">
                  Status
                </th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(employee, index) in employees.data"
                :key="employee.id"
                :class="[
                  'border-b border-gray-200 hover:bg-gray-50 transition-colors',
                  index % 2 === 0 ? 'bg-white' : 'bg-gray-50/50',
                ]"
              >
                <td class="px-6 py-4">
                  <input
                    type="checkbox"
                    class="rounded border-gray-300"
                    :checked="selectedEmployees.includes(employee.id)"
                    @change="toggleSelect(employee.id)"
                  />
                </td>
                <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                  {{ employee.employee_id }}
                </td>
                <td class="px-6 py-4 text-sm">
                  <Link
                    :href="`/employees/${employee.id}`"
                    class="text-gray-900 font-medium hover:text-blue-600"
                  >
                    {{ employee.first_name }} {{ employee.middle_name }} {{ employee.last_name }}
                  </Link>
                  <p class="text-gray-500 text-xs mt-0.5">{{ employee.email }}</p>
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">
                  {{ employee.department?.name || '-' }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">
                  {{ employee.position?.position_name || '-' }}
                </td>
                <td class="px-6 py-4 text-sm">
                  <span
                    :class="[
                      'inline-block px-3 py-1 rounded-full text-xs font-semibold',
                      getStatusColor(employee.employment_status),
                    ]"
                  >
                    {{ formatStatus(employee.employment_status) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm">
                  <DropdownMenu>
                    <DropdownMenuTrigger>
                      <button
                        class="text-gray-500 hover:text-gray-900 p-1 rounded hover:bg-gray-100"
                      >
                        <MoreVertical :size="20" />
                      </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-48">
                      <DropdownMenuItem @click="router.visit(`/employees/${employee.id}`)">
                        <Eye :size="16" />
                        <span>View Details</span>
                      </DropdownMenuItem>
                      <DropdownMenuItem
                        v-if="canEdit"
                        @click="router.visit(`/employees/${employee.id}/edit`)"
                      >
                        <Pencil :size="16" />
                        <span>Edit</span>
                      </DropdownMenuItem>
                      <DropdownMenuItem
                        v-if="canDelete"
                        class="text-red-600 focus:text-red-600 focus:bg-red-50"
                        @click="confirmDelete(employee)"
                      >
                        <Trash2 :size="16" />
                        <span>Delete</span>
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
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
              v-for="link in employees.links"
              :key="link.label"
              :href="link.url || '#'"
              :class="[
                'px-3 py-1 rounded-lg font-medium transition-colors text-sm',
                link.active
                  ? 'bg-blue-600 text-white'
                  : link.url
                    ? 'border border-gray-300 text-gray-700 hover:bg-gray-50'
                    : 'border border-gray-200 text-gray-400 cursor-not-allowed',
              ]"
              v-html="link.label"
              :preserve-scroll="true"
            />
          </div>
          <div class="flex w-1/3 justify-end">
            <p class="text-sm text-gray-600">{{ employees.total || 0 }} total employees</p>
          </div>
        </div>
      </template>
    </div>

    <!-- Bulk Actions Bar -->
    <div
      v-if="selectedEmployees.length > 0"
      class="fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-gray-900 text-white px-6 py-3 rounded-lg shadow-xl flex items-center gap-4"
    >
      <span class="text-sm">{{ selectedEmployees.length }} selected</span>
      <button
        @click="bulkDelete"
        class="flex items-center gap-2 px-3 py-1 bg-red-600 rounded hover:bg-red-700"
      >
        <Trash2 :size="16" />
        Delete Selected
      </button>
      <button @click="selectedEmployees = []" class="text-gray-400 hover:text-white">
        <X :size="20" />
      </button>
    </div>

    <!-- Delete Confirmation Modal -->
    <div
      v-if="showDeleteModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      @click.self="showDeleteModal = false"
    >
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="flex items-center gap-3 mb-4">
          <div class="p-2 bg-red-100 rounded-full">
            <AlertTriangle class="text-red-600" :size="24" />
          </div>
          <h3 class="text-lg font-bold text-gray-900">Delete Employee</h3>
        </div>
        <p class="text-gray-600 mb-6">
          Are you sure you want to delete <strong>{{ employeeToDelete?.first_name }} {{ employeeToDelete?.last_name }}</strong>?
          This action cannot be undone.
        </p>
        <div class="flex justify-end gap-3">
          <button
            @click="showDeleteModal = false"
            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            @click="deleteEmployee"
            :disabled="isDeleting"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 flex items-center gap-2"
          >
            <Loader2 v-if="isDeleting" class="animate-spin" :size="16" />
            Delete
          </button>
        </div>
      </div>
    </div>
    <!-- Import Employees Modal -->
    <div
      v-if="showImportModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
      @click.self="closeImportModal"
    >
      <div class="mx-4 w-full max-w-2xl rounded-xl bg-white shadow-2xl dark:bg-gray-800">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-900">
              <Upload class="text-blue-600 dark:text-blue-400" :size="20" />
            </div>
            <div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Import Employees</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400">Upload an Excel file to bulk-add employees</p>
            </div>
          </div>
          <button
            @click="closeImportModal"
            class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700"
          >
            <X :size="20" />
          </button>
        </div>

        <!-- Modal Body -->
        <div class="px-6 py-5">
          <!-- Results view -->
          <template v-if="importResults">
            <div class="mb-4 flex items-center justify-between">
              <div class="flex items-center gap-2 text-sm">
                <CheckCircle class="text-green-500" :size="18" />
                <span class="font-medium text-gray-800 dark:text-gray-200">
                  Import complete — {{ importResults.filter((r) => r.status === 'created').length }} created,
                  {{ importResults.filter((r) => r.status === 'skipped').length }} skipped
                </span>
              </div>
            </div>

            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200">
              Share credentials below securely. Employees will be required to change their password on first login.
            </div>

            <div class="overflow-auto rounded-lg border border-gray-200 dark:border-gray-700" style="max-height: 320px">
              <table class="w-full text-sm">
                <thead class="sticky top-0 bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Name</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Username</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Temp Password</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="result in importResults"
                    :key="result.row"
                    class="border-t border-gray-100 dark:border-gray-700"
                  >
                    <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ result.name }}</td>
                    <td class="px-4 py-2 font-mono text-gray-700 dark:text-gray-300">{{ result.username ?? '—' }}</td>
                    <td class="px-4 py-2 font-mono text-gray-700 dark:text-gray-300">{{ result.temp_password ?? '—' }}</td>
                    <td class="px-4 py-2">
                      <span
                        v-if="result.status === 'created'"
                        class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/40 dark:text-green-300"
                      >
                        <CheckCircle :size="11" /> Created
                      </span>
                      <span
                        v-else
                        :title="result.error ?? ''"
                        class="inline-flex cursor-help items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/40 dark:text-red-300"
                      >
                        <AlertCircle :size="11" /> Skipped
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="mt-5 flex justify-end">
              <button
                @click="closeImportModal"
                class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700"
              >
                Done
              </button>
            </div>
          </template>

          <!-- Upload form -->
          <template v-else>
            <!-- Step 1: Download template -->
            <div class="mb-5 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
              <p class="mb-2 text-sm font-medium text-gray-800 dark:text-gray-200">Step 1: Download the template</p>
              <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
                Fill in employee information using the provided Excel template.
              </p>
              <a
                href="/employees/import/template"
                class="inline-flex items-center gap-2 rounded-lg border border-blue-300 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50"
              >
                <Download :size="16" />
                Download Template (.xlsx)
              </a>
            </div>

            <!-- Step 2: Upload -->
            <div class="mb-5 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
              <p class="mb-2 text-sm font-medium text-gray-800 dark:text-gray-200">Step 2: Upload your completed file</p>
              <label
                class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-blue-400 hover:bg-blue-50/50 dark:border-gray-600 dark:hover:border-blue-500 dark:hover:bg-blue-900/10"
                :class="importFile ? 'border-blue-400 bg-blue-50/50 dark:border-blue-500' : ''"
              >
                <Upload class="text-gray-400" :size="28" />
                <span class="text-sm text-gray-600 dark:text-gray-400">
                  <span v-if="importFile" class="font-medium text-blue-600 dark:text-blue-400">{{ importFile.name }}</span>
                  <span v-else>Click to select or drag &amp; drop your Excel file</span>
                </span>
                <span class="text-xs text-gray-400">.xlsx or .xls only, max 5 MB</span>
                <input
                  type="file"
                  accept=".xlsx,.xls"
                  class="hidden"
                  @change="onFileChange"
                />
              </label>
            </div>

            <div v-if="importError" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-700 dark:bg-red-900/30 dark:text-red-300">
              {{ importError }}
            </div>

            <div class="flex justify-end gap-3">
              <button
                @click="closeImportModal"
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
              >
                Cancel
              </button>
              <button
                @click="importEmployees"
                :disabled="!importFile || isImporting"
                class="flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
              >
                <Loader2 v-if="isImporting" class="animate-spin" :size="16" />
                {{ isImporting ? 'Importing...' : 'Import Employees' }}
              </button>
            </div>
          </template>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { ref, computed, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
  AlertCircle,
  AlertTriangle,
  ArrowUpDown,
  CheckCircle,
  Download,
  Eye,
  Loader2,
  MoreVertical,
  Pencil,
  Plus,
  Search,
  Trash2,
  Upload,
  Users,
  X,
} from 'lucide-vue-next'
import Layout from '@/components/Layout.vue'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

interface ImportResult {
  row: number
  name: string
  username: string | null
  temp_password: string | null
  status: 'created' | 'skipped'
  error: string | null
}

interface Employee {
  id: number
  employee_id: string
  first_name: string
  middle_name: string | null
  last_name: string
  email: string
  employment_status: string
  is_active: boolean
  department: { id: number; name: string } | null
  position: { id: number; position_name: string } | null
}

interface PaginatedEmployees {
  data: Employee[]
  current_page: number
  from: number
  to: number
  total: number
  last_page: number
  per_page: number
  links: Array<{ url: string | null; label: string; active: boolean }>
}

interface Props {
  employees: PaginatedEmployees
  departments: Array<{ id: number; name: string }>
  positions: Array<{ id: number; position_name: string; department_id: number }>
  filters: {
    search: string | null
    department_id: number | null
    position_id: number | null
    employment_status: string | null
    sort_by: string
    sort_direction: string
  }
  employmentStatuses: Array<{ value: string; label: string }>
  canEdit: boolean
  canDelete: boolean
}

const props = withDefaults(defineProps<Props>(), {
  employees: () => ({ data: [], current_page: 1, from: 0, to: 0, total: 0, last_page: 1, links: [] }),
  departments: () => [],
  positions: () => [],
  filters: () => ({ search: null, department_id: null, position_id: null, employment_status: null, sort_by: 'created_at', sort_direction: 'desc' }),
  employmentStatuses: () => [],
  canEdit: false,
  canDelete: false,
})

// Import state
const showImportModal = ref(false)
const importFile = ref<File | null>(null)
const importResults = ref<ImportResult[] | null>(null)
const isImporting = ref(false)
const importError = ref<string | null>(null)

// State
const searchQuery = ref(props.filters.search || '')
const selectedDepartment = ref<number | ''>(props.filters.department_id || '')
const selectedPosition = ref<number | ''>(props.filters.position_id || '')
const selectedStatus = ref(props.filters.employment_status || '')
const selectedEmployees = ref<number[]>([])
const showDeleteModal = ref(false)
const employeeToDelete = ref<Employee | null>(null)
const isLoading = ref(false)
const isDeleting = ref(false)
const sortField = ref(props.filters.sort_by || 'created_at')
const sortDirection = ref(props.filters.sort_direction || 'desc')
const perPage = ref<number>(props.employees.per_page ?? 5)

// Computed
const hasActiveFilters = computed(() => {
  return (
    searchQuery.value ||
    selectedDepartment.value ||
    selectedPosition.value ||
    selectedStatus.value
  )
})

const allSelected = computed(() => {
  return (
    props.employees.data?.length > 0 &&
    props.employees.data.every((emp) => selectedEmployees.value.includes(emp.id))
  )
})

const filteredPositions = computed(() => {
  if (!selectedDepartment.value) return props.positions
  return props.positions.filter((pos) => pos.department_id === selectedDepartment.value)
})

// Methods
let searchTimeout: ReturnType<typeof setTimeout> | null = null

const debouncedSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 300)
}

const applyFilters = () => {
  isLoading.value = true
  router.get(
    '/employees',
    {
      search: searchQuery.value || undefined,
      department_id: selectedDepartment.value || undefined,
      position_id: selectedPosition.value || undefined,
      employment_status: selectedStatus.value || undefined,
      sort_by: sortField.value,
      sort_direction: sortDirection.value,
      per_page: perPage.value,
    },
    {
      preserveState: true,
      preserveScroll: true,
      onFinish: () => {
        isLoading.value = false
      },
    }
  )
}

const changePerPage = () => {
  applyFilters()
}

const clearFilters = () => {
  searchQuery.value = ''
  selectedDepartment.value = ''
  selectedPosition.value = ''
  selectedStatus.value = ''
  applyFilters()
}

const sortBy = (field: string) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDirection.value = 'asc'
  }
  applyFilters()
}

const toggleSelectAll = () => {
  if (allSelected.value) {
    selectedEmployees.value = []
  } else {
    selectedEmployees.value = props.employees.data.map((emp) => emp.id)
  }
}

const toggleSelect = (id: number) => {
  const index = selectedEmployees.value.indexOf(id)
  if (index > -1) {
    selectedEmployees.value.splice(index, 1)
  } else {
    selectedEmployees.value.push(id)
  }
}

const confirmDelete = (employee: Employee) => {
  employeeToDelete.value = employee
  showDeleteModal.value = true
}

const deleteEmployee = () => {
  if (!employeeToDelete.value) return

  isDeleting.value = true
  router.delete(`/employees/${employeeToDelete.value.id}`, {
    onSuccess: () => {
      showDeleteModal.value = false
      employeeToDelete.value = null
    },
    onFinish: () => {
      isDeleting.value = false
    },
  })
}

const bulkDelete = () => {
  if (!confirm(`Are you sure you want to delete ${selectedEmployees.value.length} employees?`)) {
    return
  }
  selectedEmployees.value.forEach((id) => {
    router.delete(`/employees/${id}`, { preserveScroll: true })
  })
  selectedEmployees.value = []
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'active':
      return 'bg-green-50 text-green-700'
    case 'inactive':
      return 'bg-gray-100 text-gray-600'
    case 'resigned':
      return 'bg-yellow-50 text-yellow-700'
    case 'terminated':
      return 'bg-red-50 text-red-700'
    case 'retired':
      return 'bg-blue-50 text-blue-700'
    default:
      return 'bg-gray-50 text-gray-700'
  }
}

const formatStatus = (status: string) => {
  const found = props.employmentStatuses.find((s) => s.value === status)
  return found?.label || status
}

// Import methods
const openImportModal = (): void => {
  importFile.value = null
  importResults.value = null
  importError.value = null
  showImportModal.value = true
}

const closeImportModal = (): void => {
  showImportModal.value = false
  if (importResults.value?.some((r) => r.status === 'created')) {
    router.reload({ only: ['employees'] })
  }
}

const onFileChange = (event: Event): void => {
  const input = event.target as HTMLInputElement
  importFile.value = input.files?.[0] ?? null
  importError.value = null
}

const importEmployees = async (): Promise<void> => {
  if (!importFile.value) { return }

  isImporting.value = true
  importError.value = null

  const formData = new FormData()
  formData.append('file', importFile.value)

  const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? ''

  try {
    const response = await fetch('/employees/import', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
      body: formData,
    })

    if (!response.ok) {
      const err = await response.json().catch(() => ({}))
      importError.value = err?.message ?? `Upload failed (${response.status}). Check the file and try again.`
      return
    }

    const data = await response.json()
    importResults.value = data.results
  } catch {
    importError.value = 'An unexpected error occurred. Please try again.'
  } finally {
    isImporting.value = false
  }
}

onUnmounted(() => {
  if (searchTimeout) clearTimeout(searchTimeout)
})
</script>
