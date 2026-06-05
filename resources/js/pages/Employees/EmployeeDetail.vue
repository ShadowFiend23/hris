<template>
  <Layout>
    <!-- Page Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Link
            href="/employees"
            class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <ArrowLeft :size="20" />
          </Link>
          <div class="flex items-center gap-4">
            <!-- Avatar -->
            <img
              v-if="employee.profile_photo_url"
              :src="employee.profile_photo_url"
              class="w-14 h-14 rounded-full object-cover border-2 border-blue-200 shrink-0"
              alt="Profile photo"
            />
            <div
              v-else
              class="w-14 h-14 rounded-full bg-blue-600 flex items-center justify-center text-white text-xl font-bold border-2 border-blue-200 shrink-0"
            >
              {{ (employee.first_name[0] + employee.last_name[0]).toUpperCase() }}
            </div>
            <div>
              <h1 class="text-3xl font-bold text-gray-900">
                {{ employee.first_name }} {{ employee.middle_name }} {{ employee.last_name }}
              </h1>
              <p class="text-gray-600">{{ employee.employee_id }} - {{ employee.position?.position_name }}</p>
            </div>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <Link
            :href="`/employees/${employee.id}/edit`"
            class="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
          >
            <Pencil :size="16" />
            Edit
          </Link>
          <button
            @click="showDeleteModal = true"
            class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
          >
            <Trash2 :size="16" />
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Status Badge -->
    <div class="mb-6">
      <span
        :class="[
          'inline-block px-4 py-2 rounded-full text-sm font-semibold',
          getStatusColor(employee.employment_status),
        ]"
      >
        {{ formatStatus(employee.employment_status) }}
      </span>
      <span
        v-if="!employee.is_active"
        class="inline-block ml-2 px-4 py-2 rounded-full text-sm font-semibold bg-gray-100 text-gray-700"
      >
        Inactive
      </span>
    </div>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200">
      <nav class="flex gap-8">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'pb-4 text-sm font-medium transition-colors relative',
            activeTab === tab.id
              ? 'text-blue-600'
              : 'text-gray-500 hover:text-gray-700',
          ]"
        >
          {{ tab.label }}
          <div
            v-if="activeTab === tab.id"
            class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600"
          />
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div v-if="activeTab === 'overview'" class="space-y-6">
      <!-- Personal Information -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
          <User :size="20" class="text-blue-600" />
          Personal Information
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <p class="text-sm text-gray-500">Full Name</p>
            <p class="font-medium text-gray-900">
              {{ employee.first_name }} {{ employee.middle_name }} {{ employee.last_name }}
            </p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Email</p>
            <p class="font-medium text-gray-900">{{ employee.email }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Phone</p>
            <p class="font-medium text-gray-900">{{ employee.phone || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Date of Birth</p>
            <p class="font-medium text-gray-900">{{ formatDate(employee.date_of_birth) }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Gender</p>
            <p class="font-medium text-gray-900 capitalize">{{ employee.gender || '-' }}</p>
          </div>
        </div>
      </div>

      <!-- Employment Information -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
          <Briefcase :size="20" class="text-blue-600" />
          Employment Information
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <p class="text-sm text-gray-500">Employee ID</p>
            <p class="font-medium text-gray-900">{{ employee.employee_id }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Department</p>
            <p class="font-medium text-gray-900">{{ employee.department?.name || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Position</p>
            <p class="font-medium text-gray-900">{{ employee.position?.position_name || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Date Hired</p>
            <p class="font-medium text-gray-900">{{ formatDate(employee.date_hired) }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Employment Status</p>
            <p class="font-medium text-gray-900 capitalize">{{ formatStatus(employee.employment_status) }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Employment Type</p>
            <p class="font-medium text-gray-900 capitalize">{{ formatEmploymentType(employee.employment_type) }}</p>
          </div>
          <div v-if="employee.date_resigned">
            <p class="text-sm text-gray-500">Date Resigned</p>
            <p class="font-medium text-gray-900">{{ formatDate(employee.date_resigned) }}</p>
          </div>
        </div>
      </div>

      <!-- Address Information -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
          <MapPin :size="20" class="text-blue-600" />
          Address Information
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="md:col-span-2">
            <p class="text-sm text-gray-500">Street Address</p>
            <p class="font-medium text-gray-900">{{ employee.address || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">City</p>
            <p class="font-medium text-gray-900">{{ employee.city || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Province</p>
            <p class="font-medium text-gray-900">{{ employee.province || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Postal Code</p>
            <p class="font-medium text-gray-900">{{ employee.postal_code || '-' }}</p>
          </div>
        </div>
      </div>

      <!-- Compensation -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
          <PhilippinePeso :size="20" class="text-blue-600" />
          Compensation
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <p class="text-sm text-gray-500">Monthly Salary</p>
            <p class="font-medium text-gray-900 text-xl">{{ formatCurrency(employee.salary) }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Bank Account</p>
            <p class="font-medium text-gray-900">{{ employee.bank_account || '-' }}</p>
          </div>
        </div>
      </div>

      <!-- Government IDs -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
          <FileText :size="20" class="text-blue-600" />
          Government IDs
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <p class="text-sm text-gray-500">TIN</p>
            <p class="font-medium text-gray-900">{{ employee.tin || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">SSS Number</p>
            <p class="font-medium text-gray-900">{{ employee.sss_number || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">PhilHealth Number</p>
            <p class="font-medium text-gray-900">{{ employee.philhealth_number || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Pag-IBIG Number</p>
            <p class="font-medium text-gray-900">{{ employee.pagibig_number || '-' }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Timekeeping Tab -->
    <div v-else-if="activeTab === 'timekeeping'" class="space-y-6">
      <!-- Loading State -->
      <div v-if="timekeepingLoading" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div v-for="i in 3" :key="i" class="bg-white rounded-lg border border-gray-200 p-6 animate-pulse">
            <div class="h-4 bg-gray-200 rounded w-32 mb-4" />
            <div class="h-8 bg-gray-200 rounded w-24" />
          </div>
        </div>
      </div>

      <template v-else>
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center gap-3 mb-4">
              <div class="p-2 bg-blue-100 rounded-lg">
                <CalendarCheck :size="20" class="text-blue-600" />
              </div>
              <h3 class="font-bold text-gray-900">Attendance Summary</h3>
            </div>
            <div class="space-y-2">
              <div class="flex justify-between">
                <span class="text-gray-600">Days Present</span>
                <span class="font-semibold">{{ timekeepingData.attendance.daysPresent }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Days Late</span>
                <span class="font-semibold text-amber-600">{{ timekeepingData.attendance.daysLate }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Days Absent</span>
                <span class="font-semibold text-red-600">{{ timekeepingData.attendance.daysAbsent }}</span>
              </div>
              <div class="flex justify-between pt-2 border-t">
                <span class="text-gray-600">Total Hours</span>
                <span class="font-semibold">{{ timekeepingData.attendance.totalHours }}h</span>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center gap-3 mb-4">
              <div class="p-2 bg-green-100 rounded-lg">
                <CalendarDays :size="20" class="text-green-600" />
              </div>
              <h3 class="font-bold text-gray-900">Leave Balance</h3>
            </div>
            <div class="space-y-2">
              <div v-if="timekeepingData.leave.balances.length === 0" class="text-gray-500 text-sm">
                No leave balances configured
              </div>
              <div v-for="balance in timekeepingData.leave.balances.slice(0, 4)" :key="balance.id" class="flex justify-between">
                <span class="text-gray-600">{{ balance.leave_type?.name || 'Leave' }}</span>
                <span class="font-semibold">{{ balance.remaining_days }}/{{ balance.total_days }}</span>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center gap-3 mb-4">
              <div class="p-2 bg-purple-100 rounded-lg">
                <Timer :size="20" class="text-purple-600" />
              </div>
              <h3 class="font-bold text-gray-900">Overtime Hours</h3>
            </div>
            <div class="space-y-2">
              <div class="flex justify-between">
                <span class="text-gray-600">This Month</span>
                <span class="font-semibold">{{ timekeepingData.overtime.thisMonth }}h</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Pending</span>
                <span class="font-semibold text-amber-600">{{ timekeepingData.overtime.pending }}h</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Approved</span>
                <span class="font-semibold text-green-600">{{ timekeepingData.overtime.approved }}h</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <h3 class="font-bold text-gray-900 mb-4">Recent Attendance</h3>
          <div v-if="timekeepingData.attendance.recent.length === 0" class="text-center py-8 text-gray-500">
            No recent attendance records
          </div>
          <div v-else class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b border-gray-200">
                  <th class="text-left py-3 px-4 font-medium text-gray-900">Date</th>
                  <th class="text-left py-3 px-4 font-medium text-gray-900">Clock In</th>
                  <th class="text-left py-3 px-4 font-medium text-gray-900">Clock Out</th>
                  <th class="text-left py-3 px-4 font-medium text-gray-900">Hours</th>
                  <th class="text-left py-3 px-4 font-medium text-gray-900">Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="record in timekeepingData.attendance.recent" :key="record.id" class="border-b border-gray-200">
                  <td class="py-3 px-4 text-gray-900">{{ formatDate(record.date) }}</td>
                  <td class="py-3 px-4 text-gray-600">{{ formatTime(record.clock_in) }}</td>
                  <td class="py-3 px-4 text-gray-600">{{ formatTime(record.clock_out) }}</td>
                  <td class="py-3 px-4 text-gray-900">{{ record.total_hours?.toFixed(1) || '0' }}h</td>
                  <td class="py-3 px-4">
                    <span :class="['inline-flex items-center px-2 py-1 rounded text-xs font-medium', getAttendanceStatusClass(record.status)]">
                      {{ formatAttendanceStatus(record.status) }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </div>

    <!-- Payroll Tab -->
    <div v-else-if="activeTab === 'payroll'" class="space-y-6">
      <!-- Salary Summary Card -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="p-2 bg-blue-100 rounded-lg">
              <PhilippinePeso :size="20" class="text-blue-600" />
            </div>
            <h3 class="font-bold text-gray-900">Salary Details</h3>
          </div>
          <div v-if="employee.salary" class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-gray-600">Base Salary</span>
              <span class="text-sm font-semibold text-gray-900">{{ formatCurrency(employee.salary) }}</span>
            </div>
          </div>
          <p v-else class="text-gray-500 text-sm">No salary configured</p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="p-2 bg-green-100 rounded-lg">
              <Wallet :size="20" class="text-green-600" />
            </div>
            <h3 class="font-bold text-gray-900">Last Net Pay</h3>
          </div>
          <div v-if="latestPayrollItem">
            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(Number(latestPayrollItem.net_pay)) }}</p>
            <p class="text-sm text-gray-500 mt-1">
              {{ latestPayrollItem.period ? formatDate(latestPayrollItem.period.pay_date ?? latestPayrollItem.period.end_date) : '—' }}
            </p>
          </div>
          <p v-else class="text-gray-500 text-sm">No payroll records yet</p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="p-2 bg-amber-100 rounded-lg">
              <Receipt :size="20" class="text-amber-600" />
            </div>
            <h3 class="font-bold text-gray-900">Last Deductions</h3>
          </div>
          <div v-if="latestPayrollItem">
            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(Number(latestPayrollItem.total_deductions)) }}</p>
            <p class="text-sm text-gray-500 mt-1">from gross {{ formatCurrency(Number(latestPayrollItem.gross_pay)) }}</p>
          </div>
          <p v-else class="text-gray-500 text-sm">No payroll records yet</p>
        </div>
      </div>

      <!-- Recent Payslips -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-6">
          <div class="p-2 bg-purple-100 rounded-lg">
            <FileText :size="20" class="text-purple-600" />
          </div>
          <h3 class="font-bold text-gray-900">Recent Payslips</h3>
        </div>

        <div v-if="payroll_items.length === 0" class="text-center py-8">
          <FileText :size="40" class="text-gray-300 mx-auto mb-3" />
          <p class="text-gray-500">No payslip records found for this employee.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-600">Period</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-600">Days Worked</th>
                <th class="text-right py-3 px-4 text-sm font-medium text-gray-600">Gross Pay</th>
                <th class="text-right py-3 px-4 text-sm font-medium text-gray-600">Deductions</th>
                <th class="text-right py-3 px-4 text-sm font-medium text-gray-600">Net Pay</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-600">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in payroll_items" :key="item.id" class="border-b border-gray-200 hover:bg-gray-50">
                <td class="py-3 px-4 text-sm text-gray-900">
                  <span v-if="item.period">
                    {{ formatDate(item.period.start_date) }} – {{ formatDate(item.period.end_date) }}
                  </span>
                  <span v-else class="text-gray-400">—</span>
                </td>
                <td class="py-3 px-4 text-sm text-gray-600">{{ item.days_worked }}</td>
                <td class="py-3 px-4 text-sm text-gray-900 text-right">{{ formatCurrency(Number(item.gross_pay)) }}</td>
                <td class="py-3 px-4 text-sm text-red-600 text-right">{{ formatCurrency(Number(item.total_deductions)) }}</td>
                <td class="py-3 px-4 text-sm font-semibold text-green-700 text-right">{{ formatCurrency(Number(item.net_pay)) }}</td>
                <td class="py-3 px-4">
                  <span :class="[
                    'inline-flex px-2 py-1 rounded-full text-xs font-medium',
                    item.status === 'paid' ? 'bg-green-100 text-green-700' :
                    item.status === 'processed' ? 'bg-blue-100 text-blue-700' :
                    'bg-gray-100 text-gray-600'
                  ]">
                    {{ item.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Allowances Tab -->
    <div v-else-if="activeTab === 'allowances'" class="space-y-6">
      <div class="rounded-lg border border-gray-200 bg-white p-6">
        <div class="mb-6 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="rounded-lg bg-green-100 p-2">
              <Wallet :size="20" class="text-green-600" />
            </div>
            <h3 class="font-bold text-gray-900">Employee Allowances</h3>
          </div>
          <button
            @click="showAllowanceForm = true"
            class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
          >
            <Plus :size="16" />
            Add Allowance
          </button>
        </div>

        <!-- Add / Edit Form -->
        <div
          v-if="showAllowanceForm"
          class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4"
        >
          <h4 class="mb-4 text-sm font-semibold text-gray-900">
            {{ editingAllowance ? 'Edit Allowance' : 'New Allowance' }}
          </h4>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-700">Type</label>
              <select
                v-model="allowanceForm.allowance_type_id"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                @change="onAllowanceTypeChange"
              >
                <option :value="null" disabled>Select allowance type</option>
                <option v-for="t in allowanceTypes" :key="t.id" :value="t.id">
                  {{ t.name }}{{ t.is_taxable ? ' (Taxable)' : '' }}
                </option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-700">Name <span class="font-normal text-gray-400">(optional override)</span></label>
              <input
                v-model="allowanceForm.name"
                type="text"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="e.g., Rice Allowance"
              />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-700">Amount (₱)</label>
              <input
                v-model.number="allowanceForm.amount"
                type="number"
                min="0"
                step="0.01"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-700">Frequency</label>
              <select
                v-model="allowanceForm.frequency"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="monthly">Monthly (split per cutoff)</option>
                <option value="per_cutoff">Per Cutoff</option>
              </select>
            </div>
            <div class="flex items-center gap-4">
              <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                <input
                  v-model="allowanceForm.is_active"
                  type="checkbox"
                  class="rounded border-gray-300 text-blue-600"
                />
                Active
              </label>
            </div>
          </div>
          <p v-if="allowanceError" class="mt-2 text-xs text-red-600">{{ allowanceError }}</p>
          <div class="mt-4 flex gap-3">
            <button
              @click="saveAllowance"
              :disabled="allowanceSaving"
              class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
            >
              <Loader2 v-if="allowanceSaving" :size="14" class="animate-spin" />
              {{ editingAllowance ? 'Update' : 'Save' }}
            </button>
            <button
              @click="cancelAllowanceForm"
              class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
            >
              Cancel
            </button>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="allowancesLoading" class="py-8 text-center text-gray-500 text-sm">
          <Loader2 :size="24" class="mx-auto mb-2 animate-spin text-blue-500" />
          Loading allowances…
        </div>

        <!-- Empty -->
        <div v-else-if="allowances.length === 0 && !showAllowanceForm" class="py-8 text-center">
          <Wallet :size="36" class="mx-auto mb-3 text-gray-300" />
          <p class="text-gray-500">No allowances configured for this employee.</p>
        </div>

        <!-- List -->
        <div v-else-if="allowances.length > 0" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">Name</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">Type</th>
                <th class="py-3 px-4 text-right text-sm font-medium text-gray-600">Amount</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">Frequency</th>
                <th class="py-3 px-4 text-center text-sm font-medium text-gray-600">Taxable</th>
                <th class="py-3 px-4 text-center text-sm font-medium text-gray-600">Active</th>
                <th class="py-3 px-4 text-right text-sm font-medium text-gray-600">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="allowance in allowances"
                :key="allowance.id"
                class="border-b border-gray-100"
              >
                <td class="py-3 px-4 font-medium text-gray-900">{{ allowance.name }}</td>
                <td class="py-3 px-4 capitalize text-gray-600">{{ allowance.type }}</td>
                <td class="py-3 px-4 text-right text-gray-900">{{ formatCurrency(allowance.amount) }}</td>
                <td class="py-3 px-4 text-gray-600">
                  {{ allowance.frequency === 'per_cutoff' ? 'Per Cutoff' : 'Monthly' }}
                </td>
                <td class="py-3 px-4 text-center">
                  <span
                    :class="allowance.is_taxable ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'"
                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                  >
                    {{ allowance.is_taxable ? 'Taxable' : 'Non-Taxable' }}
                  </span>
                </td>
                <td class="py-3 px-4 text-center">
                  <span
                    :class="allowance.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                  >
                    {{ allowance.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="py-3 px-4 text-right">
                  <div class="flex justify-end gap-2">
                    <button
                      @click="startEditAllowance(allowance)"
                      class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-blue-600"
                    >
                      <Pencil :size="14" />
                    </button>
                    <button
                      @click="deleteAllowance(allowance.id)"
                      class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-red-600"
                    >
                      <Trash2 :size="14" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
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
          Are you sure you want to delete <strong>{{ employee.first_name }} {{ employee.last_name }}</strong>?
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
  </Layout>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import { Link, router } from '@inertiajs/vue3'
import {
  ArrowLeft,
  Pencil,
  Plus,
  Trash2,
  User,
  Briefcase,
  MapPin,
  PhilippinePeso,
  FileText,
  Clock,
  CalendarCheck,
  CalendarDays,
  Timer,
  Wallet,
  Receipt,
  AlertTriangle,
  Loader2,
} from 'lucide-vue-next'
import Layout from '@/components/Layout.vue'

interface AttendanceRecord {
  id: number
  date: string
  clock_in: string | null
  clock_out: string | null
  total_hours: number | null
  status: string
}

interface LeaveBalance {
  id: number
  leave_type?: { name: string }
  remaining_days: number
  total_days: number
}

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
  date_resigned: string | null
  employment_status: string
  employment_type: string | null
  salary: number | null
  bank_account: string | null
  tin: string | null
  sss_number: string | null
  philhealth_number: string | null
  pagibig_number: string | null
  is_active: boolean
  profile_photo_url: string | null
  department: { id: number; name: string } | null
  position: { id: number; position_name: string } | null
  company: { id: number; name: string } | null
}

interface PayrollPeriodSummary {
  id: number
  start_date: string
  end_date: string
  pay_date: string | null
  status: string
}

interface PayrollItem {
  id: number
  payroll_period_id: number
  gross_pay: string
  total_deductions: string
  net_pay: string
  status: string
  days_worked: string
  created_at: string
  period: PayrollPeriodSummary | null
}

interface Allowance {
  id: number
  allowance_type_id: number | null
  type: string
  name: string
  amount: number
  is_taxable: boolean
  frequency: string
  is_active: boolean
}

interface AllowanceTypeOption {
  id: number
  code: string
  name: string
  default_amount: number | null
  is_taxable: boolean
}

interface Props {
  employee: Employee
  payroll_items: PayrollItem[]
}

const props = defineProps<Props>()

const tabs = [
  { id: 'overview', label: 'Overview' },
  { id: 'timekeeping', label: 'Timekeeping' },
  { id: 'payroll', label: 'Payroll' },
  { id: 'allowances', label: 'Allowances' },
]

const activeTab = ref('overview')
const showDeleteModal = ref(false)
const isDeleting = ref(false)
const timekeepingLoading = ref(false)

// Timekeeping data
const timekeepingData = ref({
  attendance: {
    daysPresent: 0,
    daysLate: 0,
    daysAbsent: 0,
    totalHours: 0,
    recent: [] as AttendanceRecord[],
  },
  leave: {
    balances: [] as LeaveBalance[],
  },
  overtime: {
    thisMonth: 0,
    pending: 0,
    approved: 0,
  },
})

// Load timekeeping data when tab is switched
watch(activeTab, async (newTab) => {
  if (newTab === 'timekeeping') {
    await loadTimekeepingData()
  } else if (newTab === 'allowances') {
    await loadAllowances()
  }
})

const loadTimekeepingData = async () => {
  timekeepingLoading.value = true
  try {
    // Fetch employee-specific timekeeping data
    const [attendanceRes, leaveRes, overtimeRes] = await Promise.all([
      fetch(`/api/timekeeping/admin/employee/${props.employee.id}/attendance?per_page=10`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      }),
      fetch(`/api/timekeeping/admin/employee/${props.employee.id}/leave/balance`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      }),
      fetch(`/api/timekeeping/admin/employee/${props.employee.id}/overtime?per_page=30`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      }),
    ])

    if (attendanceRes.ok) {
      const data = await attendanceRes.json()
      const records = data.data || []
      timekeepingData.value.attendance.recent = records.slice(0, 5)
      timekeepingData.value.attendance.daysPresent = records.filter((r: AttendanceRecord) => r.status === 'present').length
      timekeepingData.value.attendance.daysLate = records.filter((r: AttendanceRecord) => r.status === 'late').length
      timekeepingData.value.attendance.daysAbsent = records.filter((r: AttendanceRecord) => r.status === 'absent').length
      timekeepingData.value.attendance.totalHours = records.reduce((sum: number, r: AttendanceRecord) => sum + (r.total_hours || 0), 0)
    }

    if (leaveRes.ok) {
      const data = await leaveRes.json()
      timekeepingData.value.leave.balances = data.data || []
    }

    if (overtimeRes.ok) {
      const data = await overtimeRes.json()
      const records = data.data || []
      const now = new Date()
      const thisMonth = records.filter((r: { date: string }) => {
        const date = new Date(r.date)
        return date.getMonth() === now.getMonth() && date.getFullYear() === now.getFullYear()
      })
      timekeepingData.value.overtime.thisMonth = thisMonth.reduce((sum: number, r: { hours: number }) => sum + r.hours, 0)
      timekeepingData.value.overtime.pending = records
        .filter((r: { status: string }) => r.status === 'pending')
        .reduce((sum: number, r: { hours: number }) => sum + r.hours, 0)
      timekeepingData.value.overtime.approved = records
        .filter((r: { status: string }) => r.status === 'approved')
        .reduce((sum: number, r: { hours: number }) => sum + r.hours, 0)
    }
  } catch (e) {
    console.error('Failed to load timekeeping data:', e)
  } finally {
    timekeepingLoading.value = false
  }
}

// Helper functions for timekeeping
const formatTime = (time: string | null): string => {
  if (!time) return '-'
  const date = new Date(time)
  return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

const formatAttendanceStatus = (status: string): string => {
  const statusMap: Record<string, string> = {
    present: 'Present',
    late: 'Late',
    absent: 'Absent',
    half_day: 'Half Day',
    on_leave: 'On Leave',
  }
  return statusMap[status] || status
}

const getAttendanceStatusClass = (status: string): string => {
  switch (status) {
    case 'present': return 'bg-green-100 text-green-700'
    case 'late': return 'bg-amber-100 text-amber-700'
    case 'absent': return 'bg-red-100 text-red-700'
    case 'half_day': return 'bg-blue-100 text-blue-700'
    case 'on_leave': return 'bg-purple-100 text-purple-700'
    default: return 'bg-gray-100 text-gray-700'
  }
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'active':
      return 'bg-green-100 text-green-700'
    case 'inactive':
      return 'bg-gray-100 text-gray-600'
    case 'resigned':
      return 'bg-yellow-100 text-yellow-700'
    case 'terminated':
      return 'bg-red-100 text-red-700'
    case 'retired':
      return 'bg-blue-100 text-blue-700'
    default:
      return 'bg-gray-100 text-gray-700'
  }
}

const formatStatus = (status: string) => {
  const statusMap: Record<string, string> = {
    active: 'Active',
    inactive: 'Inactive',
    resigned: 'Resigned',
    terminated: 'Terminated',
    retired: 'Retired',
  }
  return statusMap[status] || status
}

const formatEmploymentType = (type: string | null) => {
  if (!type) return '-'
  const typeMap: Record<string, string> = {
    full_time: 'Full Time',
    part_time: 'Part Time',
    contract: 'Contract',
    probationary: 'Probationary',
  }
  return typeMap[type] || type
}

const formatDate = (date: string | null) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

const formatCurrency = (amount: number | null) => {
  if (amount === null || amount === undefined) return '-'
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    currencyDisplay: 'narrowSymbol',
  }).format(amount)
}

const deleteEmployee = () => {
  isDeleting.value = true
  router.delete(`/employees/${props.employee.id}`, {
    onSuccess: () => {
      showDeleteModal.value = false
    },
    onFinish: () => {
      isDeleting.value = false
    },
  })
}

const latestPayrollItem = computed(() => props.payroll_items?.[0] ?? null)

// --- Allowances ---
const allowances = ref<Allowance[]>([])
const allowancesLoading = ref(false)
const allowanceTypes = ref<AllowanceTypeOption[]>([])
const showAllowanceForm = ref(false)
const allowanceSaving = ref(false)
const allowanceError = ref('')
const editingAllowance = ref<Allowance | null>(null)

const defaultAllowanceForm = () => ({
  allowance_type_id: null as number | null,
  name: '',
  amount: 0,
  frequency: 'monthly',
  is_active: true,
})

const allowanceForm = ref(defaultAllowanceForm())

const onAllowanceTypeChange = () => {
  const selected = allowanceTypes.value.find((t) => t.id === allowanceForm.value.allowance_type_id)
  if (selected) {
    if (!allowanceForm.value.name || editingAllowance.value === null) {
      allowanceForm.value.name = selected.name
    }
    if (!allowanceForm.value.amount) {
      allowanceForm.value.amount = selected.default_amount ?? 0
    }
  }
}

const loadAllowances = async () => {
  allowancesLoading.value = true
  try {
    const [allowancesRes, typesRes] = await Promise.all([
      axios.get<Allowance[]>(`/api/employees/${props.employee.id}/allowances`),
      axios.get<AllowanceTypeOption[]>('/api/hr-settings/allowance-types'),
    ])
    allowances.value = allowancesRes.data
    allowanceTypes.value = typesRes.data
  } catch {
    // silently fail
  } finally {
    allowancesLoading.value = false
  }
}

const startEditAllowance = (allowance: Allowance) => {
  editingAllowance.value = allowance
  allowanceForm.value = {
    allowance_type_id: allowance.allowance_type_id,
    name: allowance.name,
    amount: allowance.amount,
    frequency: allowance.frequency,
    is_active: allowance.is_active,
  }
  showAllowanceForm.value = true
  allowanceError.value = ''
}

const cancelAllowanceForm = () => {
  showAllowanceForm.value = false
  editingAllowance.value = null
  allowanceForm.value = defaultAllowanceForm()
  allowanceError.value = ''
}

const saveAllowance = async () => {
  allowanceSaving.value = true
  allowanceError.value = ''
  try {
    if (editingAllowance.value) {
      const { data } = await axios.put<Allowance>(
        `/api/employees/${props.employee.id}/allowances/${editingAllowance.value.id}`,
        allowanceForm.value,
      )
      const idx = allowances.value.findIndex((a) => a.id === editingAllowance.value!.id)
      if (idx !== -1) allowances.value[idx] = data
    } else {
      const { data } = await axios.post<Allowance>(
        `/api/employees/${props.employee.id}/allowances`,
        allowanceForm.value,
      )
      allowances.value.push(data)
    }
    cancelAllowanceForm()
  } catch (err: unknown) {
    if (axios.isAxiosError(err) && err.response?.data?.message) {
      allowanceError.value = err.response.data.message
    } else {
      allowanceError.value = 'Failed to save allowance.'
    }
  } finally {
    allowanceSaving.value = false
  }
}

const deleteAllowance = async (id: number) => {
  if (!confirm('Delete this allowance?')) return
  try {
    await axios.delete(`/api/employees/${props.employee.id}/allowances/${id}`)
    allowances.value = allowances.value.filter((a) => a.id !== id)
  } catch {
    // silently fail
  }
}
</script>
