<template>
  <Layout>
    <!-- Page Header -->
    <div class="mb-8">
      <div class="flex items-center gap-4 mb-2">
        <Link
          href="/employees"
          class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
        >
          <ArrowLeft :size="20" />
        </Link>
        <h1 class="text-3xl font-bold text-gray-900">
          {{ mode === 'create' ? 'Add New Employee' : 'Edit Employee' }}
        </h1>
      </div>
      <p class="text-gray-600 ml-12">
        {{ mode === 'create' ? 'Fill in the details to create a new employee record' : 'Update the employee information' }}
      </p>
    </div>

    <!-- Form -->
    <form @submit.prevent="submitForm" class="space-y-8">
      <!-- Profile Photo -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
          <User :size="20" class="text-blue-600" />
          Profile Photo
        </h2>
        <div class="flex items-center gap-6">
          <!-- Preview -->
          <div class="shrink-0">
            <img
              v-if="photoPreview"
              :src="photoPreview"
              class="w-20 h-20 rounded-full object-cover border-2 border-blue-200"
              alt="Profile preview"
            />
            <div
              v-else-if="existingPhotoUrl"
              class="w-20 h-20 rounded-full overflow-hidden border-2 border-blue-200"
            >
              <img :src="existingPhotoUrl" class="w-full h-full object-cover" alt="Current photo" />
            </div>
            <div
              v-else
              class="w-20 h-20 rounded-full bg-blue-600 flex items-center justify-center text-white text-2xl font-bold border-2 border-blue-200"
            >
              {{ currentInitials }}
            </div>
          </div>
          <!-- Upload -->
          <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-2">Upload Photo</label>
            <input
              ref="photoInput"
              type="file"
              accept="image/*"
              class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
              @change="onPhotoChange"
            />
            <p class="text-xs text-gray-400 mt-1">JPG, PNG or GIF · max 2MB</p>
            <p v-if="form.errors.profile_photo" class="text-red-500 text-sm mt-1">{{ form.errors.profile_photo }}</p>
          </div>
        </div>
      </div>

      <!-- Personal Information -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
          <User :size="20" class="text-blue-600" />
          Personal Information
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- First Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              First Name <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.first_name"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': form.errors.first_name }"
              placeholder="Enter first name"
            />
            <p v-if="form.errors.first_name" class="text-red-500 text-sm mt-1">{{ form.errors.first_name }}</p>
          </div>

          <!-- Middle Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
            <input
              v-model="form.middle_name"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Enter middle name"
            />
          </div>

          <!-- Last Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Last Name <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.last_name"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': form.errors.last_name }"
              placeholder="Enter last name"
            />
            <p v-if="form.errors.last_name" class="text-red-500 text-sm mt-1">{{ form.errors.last_name }}</p>
          </div>

          <!-- Date of Birth -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
            <input
              v-model="form.date_of_birth"
              type="date"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': form.errors.date_of_birth }"
            />
            <p v-if="form.errors.date_of_birth" class="text-red-500 text-sm mt-1">{{ form.errors.date_of_birth }}</p>
          </div>

          <!-- Gender -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
            <select
              v-model="form.gender"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
            >
              <option value="">Select gender</option>
              <option v-for="option in genderOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
          </div>

          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Email <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.email"
              type="email"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': form.errors.email }"
              placeholder="employee@company.com"
            />
            <p v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</p>
          </div>

          <!-- Phone -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
            <input
              v-model="form.phone"
              type="tel"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="+63 XXX XXX XXXX"
            />
          </div>
        </div>
      </div>

      <!-- Address Information -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
          <MapPin :size="20" class="text-blue-600" />
          Address Information
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Street Address -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Street Address</label>
            <textarea
              v-model="form.address"
              rows="2"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Enter street address"
            />
          </div>

          <!-- City -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
            <input
              v-model="form.city"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Enter city"
            />
          </div>

          <!-- Province -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Province</label>
            <input
              v-model="form.province"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Enter province"
            />
          </div>

          <!-- Postal Code -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Postal Code</label>
            <input
              v-model="form.postal_code"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="XXXX"
            />
          </div>
        </div>
      </div>

      <!-- Employment Details -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
          <Briefcase :size="20" class="text-blue-600" />
          Employment Details
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Employee ID -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Employee ID
              <span class="text-gray-400 text-xs ml-1">(auto-generated if empty)</span>
            </label>
            <input
              v-model="form.employee_id"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': form.errors.employee_id }"
              placeholder="EMP-XXXX"
            />
            <p v-if="form.errors.employee_id" class="text-red-500 text-sm mt-1">{{ form.errors.employee_id }}</p>
          </div>

          <!-- Department -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Department <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.department_id"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
              :class="{ 'border-red-500': form.errors.department_id }"
              @change="onDepartmentChange"
            >
              <option value="">Select department</option>
              <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                {{ dept.name }}
              </option>
            </select>
            <p v-if="form.errors.department_id" class="text-red-500 text-sm mt-1">{{ form.errors.department_id }}</p>
          </div>

          <!-- Position -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Position <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.position_id"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
              :class="{ 'border-red-500': form.errors.position_id }"
            >
              <option value="">Select position</option>
              <option v-for="pos in filteredPositions" :key="pos.id" :value="pos.id">
                {{ pos.position_name }}
              </option>
            </select>
            <p v-if="form.errors.position_id" class="text-red-500 text-sm mt-1">{{ form.errors.position_id }}</p>
          </div>

          <!-- Date Hired -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Date Hired <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.date_hired"
              type="date"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': form.errors.date_hired }"
            />
            <p v-if="form.errors.date_hired" class="text-red-500 text-sm mt-1">{{ form.errors.date_hired }}</p>
          </div>

          <!-- Employment Status -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Employment Status</label>
            <select
              v-model="form.employment_status"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
            >
              <option v-for="status in employmentStatuses" :key="status.value" :value="status.value">
                {{ status.label }}
              </option>
            </select>
          </div>

          <!-- Employment Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Employment Type</label>
            <select
              v-model="form.employment_type"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
            >
              <option value="">Select type</option>
              <option v-for="type in employmentTypes" :key="type.value" :value="type.value">
                {{ type.label }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Compensation -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
          <PhilippinePeso :size="20" class="text-blue-600" />
          Compensation
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Salary -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Monthly Salary (₱)</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500">PHP</span>
              <input
                v-model="form.salary"
                type="number"
                step="0.01"
                min="0"
                class="w-full pl-14 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                :class="{ 'border-red-500': form.errors.salary }"
                placeholder="0.00"
              />
            </div>
            <p v-if="form.errors.salary" class="text-red-500 text-sm mt-1">{{ form.errors.salary }}</p>
          </div>

          <!-- Bank Account -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Bank Account Number</label>
            <input
              v-model="form.bank_account"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Enter bank account number"
            />
          </div>
        </div>
      </div>

      <!-- Government IDs -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
          <FileText :size="20" class="text-blue-600" />
          Government IDs
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- TIN -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">TIN (Tax Identification Number)</label>
            <input
              v-model="form.tin"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': form.errors.tin }"
              placeholder="XXX-XXX-XXX-XXX"
            />
            <p v-if="form.errors.tin" class="text-red-500 text-sm mt-1">{{ form.errors.tin }}</p>
          </div>

          <!-- SSS -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">SSS Number</label>
            <input
              v-model="form.sss_number"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': form.errors.sss_number }"
              placeholder="XX-XXXXXXX-X"
            />
            <p v-if="form.errors.sss_number" class="text-red-500 text-sm mt-1">{{ form.errors.sss_number }}</p>
          </div>

          <!-- PhilHealth -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">PhilHealth Number</label>
            <input
              v-model="form.philhealth_number"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': form.errors.philhealth_number }"
              placeholder="XX-XXXXXXXXX-X"
            />
            <p v-if="form.errors.philhealth_number" class="text-red-500 text-sm mt-1">{{ form.errors.philhealth_number }}</p>
          </div>

          <!-- Pag-IBIG -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Pag-IBIG Number</label>
            <input
              v-model="form.pagibig_number"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': form.errors.pagibig_number }"
              placeholder="XXXX-XXXX-XXXX"
            />
            <p v-if="form.errors.pagibig_number" class="text-red-500 text-sm mt-1">{{ form.errors.pagibig_number }}</p>
          </div>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="flex items-center justify-end gap-4">
        <Link
          href="/employees"
          class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
        >
          Cancel
        </Link>
        <button
          type="submit"
          :disabled="form.processing"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center gap-2"
        >
          <Loader2 v-if="form.processing" class="animate-spin" :size="16" />
          <Save v-else :size="16" />
          {{ mode === 'create' ? 'Create Employee' : 'Save Changes' }}
        </button>
      </div>
    </form>
  </Layout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import {
  ArrowLeft,
  User,
  MapPin,
  Briefcase,
  PhilippinePeso,
  FileText,
  Save,
  Loader2,
} from 'lucide-vue-next'
import Layout from '@/components/Layout.vue'

interface Employee {
  id: number
  employee_id: string
  first_name: string
  middle_name: string | null
  last_name: string
  email: string
  phone: string | null
  date_of_birth: string | null
  gender: string | null
  address: string | null
  city: string | null
  province: string | null
  postal_code: string | null
  date_hired: string
  employment_status: string
  employment_type: string | null
  salary: number | null
  bank_account: string | null
  tin: string | null
  sss_number: string | null
  philhealth_number: string | null
  pagibig_number: string | null
  department_id: number
  position_id: number
  profile_photo_url: string | null
}

interface Props {
  mode: 'create' | 'edit'
  employee?: Employee
  departments: Array<{ id: number; name: string }>
  positions: Array<{ id: number; position_name: string; department_id: number }>
  employmentStatuses: Array<{ value: string; label: string }>
  employmentTypes: Array<{ value: string; label: string }>
  genderOptions: Array<{ value: string; label: string }>
}

const props = withDefaults(defineProps<Props>(), {
  departments: () => [],
  positions: () => [],
  employmentStatuses: () => [],
  employmentTypes: () => [],
  genderOptions: () => [],
})

// Initialize form with employee data if editing
const photoInput = ref<HTMLInputElement | null>(null)
const photoPreview = ref<string | null>(null)
const existingPhotoUrl = computed(() => props.employee?.profile_photo_url ?? null)

const currentInitials = computed(() => {
  const first = form.first_name?.charAt(0) ?? ''
  const last = form.last_name?.charAt(0) ?? ''
  return (first + last).toUpperCase() || '?'
})

const onPhotoChange = (e: Event) => {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) {
    return
  }
  form.profile_photo = file
  const reader = new FileReader()
  reader.onload = (ev) => {
    photoPreview.value = ev.target?.result as string
  }
  reader.readAsDataURL(file)
}

const form = useForm({
  employee_id: props.employee?.employee_id || '',
  first_name: props.employee?.first_name || '',
  middle_name: props.employee?.middle_name || '',
  last_name: props.employee?.last_name || '',
  email: props.employee?.email || '',
  phone: props.employee?.phone || '',
  date_of_birth: props.employee?.date_of_birth || '',
  gender: props.employee?.gender || '',
  address: props.employee?.address || '',
  city: props.employee?.city || '',
  province: props.employee?.province || '',
  postal_code: props.employee?.postal_code || '',
  date_hired: props.employee?.date_hired || '',
  employment_status: props.employee?.employment_status || 'active',
  employment_type: props.employee?.employment_type || '',
  salary: props.employee?.salary || null,
  bank_account: props.employee?.bank_account || '',
  tin: props.employee?.tin || '',
  sss_number: props.employee?.sss_number || '',
  philhealth_number: props.employee?.philhealth_number || '',
  pagibig_number: props.employee?.pagibig_number || '',
  department_id: props.employee?.department_id || '',
  position_id: props.employee?.position_id || '',
  profile_photo: null as File | null,
})

// Filter positions by selected department
const filteredPositions = computed(() => {
  if (!form.department_id) return props.positions
  return props.positions.filter((pos) => pos.department_id === Number(form.department_id))
})

// Reset position when department changes
const onDepartmentChange = () => {
  const validPosition = filteredPositions.value.find((pos) => pos.id === Number(form.position_id))
  if (!validPosition) {
    form.position_id = ''
  }
}

// Submit form
const submitForm = () => {
  if (props.mode === 'create') {
    form.post('/employees', {
      preserveScroll: true,
    })
  } else {
    form
      .transform((data) => ({ ...data, _method: 'PUT' }))
      .post(`/employees/${props.employee?.id}`, {
        preserveScroll: true,
      })
  }
}
</script>
