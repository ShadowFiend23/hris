<template>
  <Layout>
    <div class="w-full">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-1">Team Dashboard</h1>
        <p class="text-gray-500">Overview of your team's performance today</p>
      </div>

      <!-- Team Snapshot Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500">Direct Reports</p>
              <p class="text-3xl font-bold text-gray-900 mt-1">{{ teamTotal }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <Users class="w-6 h-6 text-blue-600" />
            </div>
          </div>
          <p class="text-xs text-gray-400 mt-3">active team members</p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500">Present Today</p>
              <p class="text-3xl font-bold text-green-600 mt-1">{{ teamPresent }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <CheckCircle2 class="w-6 h-6 text-green-600" />
            </div>
          </div>
          <p class="text-xs text-gray-400 mt-3">{{ teamAttendancePct }}% attendance rate</p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500">Pending Leave</p>
              <p class="text-3xl font-bold text-amber-600 mt-1">{{ pendingLeaveCount }}</p>
            </div>
            <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
              <Calendar class="w-6 h-6 text-amber-600" />
            </div>
          </div>
          <p class="text-xs text-gray-400 mt-3">awaiting approval</p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500">Pending Overtime</p>
              <p class="text-3xl font-bold text-purple-600 mt-1">{{ pendingOtCount }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
              <Clock class="w-6 h-6 text-purple-600" />
            </div>
          </div>
          <p class="text-xs text-gray-400 mt-3">awaiting approval</p>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h2>
        <div class="flex flex-wrap gap-3">
          <Link href="/timekeeping" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
            View Team Attendance
          </Link>
          <Link href="/timekeeping" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
            Review Leave Requests
          </Link>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { Calendar, CheckCircle2, Clock, Users } from 'lucide-vue-next'
import { Link } from '@inertiajs/vue3'
import Layout from '@/components/Layout.vue'

defineProps<{
  teamTotal: number
  teamPresent: number
  teamAttendancePct: number
  pendingLeaveCount: number
  pendingOtCount: number
}>()
</script>
