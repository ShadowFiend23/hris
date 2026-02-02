<template>
  <Layout>
    <div class="max-w-6xl">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Payroll</h1>
      <p class="text-gray-600 mb-8">
        Manage salaries, payments, and payroll cycles.
      </p>
      <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
        <div class="text-6xl mb-4">💰</div>
        <h2 class="text-2xl font-bold text-gray-900 mb-2">
          Payroll Module
        </h2>
        <p class="text-gray-600 mb-6">
          This page is ready to be populated with payroll management features
        </p>
        <button class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors">
          View Payroll Details
        </button>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import Layout from '@/components/Layout.vue'
import { useApi } from '@/composables/useApi'

const { request } = useApi()

// Example function to fetch payroll data from backend
const fetchPayrollData = async () => {
  try {
    // const data = await request('/payroll')
    // Update state with data
    console.log('Payroll data would be fetched here')
  } catch (err) {
    console.error('Failed to fetch payroll data:', err)
  }
}
</script>
