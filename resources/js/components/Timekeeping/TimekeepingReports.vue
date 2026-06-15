<template>
  <div class="space-y-6">
    <!-- Report Filters -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Generate Reports</h3>

      <div :class="['grid grid-cols-1 gap-4 mb-4', isAdmin ? 'md:grid-cols-4' : 'md:grid-cols-3']">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
          <select v-model="reportFilters.type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="attendance">Attendance Report</option>
            <option value="overtime">Overtime Report</option>
            <option value="leave">Leave Report</option>
            <option value="summary">Summary Report</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
          <DatePicker v-model="reportFilters.startDate" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
          <DatePicker v-model="reportFilters.endDate" />
        </div>

        <!-- Employee search — admin only -->
        <div v-if="isAdmin" class="relative" ref="autocompleteWrap">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Employee <span class="font-normal text-gray-400">(optional)</span>
          </label>
          <div class="relative">
            <input
              v-model="employeeQuery"
              type="text"
              placeholder="Name or ID…"
              autocomplete="off"
              class="w-full px-4 py-2 pr-8 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              @input="onEmployeeInput"
              @focus="showSuggestions = suggestions.length > 0"
              @keydown.escape="showSuggestions = false"
              @keydown.down.prevent="highlightNext"
              @keydown.up.prevent="highlightPrev"
              @keydown.enter.prevent="selectHighlighted"
            />
            <button v-if="selectedEmployee" @click="clearEmployee" type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
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
        </div>
      </div>

      <div class="flex gap-2">
        <button
          @click="generateReport"
          :disabled="isLoading"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center gap-2"
        >
          <Loader2 v-if="isLoading" class="animate-spin" :size="16" />
          Generate Report
        </button>
        <button @click="downloadReport" class="px-6 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors flex items-center gap-2">
          <Download :size="18" />
          Download
        </button>
      </div>
    </div>

    <!-- Empty prompt for admin before first generate -->
    <div v-if="!isLoading && activeReport === null" class="bg-white rounded-lg border border-gray-200 p-12 text-center text-gray-500">
      <p class="text-base font-medium text-gray-700 mb-1">No report generated yet</p>
      <p class="text-sm">Select your filters above and click <span class="font-medium text-gray-800">Generate Report</span> to view results.</p>
    </div>

    <!-- Loading State -->
    <div v-else-if="isLoading" class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="space-y-4">
        <div v-for="i in 5" :key="i" class="h-12 bg-gray-100 rounded animate-pulse" />
      </div>
    </div>

    <!-- Attendance Report -->
    <div v-else-if="activeReport === 'attendance'" class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Attendance Report</h3>

      <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-blue-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Days Present</p>
          <p class="text-2xl font-bold text-blue-600">{{ attendanceSummary.daysPresent }}</p>
        </div>
        <div class="bg-amber-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Days Late</p>
          <p class="text-2xl font-bold text-amber-600">{{ attendanceSummary.daysLate }}</p>
        </div>
        <div class="bg-red-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Days Absent</p>
          <p class="text-2xl font-bold text-red-600">{{ attendanceSummary.daysAbsent }}</p>
        </div>
        <div class="bg-green-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Total Hours</p>
          <p class="text-2xl font-bold text-green-600">{{ Number(attendanceSummary.totalHours || 0).toFixed(1) }}h</p>
        </div>
        <div class="bg-purple-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Attendance %</p>
          <p class="text-2xl font-bold text-purple-600">{{ Number(attendanceSummary.attendanceRate || 0).toFixed(1) }}%</p>
        </div>
      </div>

      <!-- Search -->
      <div v-if="attendanceData.length > 0" class="mb-3 flex justify-end">
        <input v-model="attendanceSearch" type="text" placeholder="Search by date or status..." class="w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>

      <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200 bg-gray-50">
              <th v-if="showEmployeeColumn" class="text-left py-3 px-4 font-medium text-gray-900">Employee</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Date</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Clock In</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Clock Out</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Hours</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="attendanceData.length === 0">
              <td :colspan="showEmployeeColumn ? 6 : 5" class="py-8 text-center text-gray-500">No attendance records found</td>
            </tr>
            <tr v-else-if="filteredAttendance.length === 0">
              <td :colspan="showEmployeeColumn ? 6 : 5" class="py-8 text-center text-gray-500">No records match your search.</td>
            </tr>
            <tr v-for="(record, idx) in paginatedAttendance" :key="idx" class="border-b border-gray-200 hover:bg-gray-50">
              <td v-if="showEmployeeColumn" class="py-3 px-4 text-gray-900 font-medium">{{ record.employee_name }}</td>
              <td class="py-3 px-4 text-gray-900">{{ formatDate(record.date) }}</td>
              <td class="py-3 px-4 text-gray-600">{{ formatTime(record.clock_in) }}</td>
              <td class="py-3 px-4 text-gray-600">{{ formatTime(record.clock_out) }}</td>
              <td class="py-3 px-4 text-gray-900">{{ Number(record.total_hours || 0).toFixed(1) }}h</td>
              <td class="py-3 px-4">
                <span :class="['inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium', getStatusClass(record.status)]">
                  {{ formatStatus(record.status) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
        <!-- Attendance Pagination -->
        <div v-if="attendanceData.length > 0" class="flex items-center border-t border-gray-200 bg-white px-6 py-4">
          <div class="flex w-1/3 items-center gap-2">
            <span class="text-sm text-gray-600">Per page:</span>
            <select v-model="attendancePerPage" @change="changeAttendancePerPage" class="rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option v-for="n in [10, 25, 50]" :key="n" :value="n">{{ n }}</option>
            </select>
          </div>
          <div class="flex w-1/3 justify-center gap-2">
            <button @click="attendancePage--" :disabled="attendancePage <= 1" class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400" v-html="'&laquo;'" />
            <template v-for="(p, i) in attendancePageWindow" :key="i">
              <span v-if="p === '...'" class="px-2 py-1 text-sm text-gray-400">…</span>
              <button v-else @click="attendancePage = p" :class="['rounded-lg px-3 py-1 text-sm font-medium transition-colors', attendancePage === p ? 'bg-blue-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50']">{{ p }}</button>
            </template>
            <button @click="attendancePage++" :disabled="attendancePage >= attendanceTotalPages" class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400" v-html="'&raquo;'" />
          </div>
          <div class="flex w-1/3 justify-end">
            <p class="text-sm text-gray-600">{{ attendanceSearch ? `${filteredAttendance.length} of ${attendanceData.length}` : attendanceData.length }} records</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Overtime Report -->
    <div v-else-if="activeReport === 'overtime'" class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Overtime Report</h3>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Total Overtime</p>
          <p class="text-2xl font-bold text-blue-600">{{ overtimeSummary.totalHours }}h</p>
        </div>
        <div class="bg-green-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Approved</p>
          <p class="text-2xl font-bold text-green-600">{{ overtimeSummary.approvedHours }}h</p>
        </div>
        <div class="bg-amber-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Pending</p>
          <p class="text-2xl font-bold text-amber-600">{{ overtimeSummary.pendingHours }}h</p>
        </div>
        <div class="bg-purple-50 rounded-lg p-4">
          <p class="text-gray-600 text-sm mb-2">Est. Compensation</p>
          <p class="text-2xl font-bold text-purple-600">${{ overtimeSummary.estimatedCompensation }}</p>
        </div>
      </div>

      <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200 bg-gray-50">
              <th v-if="showOvertimeEmployeeColumn" class="text-left py-3 px-4 font-medium text-gray-900">Employee</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Date</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Time In</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Time Out</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Hours</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Type</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Reason</th>
              <th class="text-left py-3 px-4 font-medium text-gray-900">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="overtimeData.length === 0">
              <td :colspan="showOvertimeEmployeeColumn ? 8 : 7" class="py-8 text-center text-gray-500">No overtime records found</td>
            </tr>
            <tr v-for="(record, idx) in paginatedOvertime" :key="idx" class="border-b border-gray-200 hover:bg-gray-50">
              <td v-if="showOvertimeEmployeeColumn" class="py-3 px-4 text-gray-900 font-medium">{{ record.employee_name }}</td>
              <td class="py-3 px-4 text-gray-900">{{ formatDate(record.date) }}</td>
              <td class="py-3 px-4 text-gray-600">{{ formatDateTime(record.start_at) }}</td>
              <td class="py-3 px-4 text-gray-600">{{ formatDateTime(record.end_at) }}</td>
              <td class="py-3 px-4 text-gray-900 font-medium">{{ record.hours }}h</td>
              <td class="py-3 px-4 text-gray-600 capitalize">{{ record.overtime_type }}</td>
              <td class="py-3 px-4 text-gray-600">{{ record.reason || '-' }}</td>
              <td class="py-3 px-4">
                <span :class="['inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium', getStatusClass(record.status)]">
                  {{ formatStatus(record.status) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
        <!-- Overtime Pagination -->
        <div v-if="overtimeData.length > 0" class="flex items-center border-t border-gray-200 bg-white px-6 py-4">
          <div class="flex w-1/3 items-center gap-2">
            <span class="text-sm text-gray-600">Per page:</span>
            <select v-model="overtimePerPage" @change="changeOvertimePerPage" class="rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option v-for="n in [10, 25, 50]" :key="n" :value="n">{{ n }}</option>
            </select>
          </div>
          <div class="flex w-1/3 justify-center gap-2">
            <button @click="overtimePage--" :disabled="overtimePage <= 1" class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400" v-html="'&laquo;'" />
            <template v-for="(p, i) in overtimePageWindow" :key="i">
              <span v-if="p === '...'" class="px-2 py-1 text-sm text-gray-400">…</span>
              <button v-else @click="overtimePage = p" :class="['rounded-lg px-3 py-1 text-sm font-medium transition-colors', overtimePage === p ? 'bg-blue-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50']">{{ p }}</button>
            </template>
            <button @click="overtimePage++" :disabled="overtimePage >= overtimeTotalPages" class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400" v-html="'&raquo;'" />
          </div>
          <div class="flex w-1/3 justify-end">
            <p class="text-sm text-gray-600">{{ overtimeData.length }} total records</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Leave Report -->
    <div v-else-if="activeReport === 'leave'" class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Leave Report</h3>

      <!-- Admin: leave requests across employees (any status, within date range) -->
      <template v-if="isAdmin">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-blue-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm mb-2">Total Requests</p>
            <p class="text-2xl font-bold text-blue-600">{{ leaveRequestSummary.totalRequests }}</p>
          </div>
          <div class="bg-green-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm mb-2">Approved Days</p>
            <p class="text-2xl font-bold text-green-600">{{ leaveRequestSummary.approvedDays }}</p>
            <p class="text-xs text-gray-500 mt-1">days taken</p>
          </div>
          <div class="bg-amber-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm mb-2">Pending</p>
            <p class="text-2xl font-bold text-amber-600">{{ leaveRequestSummary.pending }}</p>
            <p class="text-xs text-gray-500 mt-1">requests</p>
          </div>
          <div class="bg-purple-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm mb-2">Approved</p>
            <p class="text-2xl font-bold text-purple-600">{{ leaveRequestSummary.approved }}</p>
            <p class="text-xs text-gray-500 mt-1">requests</p>
          </div>
        </div>

        <!-- Search -->
        <div v-if="leaveRequestData.length > 0" class="mb-3 flex justify-end">
          <input v-model="leaveRequestSearch" type="text" placeholder="Search by employee, type or status..." class="w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-200 bg-gray-50">
                <th v-if="showLeaveEmployeeColumn" class="text-left py-3 px-4 font-medium text-gray-900">Employee</th>
                <th class="text-left py-3 px-4 font-medium text-gray-900">Type</th>
                <th class="text-left py-3 px-4 font-medium text-gray-900">Start</th>
                <th class="text-left py-3 px-4 font-medium text-gray-900">End</th>
                <th class="text-left py-3 px-4 font-medium text-gray-900">Days</th>
                <th class="text-left py-3 px-4 font-medium text-gray-900">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="leaveRequestData.length === 0">
                <td :colspan="showLeaveEmployeeColumn ? 6 : 5" class="py-8 text-center text-gray-500">No leave requests found in this date range</td>
              </tr>
              <tr v-else-if="filteredLeaveRequests.length === 0">
                <td :colspan="showLeaveEmployeeColumn ? 6 : 5" class="py-8 text-center text-gray-500">No records match your search.</td>
              </tr>
              <tr v-for="(req, idx) in paginatedLeaveRequests" :key="idx" class="border-b border-gray-200 hover:bg-gray-50">
                <td v-if="showLeaveEmployeeColumn" class="py-3 px-4 text-gray-900 font-medium">{{ req.employee_name }}</td>
                <td class="py-3 px-4 text-gray-900">{{ req.leave_type }}</td>
                <td class="py-3 px-4 text-gray-600">{{ formatDate(req.start_date) }}</td>
                <td class="py-3 px-4 text-gray-600">{{ formatDate(req.end_date) }}</td>
                <td class="py-3 px-4 text-gray-900">{{ req.total_days }}</td>
                <td class="py-3 px-4">
                  <span :class="['inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium', getStatusClass(req.status)]">
                    {{ formatStatus(req.status) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
          <!-- Leave Request Pagination -->
          <div v-if="leaveRequestData.length > 0" class="flex items-center border-t border-gray-200 bg-white px-6 py-4">
            <div class="flex w-1/3 items-center gap-2">
              <span class="text-sm text-gray-600">Per page:</span>
              <select v-model="leaveRequestPerPage" @change="changeLeaveRequestPerPage" class="rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option v-for="n in [10, 25, 50]" :key="n" :value="n">{{ n }}</option>
              </select>
            </div>
            <div class="flex w-1/3 justify-center gap-2">
              <button @click="leaveRequestPage--" :disabled="leaveRequestPage <= 1" class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400" v-html="'&laquo;'" />
              <template v-for="(p, i) in leaveRequestPageWindow" :key="i">
                <span v-if="p === '...'" class="px-2 py-1 text-sm text-gray-400">…</span>
                <button v-else @click="leaveRequestPage = p" :class="['rounded-lg px-3 py-1 text-sm font-medium transition-colors', leaveRequestPage === p ? 'bg-blue-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50']">{{ p }}</button>
              </template>
              <button @click="leaveRequestPage++" :disabled="leaveRequestPage >= leaveRequestTotalPages" class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400" v-html="'&raquo;'" />
            </div>
            <div class="flex w-1/3 justify-end">
              <p class="text-sm text-gray-600">{{ leaveRequestSearch ? `${filteredLeaveRequests.length} of ${leaveRequestData.length}` : leaveRequestData.length }} requests</p>
            </div>
          </div>
        </div>
      </template>

      <!-- Employee: own leave balance -->
      <template v-else>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-blue-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm mb-2">Total Allocated</p>
            <p class="text-2xl font-bold text-blue-600">{{ leaveSummary.totalAllocated }}</p>
            <p class="text-xs text-gray-500 mt-1">days</p>
          </div>
          <div class="bg-orange-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm mb-2">Used</p>
            <p class="text-2xl font-bold text-orange-600">{{ leaveSummary.totalUsed }}</p>
            <p class="text-xs text-gray-500 mt-1">days</p>
          </div>
          <div class="bg-green-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm mb-2">Remaining</p>
            <p class="text-2xl font-bold text-green-600">{{ leaveSummary.totalRemaining }}</p>
            <p class="text-xs text-gray-500 mt-1">days</p>
          </div>
          <div class="bg-purple-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm mb-2">Approval Rate</p>
            <p class="text-2xl font-bold text-purple-600">{{ leaveSummary.approvalRate }}%</p>
            <p class="text-xs text-gray-500 mt-1">approved</p>
          </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-200 bg-gray-50">
                <th class="text-left py-3 px-4 font-medium text-gray-900">Type</th>
                <th class="text-left py-3 px-4 font-medium text-gray-900">Allocated</th>
                <th class="text-left py-3 px-4 font-medium text-gray-900">Used</th>
                <th class="text-left py-3 px-4 font-medium text-gray-900">Remaining</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="leaveData.length === 0">
                <td colspan="4" class="py-8 text-center text-gray-500">No leave balance data found</td>
              </tr>
              <tr v-for="leave in paginatedLeave" :key="leave.id" class="border-b border-gray-200 hover:bg-gray-50">
                <td class="py-3 px-4 text-gray-900 font-medium">{{ leave.leave_type?.name || 'Leave' }}</td>
                <td class="py-3 px-4 text-gray-600">{{ leave.total_days }}</td>
                <td class="py-3 px-4 text-gray-600">{{ leave.used_days }}</td>
                <td class="py-3 px-4 text-gray-900">{{ leave.remaining_days }}</td>
              </tr>
            </tbody>
          </table>
          <!-- Leave Pagination -->
          <div v-if="leaveData.length > 0" class="flex items-center border-t border-gray-200 bg-white px-6 py-4">
            <div class="flex w-1/3 items-center gap-2">
              <span class="text-sm text-gray-600">Per page:</span>
              <select v-model="leavePerPage" @change="changeLeavePerPage" class="rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option v-for="n in [10, 25, 50]" :key="n" :value="n">{{ n }}</option>
              </select>
            </div>
            <div class="flex w-1/3 justify-center gap-2">
              <button @click="leavePage--" :disabled="leavePage <= 1" class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400" v-html="'&laquo;'" />
              <template v-for="(p, i) in leavePageWindow" :key="i">
                <span v-if="p === '...'" class="px-2 py-1 text-sm text-gray-400">…</span>
                <button v-else @click="leavePage = p" :class="['rounded-lg px-3 py-1 text-sm font-medium transition-colors', leavePage === p ? 'bg-blue-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50']">{{ p }}</button>
              </template>
              <button @click="leavePage++" :disabled="leavePage >= leaveTotalPages" class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400" v-html="'&raquo;'" />
            </div>
            <div class="flex w-1/3 justify-end">
              <p class="text-sm text-gray-600">{{ leaveData.length }} total records</p>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Summary Report -->
    <div v-else-if="activeReport === 'summary' && summaryReport" class="space-y-4">
      <!-- Attendance -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
          Attendance Summary
          <span v-if="summaryReport.attendance.total_employees" class="ml-2 text-sm font-normal text-gray-500">({{ summaryReport.attendance.total_employees }} employees)</span>
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
          <div class="bg-blue-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm mb-2">Days Present</p>
            <p class="text-2xl font-bold text-blue-600">{{ summaryReport.attendance.present }}</p>
          </div>
          <div class="bg-amber-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm mb-2">Days Late</p>
            <p class="text-2xl font-bold text-amber-600">{{ summaryReport.attendance.late }}</p>
          </div>
          <div class="bg-red-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm mb-2">Days Absent</p>
            <p class="text-2xl font-bold text-red-600">{{ summaryReport.attendance.absent }}</p>
          </div>
          <div class="bg-green-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm mb-2">Total Hours</p>
            <p class="text-2xl font-bold text-green-600">{{ Number(summaryReport.attendance.total_hours).toFixed(1) }}h</p>
          </div>
          <div class="bg-purple-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm mb-2">Attendance Rate</p>
            <p class="text-2xl font-bold text-purple-600">{{ Number(summaryReport.attendance.attendance_rate).toFixed(1) }}%</p>
          </div>
        </div>
      </div>

      <!-- Leave -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Leave Summary</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-blue-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm mb-2">Total Requests</p>
            <p class="text-2xl font-bold text-blue-600">{{ summaryReport.leave.total_requests }}</p>
          </div>
          <div class="bg-green-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm mb-2">Approved Days</p>
            <p class="text-2xl font-bold text-green-600">{{ summaryReport.leave.approved_days }}</p>
            <p class="text-xs text-gray-500 mt-1">days taken</p>
          </div>
          <div class="bg-amber-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm mb-2">Pending</p>
            <p class="text-2xl font-bold text-amber-600">{{ summaryReport.leave.pending_requests }}</p>
            <p class="text-xs text-gray-500 mt-1">requests</p>
          </div>
          <div class="bg-purple-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm mb-2">Approved</p>
            <p class="text-2xl font-bold text-purple-600">{{ summaryReport.leave.approved_requests }}</p>
            <p class="text-xs text-gray-500 mt-1">requests</p>
          </div>
        </div>
      </div>

      <!-- Overtime -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Overtime Summary</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-blue-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm mb-2">Total Hours</p>
            <p class="text-2xl font-bold text-blue-600">{{ Number(summaryReport.overtime.total_hours).toFixed(1) }}h</p>
          </div>
          <div class="bg-green-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm mb-2">Weekday OT</p>
            <p class="text-2xl font-bold text-green-600">{{ Number(summaryReport.overtime.by_type?.weekday ?? 0).toFixed(1) }}h</p>
          </div>
          <div class="bg-amber-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm mb-2">Weekend OT</p>
            <p class="text-2xl font-bold text-amber-600">{{ Number(summaryReport.overtime.by_type?.weekend ?? 0).toFixed(1) }}h</p>
          </div>
          <div class="bg-red-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm mb-2">Holiday OT</p>
            <p class="text-2xl font-bold text-red-600">{{ Number(summaryReport.overtime.by_type?.holiday ?? 0).toFixed(1) }}h</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Daily Time Record (DTR) -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">Daily Time Record (DTR)</h3>
          <p class="text-sm text-gray-500 mt-1">Civil Service Form No. 48 — print or download per employee per month</p>
        </div>
        <Link
          href="/timekeeping/dtr"
          class="flex items-center gap-2 px-5 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors"
        >
          <FileText :size="18" />
          Open DTR
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import { Download, FileText, Loader2, Search, X } from 'lucide-vue-next'
import { useTimekeeping, type LeaveBalance } from '@/composables/useTimekeeping'
import DatePicker from '@/components/ui/DatePicker.vue'

interface AttendanceRow {
  date: string
  clock_in: string | null
  clock_out: string | null
  total_hours: number | null
  status: string
  employee_name?: string
}

interface OvertimeRow {
  date: string
  start_at: string | null
  end_at: string | null
  hours: number
  overtime_type: string
  reason: string | null
  status: string
  employee_name?: string
}

interface LeaveRequestRow {
  employee_name?: string
  leave_type: string
  start_date: string
  end_date: string
  total_days: number
  status: string
  reason: string | null
}

interface SummaryReport {
  attendance: {
    total_employees?: number
    present: number
    late: number
    absent: number
    total_hours: number
    attendance_rate: number
  }
  leave: {
    total_requests: number
    approved_days: number
    pending_requests: number
    approved_requests: number
  }
  overtime: {
    total_hours: number
    approved_hours?: number
    pending_hours?: number
    by_type?: { weekday: number; weekend: number; holiday: number }
  }
}

const page = usePage()
const isAdmin = (page.props.auth as any)?.isAdmin === true

// Employee autocomplete (admin only)
interface EmployeeSuggestion { id: number; employee_id: string; first_name: string; last_name: string }
const employeeQuery = ref('')
const selectedEmployee = ref<EmployeeSuggestion | null>(null)
const suggestions = ref<EmployeeSuggestion[]>([])
const showSuggestions = ref(false)
const suggestionsLoading = ref(false)
const highlightedIndex = ref(-1)
const autocompleteWrap = ref<HTMLElement | null>(null)
let debounceTimer: ReturnType<typeof setTimeout>

const onEmployeeInput = () => {
  selectedEmployee.value = null
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
  selectedEmployee.value = emp
  employeeQuery.value = `${emp.last_name}, ${emp.first_name}`
  showSuggestions.value = false
  suggestions.value = []
}

const clearEmployee = () => {
  selectedEmployee.value = null
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

const {
  fetchAttendanceHistory,
  fetchAttendanceSummary,
  fetchOvertimeHistory,
  fetchLeaveBalance,
  fetchMySummary,
} = useTimekeeping()

const activeReport = ref<string | null>(isAdmin ? null : 'attendance')
const isLoading = ref(false)

const reportFilters = ref({
  type: 'attendance',
  startDate: getDefaultStartDate(),
  endDate: getDefaultEndDate()
})

const showEmployeeColumn = computed(() => isAdmin)

// Report data
const attendanceData = ref<AttendanceRow[]>([])
const summaryReport = ref<SummaryReport | null>(null)
const attendanceSummary = ref({
  daysPresent: 0,
  daysLate: 0,
  daysAbsent: 0,
  totalHours: 0,
  attendanceRate: 0
})

const overtimeData = ref<OvertimeRow[]>([])
const overtimeSummary = ref({
  totalHours: 0,
  approvedHours: 0,
  pendingHours: 0,
  estimatedCompensation: 0
})
const showOvertimeEmployeeColumn = computed(() => isAdmin)

const leaveData = ref<LeaveBalance[]>([])
const leaveSummary = ref({
  totalAllocated: 0,
  totalUsed: 0,
  totalRemaining: 0,
  approvalRate: 100
})

// Admin leave request list (all employees, any status, within date range)
const leaveRequestData = ref<LeaveRequestRow[]>([])
const leaveRequestSummary = ref({
  totalRequests: 0,
  approvedDays: 0,
  pending: 0,
  approved: 0,
})
const showLeaveEmployeeColumn = computed(() => isAdmin)

// Pagination — Attendance
const attendanceSearch = ref('')
const attendancePage = ref(1)
const attendancePerPage = ref(10)
const filteredAttendance = computed(() => {
  const q = attendanceSearch.value.toLowerCase()
  if (!q) { return attendanceData.value }
  return attendanceData.value.filter(r =>
    formatDate(r.date).toLowerCase().includes(q) ||
    r.status.toLowerCase().includes(q) ||
    (r.employee_name?.toLowerCase().includes(q) ?? false),
  )
})
const attendanceTotalPages = computed(() => Math.max(1, Math.ceil(filteredAttendance.value.length / attendancePerPage.value)))
const paginatedAttendance = computed(() => {
  const start = (attendancePage.value - 1) * attendancePerPage.value
  return filteredAttendance.value.slice(start, start + attendancePerPage.value)
})
function changeAttendancePerPage(): void { attendancePage.value = 1 }
watch(attendanceData, () => { attendancePage.value = 1; attendanceSearch.value = '' })
watch(attendanceSearch, () => { attendancePage.value = 1 })

// Pagination — Overtime
const overtimePage = ref(1)
const overtimePerPage = ref(10)
const overtimeTotalPages = computed(() => Math.max(1, Math.ceil(overtimeData.value.length / overtimePerPage.value)))
const paginatedOvertime = computed(() => {
  const start = (overtimePage.value - 1) * overtimePerPage.value
  return overtimeData.value.slice(start, start + overtimePerPage.value)
})
function changeOvertimePerPage(): void { overtimePage.value = 1 }
watch(overtimeData, () => { overtimePage.value = 1 })

// Pagination — Leave
const leavePage = ref(1)
const leavePerPage = ref(10)
const leaveTotalPages = computed(() => Math.max(1, Math.ceil(leaveData.value.length / leavePerPage.value)))
const paginatedLeave = computed(() => {
  const start = (leavePage.value - 1) * leavePerPage.value
  return leaveData.value.slice(start, start + leavePerPage.value)
})
function changeLeavePerPage(): void { leavePage.value = 1 }
watch(leaveData, () => { leavePage.value = 1 })

// Pagination — Leave Requests (admin list)
const leaveRequestSearch = ref('')
const leaveRequestPage = ref(1)
const leaveRequestPerPage = ref(10)
const filteredLeaveRequests = computed(() => {
  const q = leaveRequestSearch.value.toLowerCase()
  if (!q) { return leaveRequestData.value }
  return leaveRequestData.value.filter(r =>
    (r.employee_name?.toLowerCase().includes(q) ?? false) ||
    r.leave_type.toLowerCase().includes(q) ||
    r.status.toLowerCase().includes(q),
  )
})
const leaveRequestTotalPages = computed(() => Math.max(1, Math.ceil(filteredLeaveRequests.value.length / leaveRequestPerPage.value)))
const paginatedLeaveRequests = computed(() => {
  const start = (leaveRequestPage.value - 1) * leaveRequestPerPage.value
  return filteredLeaveRequests.value.slice(start, start + leaveRequestPerPage.value)
})
function changeLeaveRequestPerPage(): void { leaveRequestPage.value = 1 }
watch(leaveRequestData, () => { leaveRequestPage.value = 1; leaveRequestSearch.value = '' })
watch(leaveRequestSearch, () => { leaveRequestPage.value = 1 })

// Max numbered page buttons shown at once. Configurable via VITE_TABLE_MAX_PAGE_BUTTONS in .env (default 7).
const MAX_PAGE_BUTTONS = Math.max(5, Number(import.meta.env.VITE_TABLE_MAX_PAGE_BUTTONS) || 7)

// Windowed pagination — always shows the first & last page, with a sliding block of pages
// centred on the current page and `…` filling the gaps. Keeps a consistent button count.
function buildPageWindow(current: number, total: number): (number | '...')[] {
  if (total <= MAX_PAGE_BUTTONS) {
    return Array.from({ length: total }, (_, i) => i + 1)
  }

  const middleCount = MAX_PAGE_BUTTONS - 2
  let start = current - Math.floor(middleCount / 2)
  let end = current + Math.floor(middleCount / 2)

  if (start < 2) {
    start = 2
    end = start + middleCount - 1
  }
  if (end > total - 1) {
    end = total - 1
    start = end - middleCount + 1
  }

  const pages: (number | '...')[] = [1]
  if (start > 2) { pages.push('...') }
  for (let i = start; i <= end; i++) { pages.push(i) }
  if (end < total - 1) { pages.push('...') }
  pages.push(total)
  return pages
}

const attendancePageWindow = computed(() => buildPageWindow(attendancePage.value, attendanceTotalPages.value))
const overtimePageWindow = computed(() => buildPageWindow(overtimePage.value, overtimeTotalPages.value))
const leavePageWindow = computed(() => buildPageWindow(leavePage.value, leaveTotalPages.value))
const leaveRequestPageWindow = computed(() => buildPageWindow(leaveRequestPage.value, leaveRequestTotalPages.value))

// Helper functions
function getDefaultStartDate(): string {
  const date = new Date()
  date.setMonth(date.getMonth() - 1)
  return date.toISOString().split('T')[0]
}

function getDefaultEndDate(): string {
  return new Date().toISOString().split('T')[0]
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const formatTime = (time: string | null): string => {
  if (!time) return '-'
  // H:i:s or H:i format returned by the report API
  if (/^\d{2}:\d{2}(:\d{2})?$/.test(time)) {
    const [h, m] = time.split(':').map(Number)
    const ampm = h >= 12 ? 'PM' : 'AM'
    const h12 = h % 12 || 12
    return `${String(h12).padStart(2, '0')}:${String(m).padStart(2, '0')} ${ampm}`
  }
  return new Date(time).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

const formatDateTime = (value: string | null): string => {
  if (!value) { return '-' }
  return new Date(value).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const formatStatus = (status: string): string => {
  const statusMap: Record<string, string> = {
    present: 'Present',
    late: 'Late',
    absent: 'Absent',
    approved: 'Approved',
    pending: 'Pending',
    rejected: 'Rejected',
  }
  return statusMap[status] || status
}

const getStatusClass = (status: string): string => {
  switch (status) {
    case 'present':
    case 'approved': return 'bg-green-100 text-green-700'
    case 'late':
    case 'pending': return 'bg-amber-100 text-amber-700'
    case 'absent':
    case 'rejected': return 'bg-red-100 text-red-700'
    default: return 'bg-gray-100 text-gray-700'
  }
}

const generateReport = async () => {
  isLoading.value = true
  activeReport.value = reportFilters.value.type

  try {
    const { startDate, endDate } = reportFilters.value

    if (reportFilters.value.type === 'attendance') {
      if (isAdmin) {
        const params = new URLSearchParams({ start_date: startDate, end_date: endDate })
        if (selectedEmployee.value) { params.set('employee_id', String(selectedEmployee.value.id)) }
        const res = await fetch(`/api/timekeeping/reports/attendance?${params}`, {
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin',
        })
        const report = await res.json()
        const empGroups: any[] = report.data || []
        attendanceData.value = empGroups.flatMap(g =>
          (g.records || []).map((r: any) => ({ ...r, employee_name: g.employee?.name })),
        )
        attendanceSummary.value = {
          daysPresent: empGroups.reduce((s, g) => s + (g.summary?.present_days ?? 0), 0),
          daysLate: empGroups.reduce((s, g) => s + (g.summary?.late_days ?? 0), 0),
          daysAbsent: empGroups.reduce((s, g) => s + (g.summary?.absent_days ?? 0), 0),
          totalHours: empGroups.reduce((s, g) => s + (g.summary?.total_hours ?? 0), 0),
          attendanceRate: report.summary?.overall_attendance_rate ?? 0,
        }
      } else {
        const [historyResponse, summaryResponse] = await Promise.all([
          fetchAttendanceHistory({ start_date: startDate, end_date: endDate, per_page: 50 }),
          fetchAttendanceSummary(startDate, endDate, {}),
        ])
        attendanceData.value = historyResponse.data || []
        if (summaryResponse.data) {
          attendanceSummary.value = {
            daysPresent: summaryResponse.data.present_days || 0,
            daysLate: summaryResponse.data.late_days || 0,
            daysAbsent: summaryResponse.data.absent_days || 0,
            totalHours: summaryResponse.data.total_hours || 0,
            attendanceRate: summaryResponse.data.attendance_rate || 0,
          }
        }
      }
    } else if (reportFilters.value.type === 'overtime') {
      if (isAdmin) {
        const params = new URLSearchParams({ start_date: startDate, end_date: endDate })
        if (selectedEmployee.value) { params.set('employee_id', String(selectedEmployee.value.id)) }
        const res = await fetch(`/api/timekeeping/reports/overtime?${params}`, {
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin',
        })
        const report = await res.json()
        const empGroups: any[] = report.data || []
        overtimeData.value = empGroups.flatMap(g =>
          (g.records || []).map((r: any) => ({ ...r, employee_name: g.employee?.name })),
        )
      } else {
        const response = await fetchOvertimeHistory({ start_date: startDate, end_date: endDate, per_page: 50 })
        overtimeData.value = response.data || []
      }

      // Calculate summary from data
      const approved = overtimeData.value.filter(r => r.status === 'approved' || r.status === 'paid')
      const pending = overtimeData.value.filter(r => r.status === 'pending')
      overtimeSummary.value = {
        totalHours: overtimeData.value.reduce((sum, r) => sum + Number(r.hours), 0),
        approvedHours: approved.reduce((sum, r) => sum + Number(r.hours), 0),
        pendingHours: pending.reduce((sum, r) => sum + Number(r.hours), 0),
        estimatedCompensation: approved.reduce((sum, r) => sum + (Number(r.hours) * 30), 0), // Estimate
      }
    } else if (reportFilters.value.type === 'leave') {
      if (isAdmin) {
        const params = new URLSearchParams({ start_date: startDate, end_date: endDate })
        if (selectedEmployee.value) { params.set('employee_id', String(selectedEmployee.value.id)) }
        const res = await fetch(`/api/timekeeping/reports/leave?${params}`, {
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin',
        })
        const report = await res.json()
        leaveRequestData.value = (report.data || []).map((r: any) => ({
          employee_name: r.employee?.name,
          leave_type: r.leave_type,
          start_date: r.start_date,
          end_date: r.end_date,
          total_days: r.total_days,
          status: r.status,
          reason: r.reason,
        }))
        leaveRequestSummary.value = {
          totalRequests: report.summary?.total_requests ?? 0,
          approvedDays: report.summary?.total_days_taken ?? 0,
          pending: report.summary?.pending_requests ?? 0,
          approved: report.summary?.approved_requests ?? 0,
        }
      } else {
        await fetchLeaveBalance()
      }
    } else if (reportFilters.value.type === 'summary') {
      const fetchOpts = {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin' as RequestCredentials,
      }

      if (isAdmin) {
        const empParam = selectedEmployee.value ? `&employee_id=${selectedEmployee.value.id}` : ''
        const [attRes, leaveRes, otRes] = await Promise.all([
          fetch(`/api/timekeeping/reports/attendance?start_date=${startDate}&end_date=${endDate}${empParam}`, fetchOpts),
          fetch(`/api/timekeeping/reports/leave?start_date=${startDate}&end_date=${endDate}${empParam}`, fetchOpts),
          fetch(`/api/timekeeping/reports/overtime?start_date=${startDate}&end_date=${endDate}${empParam}`, fetchOpts),
        ])
        const [attData, leaveData, otData] = await Promise.all([attRes.json(), leaveRes.json(), otRes.json()])
        const empGroups: any[] = attData.data || []
        summaryReport.value = {
          attendance: {
            total_employees: attData.summary?.total_employees ?? 0,
            present: empGroups.reduce((s: number, g: any) => s + (g.summary?.present_days ?? 0), 0),
            late: empGroups.reduce((s: number, g: any) => s + (g.summary?.late_days ?? 0), 0),
            absent: empGroups.reduce((s: number, g: any) => s + (g.summary?.absent_days ?? 0), 0),
            total_hours: empGroups.reduce((s: number, g: any) => s + (g.summary?.total_hours ?? 0), 0),
            attendance_rate: attData.summary?.overall_attendance_rate ?? 0,
          },
          leave: {
            total_requests: leaveData.summary?.total_requests ?? 0,
            approved_days: leaveData.summary?.total_days_taken ?? 0,
            pending_requests: leaveData.summary?.pending_requests ?? 0,
            approved_requests: leaveData.summary?.approved_requests ?? 0,
          },
          overtime: {
            total_hours: otData.summary?.total_hours ?? 0,
            by_type: otData.summary?.by_type ?? { weekday: 0, weekend: 0, holiday: 0 },
          },
        }
      } else {
        const res = await fetchMySummary(startDate, endDate)
        const totalDays = res.attendance?.total_days ?? 0
        const presentAndLate = (res.attendance?.present ?? 0) + (res.attendance?.late ?? 0)
        summaryReport.value = {
          attendance: {
            present: res.attendance?.present ?? 0,
            late: res.attendance?.late ?? 0,
            absent: res.attendance?.absent ?? 0,
            total_hours: res.attendance?.total_hours ?? 0,
            attendance_rate: totalDays > 0 ? Math.round((presentAndLate / totalDays) * 1000) / 10 : 0,
          },
          leave: {
            total_requests: res.leave?.total_requests ?? 0,
            approved_days: res.leave?.approved_days ?? 0,
            pending_requests: res.leave?.pending_requests ?? 0,
            approved_requests: 0,
          },
          overtime: {
            approved_hours: res.overtime?.approved_hours ?? 0,
            pending_hours: res.overtime?.pending_hours ?? 0,
            total_hours: (res.overtime?.approved_hours ?? 0) + (res.overtime?.pending_hours ?? 0),
            by_type: { weekday: 0, weekend: 0, holiday: 0 },
          },
        }
      }
    }
  } catch (e) {
    console.error('Failed to generate report:', e)
  } finally {
    isLoading.value = false
  }
}

const downloadReport = () => {
  console.log('Downloading report:', reportFilters.value)
  alert('Report download feature coming soon')
}

onMounted(async () => {
  if (!isAdmin) {
    await generateReport()
  }
  document.addEventListener('click', (e) => {
    if (autocompleteWrap.value && !autocompleteWrap.value.contains(e.target as Node)) {
      showSuggestions.value = false
    }
  })
})
</script>
