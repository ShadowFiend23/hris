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
          class="border-b-2 border-blue-600 pb-3 text-sm font-medium text-blue-600"
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

    <!-- Tab header -->
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-900">Holidays</h2>
        <p class="mt-1 text-gray-600">Philippine national and company holidays used in payroll calculations.</p>
      </div>
      <button
        v-if="canManage"
        @click="openCreate"
        class="flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2 font-medium text-white transition-colors hover:bg-blue-700"
      >
        <Plus :size="20" />
        Add Holiday
      </button>
    </div>

    <!-- Flash -->
    <div
      v-if="$page.props.flash?.success"
      class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
    >
      {{ $page.props.flash.success }}
    </div>

    <!-- Create / Edit Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
      @click.self="closeModal"
    >
      <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl mx-4">
        <h2 class="mb-4 text-xl font-bold text-gray-900">
          {{ editingHoliday ? 'Edit Holiday' : 'Add Holiday' }}
        </h2>
        <form @submit.prevent="submitForm">
          <div class="space-y-4">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Name</label>
              <input
                v-model="form.name"
                type="text"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
              />
              <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Date</label>
                <input
                  v-model="form.date"
                  type="date"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                />
                <p v-if="form.errors.date" class="mt-1 text-xs text-red-600">{{ form.errors.date }}</p>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Type</label>
                <select
                  v-model="form.type"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="regular">Regular Holiday (200%)</option>
                  <option value="special">Special Non-Working (130%)</option>
                </select>
                <p v-if="form.errors.type" class="mt-1 text-xs text-red-600">{{ form.errors.type }}</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <input
                id="is_recurring"
                v-model="form.is_recurring"
                type="checkbox"
                class="rounded border-gray-300"
              />
              <label for="is_recurring" class="text-sm text-gray-700">Recurring every year</label>
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
              {{ editingHoliday ? 'Update' : 'Add Holiday' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div
      v-if="deletingHoliday"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
      @click.self="deletingHoliday = null"
    >
      <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl mx-4">
        <div class="mb-4 flex items-center gap-3">
          <div class="rounded-full bg-red-100 p-2">
            <AlertTriangle class="text-red-600" :size="24" />
          </div>
          <h3 class="text-lg font-bold text-gray-900">Remove Holiday</h3>
        </div>
        <p class="mb-6 text-gray-600">
          Remove <strong>{{ deletingHoliday.name }}</strong>? It will no longer affect payroll calculations.
        </p>
        <div class="flex justify-end gap-3">
          <button
            @click="deletingHoliday = null"
            class="rounded-lg border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            @click="confirmDelete"
            class="rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700"
          >
            Remove
          </button>
        </div>
      </div>
    </div>

    <!-- Holidays Table -->
    <div class="rounded-lg border border-gray-200 bg-white">
      <!-- Empty State -->
      <div v-if="holidays.length === 0" class="p-12 text-center">
        <Star class="mx-auto text-gray-400" :size="48" />
        <h3 class="mt-4 text-lg font-medium text-gray-900">No holidays configured</h3>
        <p class="mt-1 text-gray-600">Add company holidays or they will be seeded from PH national holidays.</p>
      </div>

      <template v-else>
        <table class="w-full">
          <thead class="border-b border-gray-200 bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Name</th>
              <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Date</th>
              <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Type</th>
              <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Scope</th>
              <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Recurring</th>
              <th v-if="canManage" class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="holiday in paginatedHolidays"
              :key="holiday.id"
              class="border-b border-gray-100 hover:bg-gray-50"
            >
              <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ holiday.name }}</td>
              <td class="px-6 py-4 text-sm text-gray-700">{{ formatDate(holiday.date) }}</td>
              <td class="px-6 py-4 text-sm">
                <span :class="['rounded-full px-3 py-1 text-xs font-semibold', holiday.type === 'regular' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700']">
                  {{ holiday.type === 'regular' ? 'Regular (200%)' : 'Special (130%)' }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-700">
                {{ holiday.company_id ? 'Company' : 'National' }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-700">
                {{ holiday.is_recurring ? 'Yes' : 'No' }}
              </td>
              <td v-if="canManage" class="px-6 py-4 text-sm">
                <div v-if="holiday.company_id" class="flex items-center gap-2">
                  <button
                    @click="openEdit(holiday)"
                    class="rounded px-3 py-1 text-xs font-medium text-gray-700 border border-gray-300 hover:bg-gray-50"
                  >
                    Edit
                  </button>
                  <button
                    @click="deletingHoliday = holiday"
                    class="rounded px-3 py-1 text-xs font-medium text-red-600 border border-red-200 hover:bg-red-50"
                  >
                    Remove
                  </button>
                </div>
                <span v-else class="text-xs text-gray-400">National</span>
              </td>
            </tr>
          </tbody>
        </table>
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
            <button
              @click="currentPage--"
              :disabled="currentPage <= 1"
              class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400"
              v-html="'&laquo;'"
            />
            <button
              v-for="p in totalPages"
              :key="p"
              @click="currentPage = p"
              :class="[
                'rounded-lg px-3 py-1 text-sm font-medium transition-colors',
                currentPage === p ? 'bg-blue-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50',
              ]"
            >{{ p }}</button>
            <button
              @click="currentPage++"
              :disabled="currentPage >= totalPages"
              class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400"
              v-html="'&raquo;'"
            />
          </div>
          <div class="flex w-1/3 justify-end">
            <p class="text-sm text-gray-600">{{ holidays.length }} total holidays</p>
          </div>
        </div>
      </template>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, usePage, useForm, router } from '@inertiajs/vue3'
import { Plus, Loader2, AlertTriangle, Star } from 'lucide-vue-next'
import Layout from '@/components/Layout.vue'

interface Holiday {
  id: number
  name: string
  date: string
  type: string
  company_id: number | null
  is_recurring: boolean
  is_active: boolean
}

const props = defineProps<{
  holidays: Holiday[]
}>()

const page = usePage()
const permissions = computed<string[]>(() => (page.props.auth as any)?.permissions ?? [])
const canManage = computed(() => permissions.value.includes('payroll.holidays'))

const perPage = ref(5)
const currentPage = ref(1)
const totalPages = computed(() => Math.ceil(props.holidays.length / perPage.value))
const paginatedHolidays = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return props.holidays.slice(start, start + perPage.value)
})
const changePerPage = () => { currentPage.value = 1 }

const showModal = ref(false)
const editingHoliday = ref<Holiday | null>(null)
const deletingHoliday = ref<Holiday | null>(null)

const form = useForm({
  name: '',
  date: '',
  type: 'regular',
  is_recurring: false,
  is_active: true,
})

const openCreate = () => {
  editingHoliday.value = null
  form.reset()
  showModal.value = true
}

const openEdit = (holiday: Holiday) => {
  editingHoliday.value = holiday
  form.name = holiday.name
  form.date = holiday.date
  form.type = holiday.type
  form.is_recurring = holiday.is_recurring
  form.is_active = holiday.is_active
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingHoliday.value = null
  form.reset()
}

const submitForm = () => {
  if (editingHoliday.value) {
    form.put(`/hr-settings/holidays/${editingHoliday.value.id}`, {
      onSuccess: closeModal,
    })
  } else {
    form.post('/hr-settings/holidays', {
      onSuccess: closeModal,
    })
  }
}

const confirmDelete = () => {
  if (!deletingHoliday.value) { return }
  router.delete(`/hr-settings/holidays/${deletingHoliday.value.id}`, {
    onSuccess: () => { deletingHoliday.value = null },
  })
}

const formatDate = (date: string) =>
  new Date(date).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })
</script>
