<template>
  <Layout>
    <!-- Page Header -->
    <div class="mb-8">
      <Link href="/payroll" class="mb-3 inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900">
        <ChevronLeft :size="16" />
        Back to Payroll
      </Link>
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Payroll Periods</h1>
          <p class="mt-1 text-gray-600">Create and manage payroll runs.</p>
        </div>
        <button
          v-if="canRun"
          @click="showCreateForm = true"
          class="flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2 font-medium text-white transition-colors hover:bg-blue-700"
        >
          <Plus :size="20" />
          New Period
        </button>
      </div>
    </div>

    <!-- No settings warning -->
    <div
      v-if="!setting"
      class="mb-6 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4"
    >
      <AlertTriangle :size="20" class="mt-0.5 shrink-0 text-amber-600" />
      <div>
        <p class="font-medium text-amber-900">Payroll settings not configured</p>
        <p class="text-sm text-amber-700">
          Configure payroll settings before creating periods.
          <Link href="/hr-settings/payroll" class="underline">Go to settings →</Link>
        </p>
      </div>
    </div>

    <!-- Create Form Modal -->
    <div
      v-if="showCreateForm"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
      @click.self="showCreateForm = false"
    >
      <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl mx-4">
        <h2 class="mb-4 text-xl font-bold text-gray-900">New Payroll Period</h2>
        <form @submit.prevent="submitCreate">
          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Start Date</label>
                <input
                  v-model="form.start_date"
                  type="date"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                />
                <p v-if="form.errors.start_date" class="mt-1 text-xs text-red-600">
                  {{ form.errors.start_date }}
                </p>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">End Date</label>
                <input
                  v-model="form.end_date"
                  type="date"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                />
                <p v-if="form.errors.end_date" class="mt-1 text-xs text-red-600">
                  {{ form.errors.end_date }}
                </p>
              </div>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Pay Date</label>
              <input
                v-model="form.pay_date"
                type="date"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
              />
              <p v-if="form.errors.pay_date" class="mt-1 text-xs text-red-600">
                {{ form.errors.pay_date }}
              </p>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Payroll Setting</label>
              <input
                v-model="form.payroll_setting_id"
                type="hidden"
              />
              <p class="text-sm text-gray-600">
                {{ setting ? `${formatPeriodType(setting.period_type)} — Pay days: ${setting.pay_day_1}${setting.pay_day_2 ? ' & ' + setting.pay_day_2 : ''}` : 'No setting configured' }}
              </p>
              <p v-if="form.errors.payroll_setting_id" class="mt-1 text-xs text-red-600">
                {{ form.errors.payroll_setting_id }}
              </p>
            </div>
          </div>
          <div class="mt-6 flex justify-end gap-3">
            <button
              type="button"
              @click="showCreateForm = false"
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
              Create Period
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Flash Message -->
    <div
      v-if="$page.props.flash?.success"
      class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
    >
      {{ $page.props.flash.success }}
    </div>

    <!-- Periods Table -->
    <div class="rounded-lg border border-gray-200 bg-white">
      <!-- Empty State -->
      <div v-if="periods.data.length === 0" class="p-12 text-center">
        <Calendar class="mx-auto text-gray-400" :size="48" />
        <h3 class="mt-4 text-lg font-medium text-gray-900">No payroll periods yet</h3>
        <p class="mt-1 text-gray-600">Create your first payroll period to get started.</p>
      </div>

      <template v-else>
        <table class="w-full">
          <thead class="border-b border-gray-200 bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Period</th>
              <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Pay Date</th>
              <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
              <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Processed By</th>
              <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="period in periods.data"
              :key="period.id"
              class="border-b border-gray-100 hover:bg-gray-50"
            >
              <td class="px-6 py-4 text-sm">
                <Link
                  :href="`/payroll/periods/${period.id}`"
                  class="font-medium text-blue-600 hover:underline"
                >
                  {{ formatDate(period.start_date) }} – {{ formatDate(period.end_date) }}
                </Link>
              </td>
              <td class="px-6 py-4 text-sm text-gray-700">
                {{ formatDate(period.pay_date) }}
              </td>
              <td class="px-6 py-4 text-sm">
                <span :class="['inline-block rounded-full px-3 py-1 text-xs font-semibold', statusColor(period.status)]">
                  {{ period.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-700">
                {{ period.processed_by?.name ?? '—' }}
              </td>
              <td class="px-6 py-4 text-sm">
                <div class="flex items-center gap-2">
                  <Link
                    :href="`/payroll/periods/${period.id}`"
                    class="rounded px-3 py-1 text-xs font-medium text-gray-700 border border-gray-300 hover:bg-gray-50"
                  >
                    View
                  </Link>
                  <button
                    v-if="canRun && period.status === 'draft'"
                    @click="runPeriod(period.id)"
                    :disabled="runningId === period.id"
                    class="flex items-center gap-1 rounded bg-blue-600 px-3 py-1 text-xs font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                  >
                    <Loader2 v-if="runningId === period.id" :size="12" class="animate-spin" />
                    <Play v-else :size="12" />
                    Run
                  </button>
                  <button
                    v-if="canRun && period.status === 'processing'"
                    @click="finalizePeriod(period.id)"
                    :disabled="finalizingId === period.id"
                    class="flex items-center gap-1 rounded bg-green-600 px-3 py-1 text-xs font-medium text-white hover:bg-green-700 disabled:opacity-50"
                  >
                    <Loader2 v-if="finalizingId === period.id" :size="12" class="animate-spin" />
                    <CheckCircle v-else :size="12" />
                    Finalize
                  </button>
                </div>
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
            <Link
              v-for="link in periods.links"
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
            <p class="text-sm text-gray-600">{{ periods.total }} total periods</p>
          </div>
        </div>
      </template>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, usePage, useForm, router } from '@inertiajs/vue3'
import { Plus, Calendar, Play, CheckCircle, Loader2, AlertTriangle, ChevronLeft } from 'lucide-vue-next'
import Layout from '@/components/Layout.vue'

interface PayrollSetting {
  id: number
  period_type: string
  pay_day_1: number
  pay_day_2: number | null
  work_days_per_month: number
}

interface PaginatedPeriods {
  data: {
    id: number
    start_date: string
    end_date: string
    pay_date: string
    status: string
    processed_by: { name: string } | null
  }[]
  from: number | null
  to: number | null
  total: number
  per_page: number
  links: { label: string; url: string | null; active: boolean }[]
}

const props = defineProps<{
  periods: PaginatedPeriods
  setting: PayrollSetting | null
}>()

const page = usePage()
const permissions = computed<string[]>(() => (page.props.auth as any)?.permissions ?? [])
const canRun = computed(() => permissions.value.includes('payroll.run'))

const showCreateForm = ref(false)
const runningId = ref<number | null>(null)
const finalizingId = ref<number | null>(null)
const perPage = ref<number>(props.periods.per_page ?? 5)

const changePerPage = () => {
  router.get('/payroll/periods', { per_page: perPage.value, page: 1 }, { preserveState: true, preserveScroll: true })
}

const form = useForm({
  start_date: '',
  end_date: '',
  pay_date: '',
  payroll_setting_id: props.setting?.id ?? null,
})

const submitCreate = () => {
  form.payroll_setting_id = props.setting?.id ?? null
  form.post('/payroll/periods', {
    onSuccess: () => {
      showCreateForm.value = false
      form.reset()
    },
  })
}

const runPeriod = (id: number) => {
  runningId.value = id
  router.post(`/payroll/periods/${id}/run`, {}, {
    onFinish: () => { runningId.value = null },
  })
}

const finalizePeriod = (id: number) => {
  finalizingId.value = id
  router.post(`/payroll/periods/${id}/finalize`, {}, {
    onFinish: () => { finalizingId.value = null },
  })
}

const formatDate = (date: string) => new Date(date).toLocaleDateString('en-PH', {
  year: 'numeric', month: 'short', day: 'numeric',
})

const formatPeriodType = (type: string) =>
  ({ weekly: 'Weekly', semi_monthly: 'Semi-Monthly', monthly: 'Monthly' })[type] ?? type

const statusColor = (status: string) => ({
  draft: 'bg-gray-100 text-gray-700',
  processing: 'bg-blue-100 text-blue-700',
  finalized: 'bg-green-100 text-green-700',
  cancelled: 'bg-red-100 text-red-700',
})[status] ?? 'bg-gray-100 text-gray-700'
</script>
