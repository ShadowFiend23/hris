<template>
  <Layout>
    <!-- Page Header -->
    <div class="mb-8 flex items-center justify-between">
      <h1 class="text-3xl font-bold text-gray-900">Employees</h1>
      <button class="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors">
        + Add Employee
      </button>
    </div>

    <!-- Search and Filter Bar -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6 flex gap-4">
      <div class="flex-1 relative">
        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" :size="20" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search by name or employee no..."
          class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
      <button class="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
        <Filter :size="20" />
        Filter
      </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-6 py-3 text-left">
                <input type="checkbox" class="rounded border-gray-300" />
              </th>
              <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">
                Employee No
              </th>
              <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">
                Name
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
              v-for="(employee, index) in paginatedEmployees"
              :key="employee.id"
              :class="[
                'border-b border-gray-200 hover:bg-gray-50 transition-colors',
                index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
              ]"
            >
              <td class="px-6 py-4">
                <input type="checkbox" class="rounded border-gray-300" />
              </td>
              <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                {{ employee.employeeNo }}
              </td>
              <td class="px-6 py-4 text-sm">
                <Link
                  :to="`/employees/${employee.id}`"
                  class="text-gray-900 font-medium hover:text-blue-600"
                >
                  {{ employee.name }}
                </Link>
              </td>
              <td class="px-6 py-4 text-sm text-gray-700">
                {{ employee.department }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-700">
                {{ employee.position }}
              </td>
              <td class="px-6 py-4 text-sm">
                <span
                  :class="[
                    'inline-block px-3 py-1 rounded-full text-xs font-semibold',
                    getStatusColor(employee.status),
                  ]"
                >
                  {{ employee.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-700">
                <button class="text-gray-500 hover:text-gray-900">
                  <MoreVertical :size="20" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="bg-white px-6 py-4 border-t border-gray-200 flex items-center justify-between">
        <p class="text-sm text-gray-600">
          Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to
          {{ Math.min(currentPage * itemsPerPage, filteredEmployees.length) }} of
          {{ filteredEmployees.length }} results
        </p>
        <div class="flex gap-2">
          <button
            @click="currentPage = Math.max(1, currentPage - 1)"
            :disabled="currentPage === 1"
            class="px-3 py-1 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            ←
          </button>
          <button
            v-for="page in totalPages"
            :key="page"
            @click="currentPage = page"
            :class="[
              'px-3 py-1 rounded-lg font-medium transition-colors',
              currentPage === page
                ? 'bg-blue-600 text-white'
                : 'border border-gray-300 text-gray-700 hover:bg-gray-50',
            ]"
          >
            {{ page }}
          </button>
          <button
            @click="currentPage = Math.min(totalPages, currentPage + 1)"
            :disabled="currentPage === totalPages"
            class="px-3 py-1 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            →
          </button>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Search, Filter, MoreVertical } from 'lucide-vue-next'
import Layout from '@/components/Layout.vue'
import { useApi } from '@/composables/useApi'

const { request } = useApi()

interface Employee {
  id: string
  name: string
  employeeNo: string
  department: string
  position: string
  status: 'Active' | 'On Leave' | 'Terminated'
}

const searchQuery = ref('')
const currentPage = ref(1)
const itemsPerPage = 5

const mockEmployees: Employee[] = [
  {
    id: '1',
    employeeNo: 'EMP-001',
    name: 'John Doe',
    department: 'Engineering',
    position: 'Senior Software Engineer',
    status: 'Active',
  },
  {
    id: '2',
    employeeNo: 'EMP-002',
    name: 'Jane Smith',
    department: 'Sales',
    position: 'Sales Manager',
    status: 'Active',
  },
  {
    id: '3',
    employeeNo: 'EMP-003',
    name: 'Peter Jones',
    department: 'Engineering',
    position: 'Frontend Developer',
    status: 'On Leave',
  },
  {
    id: '4',
    employeeNo: 'EMP-004',
    name: 'Mary Johnson',
    department: 'Marketing',
    position: 'Marketing Specialist',
    status: 'Active',
  },
  {
    id: '5',
    employeeNo: 'EMP-005',
    name: 'David Williams',
    department: 'Human Resources',
    position: 'HR Generalist',
    status: 'Terminated',
  },
]

const filteredEmployees = computed(() =>
  mockEmployees.filter(
    (emp) =>
      emp.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      emp.employeeNo.toLowerCase().includes(searchQuery.value.toLowerCase()),
  ),
)

const totalPages = computed(() =>
  Math.ceil(filteredEmployees.value.length / itemsPerPage),
)

const paginatedEmployees = computed(() =>
  filteredEmployees.value.slice(
    (currentPage.value - 1) * itemsPerPage,
    currentPage.value * itemsPerPage,
  ),
)

const getStatusColor = (status: string) => {
  switch (status) {
    case 'Active':
      return 'bg-green-50 text-green-700'
    case 'On Leave':
      return 'bg-yellow-50 text-yellow-700'
    case 'Terminated':
      return 'bg-red-50 text-red-700'
    default:
      return 'bg-gray-50 text-gray-700'
  }
}

// Example function to fetch employees from backend
const fetchEmployeeList = async () => {
  try {
    // const data = await request('/employees')
    // Update employees with fetched data
    console.log('Employee list would be fetched here')
  } catch (err) {
    console.error('Failed to fetch employees:', err)
  }
}
</script>
