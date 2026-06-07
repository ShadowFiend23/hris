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
          class="border-b-2 border-blue-600 pb-3 text-sm font-medium text-blue-600"
        >
          Payroll Settings
        </Link>
        <Link
          href="/app-settings/allowance-types"
          class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700"
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

    <!-- Success Message -->
    <div
      v-if="$page.props.flash?.success"
      class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
    >
      {{ $page.props.flash.success }}
    </div>

    <div class="mx-auto max-w-2xl rounded-lg border border-gray-200 bg-white p-8">
      <form @submit.prevent="save">
        <div class="space-y-6">
          <!-- Period Type -->
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Period Type</label>
            <select
              v-model="form.period_type"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="semi_monthly">Semi-Monthly</option>
              <option value="monthly">Monthly</option>
              <option value="weekly">Weekly</option>
            </select>
            <p v-if="form.errors.period_type" class="mt-1 text-xs text-red-600">{{ form.errors.period_type }}</p>
          </div>

          <!-- Pay Day 1 -->
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">
              Pay Day 1
              <span class="font-normal text-gray-500">(day of month, 1–31)</span>
            </label>
            <input
              v-model.number="form.pay_day_1"
              type="number"
              min="1"
              max="31"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <p v-if="form.errors.pay_day_1" class="mt-1 text-xs text-red-600">{{ form.errors.pay_day_1 }}</p>
          </div>

          <!-- Pay Day 2 (only for semi-monthly) -->
          <div v-if="form.period_type === 'semi_monthly'">
            <label class="mb-1 block text-sm font-medium text-gray-700">
              Pay Day 2
              <span class="font-normal text-gray-500">(day of month, for semi-monthly)</span>
            </label>
            <input
              v-model.number="form.pay_day_2"
              type="number"
              min="1"
              max="31"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <p v-if="form.errors.pay_day_2" class="mt-1 text-xs text-red-600">{{ form.errors.pay_day_2 }}</p>
          </div>

          <!-- Work Days Per Month -->
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">
              Work Days Per Month
              <span class="font-normal text-gray-500">(used for daily-rate calculation)</span>
            </label>
            <input
              v-model.number="form.work_days_per_month"
              type="number"
              min="20"
              max="31"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <p v-if="form.errors.work_days_per_month" class="mt-1 text-xs text-red-600">
              {{ form.errors.work_days_per_month }}
            </p>
            <p class="mt-1 text-xs text-gray-500">Standard is 22 days for daily-rate employees per DOLE guidelines.</p>
          </div>

          <!-- Cutoff Offset Days -->
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">
              Cutoff Offset Days
              <span class="font-normal text-gray-500">(days before pay period start)</span>
            </label>
            <input
              v-model.number="form.cutoff_offset_days"
              type="number"
              min="0"
              max="31"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <p v-if="form.errors.cutoff_offset_days" class="mt-1 text-xs text-red-600">
              {{ form.errors.cutoff_offset_days }}
            </p>
            <p class="mt-1 text-xs text-gray-500">
              E.g., 15 means attendance is cut off 15 days before the pay period starts. Set to 0 to use the pay period
              dates as the attendance window.
            </p>
          </div>

          <!-- Night Differential Rate -->
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">
              Night Differential Rate
              <span class="font-normal text-gray-500">(%)</span>
            </label>
            <input
              v-model.number="form.night_differential_rate"
              type="number"
              min="0"
              max="100"
              step="0.1"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <p v-if="form.errors.night_differential_rate" class="mt-1 text-xs text-red-600">
              {{ form.errors.night_differential_rate }}
            </p>
            <p class="mt-1 text-xs text-gray-500">
              Standard is 10% per DOLE Labor Code Art. 86 for work between 10:00 PM and 6:00 AM.
            </p>
          </div>
        </div>

        <div class="mt-8 flex justify-end gap-3">
          <Link
            href="/app-settings/leave-types"
            class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
          >
            Cancel
          </Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
          >
            <Loader2 v-if="form.processing" :size="16" class="animate-spin" />
            Save Settings
          </button>
        </div>
      </form>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import { Loader2 } from 'lucide-vue-next'
import Layout from '@/components/Layout.vue'

interface PayrollSetting {
  id: number
  period_type: string
  pay_day_1: number
  pay_day_2: number | null
  work_days_per_month: number
  cutoff_offset_days: number
  night_differential_rate: number
}

const props = defineProps<{
  setting: PayrollSetting | null
}>()

const form = useForm({
  period_type: props.setting?.period_type ?? 'semi_monthly',
  pay_day_1: props.setting?.pay_day_1 ?? 15,
  pay_day_2: props.setting?.pay_day_2 ?? 30,
  work_days_per_month: props.setting?.work_days_per_month ?? 22,
  cutoff_offset_days: props.setting?.cutoff_offset_days ?? 15,
  night_differential_rate: Math.round((props.setting?.night_differential_rate ?? 0.10) * 1000) / 10,
})

const save = () => {
  form
    .transform((data) => ({
      ...data,
      night_differential_rate: data.night_differential_rate / 100,
    }))
    .post('/app-settings/payroll')
}
</script>
