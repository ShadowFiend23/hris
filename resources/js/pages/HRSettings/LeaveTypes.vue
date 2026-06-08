<template>
  <Layout>
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">App Settings</h1>
      <p class="mt-1 text-gray-600 dark:text-gray-400">Manage leave types, payroll schedules, and other HR configurations.</p>
    </div>

    <!-- Top-level Tabs (Shift Templates removed — now a sub-tab) -->
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
      <nav class="flex gap-6">
        <Link href="/app-settings/employee-settings" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
          Employee Settings
        </Link>
        <Link href="/app-settings/timekeeping-settings" class="border-b-2 border-blue-600 pb-3 text-sm font-medium text-blue-600 dark:border-blue-400 dark:text-blue-400">
          Timekeeping Settings
        </Link>
        <Link href="/app-settings/payroll" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
          Payroll Settings
        </Link>
        <Link href="/app-settings/allowance-types" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
          Allowance Settings
        </Link>
        <Link href="/app-settings/loan-types" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
          Loan Settings
        </Link>
        <Link href="/app-settings/holidays" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
          Holidays
        </Link>
        <Link href="/app-settings/contribution-settings" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
          Contribution Settings
        </Link>
        <Link href="/app-settings/identity-settings" class="border-b-2 border-transparent pb-3 text-sm font-medium whitespace-nowrap text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
          Identity Settings
        </Link>
      </nav>
    </div>

    <!-- Section Header -->
    <div class="mb-4">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Timekeeping Settings</h2>
      <p class="mt-1 text-gray-600 dark:text-gray-400">Configure leave, overtime, and shift schedule settings.</p>
    </div>

    <!-- Sub-tab buttons -->
    <div class="mb-6 flex gap-2">
      <button
        type="button"
        class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors"
        :class="activeSubTab === 'leave'
          ? 'bg-blue-600 text-white'
          : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'"
        @click="setSubTab('leave')"
      >
        <CalendarDays :size="16" />
        Leave
      </button>
      <button
        type="button"
        class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors"
        :class="activeSubTab === 'ot'
          ? 'bg-blue-600 text-white'
          : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'"
        @click="setSubTab('ot')"
      >
        <Clock :size="16" />
        Overtime
      </button>
      <button
        type="button"
        class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors"
        :class="activeSubTab === 'shifts'
          ? 'bg-blue-600 text-white'
          : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'"
        @click="setSubTab('shifts')"
      >
        <LayoutGrid :size="16" />
        Shift Templates
      </button>
    </div>

    <!-- ═══════════════════════════════════════════ LEAVE SECTION ═══ -->
    <div v-if="activeSubTab === 'leave'">
      <!-- Leave Toggle -->
      <div class="mb-4 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Enable Leave</h3>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Allow employees to submit leave requests. When disabled, the leave section is hidden from employees.</p>
          </div>
          <button
            type="button"
            :disabled="toggleLeaveForm.processing"
            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-gray-800"
            :class="props.leaveEnabled ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-600'"
            role="switch"
            :aria-checked="props.leaveEnabled"
            @click="toggleLeaveForm.patch(toggleLeaveAction.url())"
          >
            <span class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="props.leaveEnabled ? 'translate-x-5' : 'translate-x-0'" />
          </button>
        </div>
      </div>

      <template v-if="props.leaveEnabled">
        <!-- Leave Approval Chain -->
        <div class="mb-4 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
          <div class="mb-4">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Leave Approval Chain</h3>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Define how many approvals a leave request requires and who approves at each step.</p>
          </div>

          <div class="mb-5">
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Number of Approval Steps</label>
            <div class="flex gap-2">
              <button
                v-for="n in [1, 2, 3]"
                :key="n"
                type="button"
                class="h-9 w-9 rounded-lg border text-sm font-semibold transition-colors"
                :class="leaveStepCount === n
                  ? 'border-blue-600 bg-blue-600 text-white'
                  : 'border-gray-300 bg-white text-gray-700 hover:border-blue-400 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                @click="setLeaveStepCount(n)"
              >
                {{ n }}
              </button>
            </div>
          </div>

          <div class="mb-5 space-y-3">
            <div v-for="step in leaveStepCount" :key="step" class="flex items-center gap-3">
              <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                {{ step }}
              </span>
              <div class="flex-1">
                <select
                  v-model="leaveSteps[step - 1]"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                  <option :value="null" disabled>Select approver role</option>
                  <option v-for="role in props.roles" :key="role.id" :value="role.id">
                    {{ role.name }}
                  </option>
                </select>
              </div>
              <span class="text-xs text-gray-400 dark:text-gray-500">
                {{ step === 1 ? '1st approval' : step === 2 ? '2nd approval' : '3rd approval' }}
              </span>
            </div>
          </div>

          <button
            type="button"
            :disabled="leaveChainForm.processing || !leaveChainValid"
            class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
            @click="saveLeaveChain"
          >
            {{ leaveChainForm.processing ? 'Saving...' : 'Save Approval Chain' }}
          </button>
        </div>

        <!-- Leave Types -->
        <div>
          <div class="mb-3 flex items-center justify-between">
            <div>
              <h3 class="text-base font-semibold text-gray-900 dark:text-white">Leave Types</h3>
              <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Define the leave types available to employees in your company.</p>
            </div>
            <button
              class="flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
              @click="showAddLeaveForm = true"
            >
              <Plus :size="16" />
              Add Leave Type
            </button>
          </div>
          <div class="mb-3 flex justify-end">
            <input v-model="leaveTypeSearch" type="text" placeholder="Search..." class="w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400" />
          </div>

          <!-- Add Form -->
          <div v-if="showAddLeaveForm" class="mb-4 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <h4 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">New Leave Type</h4>
            <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="submitAddLeave">
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                <input v-model="addLeaveForm.name" type="text" placeholder="e.g. Vacation Leave" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required />
                <p v-if="addLeaveForm.errors.name" class="mt-1 text-xs text-red-600">{{ addLeaveForm.errors.name }}</p>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Code</label>
                <input v-model="addLeaveForm.code" type="text" placeholder="e.g. VL" maxlength="10" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required />
                <p v-if="addLeaveForm.errors.code" class="mt-1 text-xs text-red-600">{{ addLeaveForm.errors.code }}</p>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Days Per Year</label>
                <input v-model.number="addLeaveForm.days_per_year" type="number" min="1" max="365" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required />
                <p v-if="addLeaveForm.errors.days_per_year" class="mt-1 text-xs text-red-600">{{ addLeaveForm.errors.days_per_year }}</p>
              </div>
              <div class="flex items-center gap-6 pt-6">
                <label class="flex cursor-pointer items-center gap-2">
                  <input v-model="addLeaveForm.is_paid" type="checkbox" class="rounded" />
                  <span class="text-sm text-gray-700 dark:text-gray-300">Paid Leave</span>
                </label>
                <label class="flex cursor-pointer items-center gap-2">
                  <input v-model="addLeaveForm.requires_approval" type="checkbox" class="rounded" />
                  <span class="text-sm text-gray-700 dark:text-gray-300">Requires Approval</span>
                </label>
              </div>
              <div class="flex justify-end gap-3 md:col-span-2">
                <button type="button" class="px-4 py-2 font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" @click="showAddLeaveForm = false">Cancel</button>
                <button type="submit" :disabled="addLeaveForm.processing" class="rounded-lg bg-blue-600 px-6 py-2 font-medium text-white hover:bg-blue-700 disabled:opacity-50">
                  {{ addLeaveForm.processing ? 'Saving...' : 'Save Leave Type' }}
                </button>
              </div>
            </form>
          </div>

          <!-- Leave Types Table -->
          <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <table class="w-full text-sm">
              <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Code</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Days/Year</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Type</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Approval</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                  <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <tr v-if="props.leaveTypes.length === 0">
                  <td colspan="7" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">No leave types configured yet.</td>
                </tr>
                <tr v-else-if="filteredLeaveTypes.length === 0">
                  <td colspan="7" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">No leave types match your search.</td>
                </tr>
                <tr v-for="lt in paginatedLeaveTypes" :key="lt.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                  <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                    <span v-if="leaveEditingId !== lt.id">{{ lt.name }}</span>
                    <input v-else v-model="editLeaveForm.name" class="w-full rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                  </td>
                  <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                    <span v-if="leaveEditingId !== lt.id">{{ lt.code }}</span>
                    <input v-else v-model="editLeaveForm.code" maxlength="10" class="w-20 rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                  </td>
                  <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                    <span v-if="leaveEditingId !== lt.id">{{ lt.days_per_year }}</span>
                    <input v-else v-model.number="editLeaveForm.days_per_year" type="number" min="1" max="365" class="w-20 rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                  </td>
                  <td class="px-6 py-4">
                    <span v-if="leaveEditingId !== lt.id" :class="lt.is_paid ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'" class="rounded-full px-2 py-0.5 text-xs font-medium">
                      {{ lt.is_paid ? 'Paid' : 'Unpaid' }}
                    </span>
                    <select v-else v-model="editLeaveForm.is_paid" class="rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                      <option :value="true">Paid</option>
                      <option :value="false">Unpaid</option>
                    </select>
                  </td>
                  <td class="px-6 py-4">
                    <span v-if="leaveEditingId !== lt.id" :class="lt.requires_approval ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'" class="rounded-full px-2 py-0.5 text-xs font-medium">
                      {{ lt.requires_approval ? 'Required' : 'Auto' }}
                    </span>
                    <select v-else v-model="editLeaveForm.requires_approval" class="rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                      <option :value="true">Required</option>
                      <option :value="false">Auto</option>
                    </select>
                  </td>
                  <td class="px-6 py-4">
                    <span v-if="leaveEditingId !== lt.id" :class="lt.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'" class="rounded-full px-2 py-0.5 text-xs font-medium">
                      {{ lt.is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <select v-else v-model="editLeaveForm.is_active" class="rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                      <option :value="true">Active</option>
                      <option :value="false">Inactive</option>
                    </select>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <div v-if="leaveEditingId !== lt.id" class="flex items-center justify-end gap-2">
                      <button class="rounded p-1 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200" title="Edit" @click="startLeaveEdit(lt)"><Pencil :size="16" /></button>
                      <button v-if="lt.is_active" class="rounded p-1 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-200" title="Deactivate" @click="confirmLeaveDeactivate(lt)"><Trash2 :size="16" /></button>
                    </div>
                    <div v-else class="flex items-center justify-end gap-2">
                      <button class="rounded p-1 text-green-600 hover:text-green-800 dark:text-green-400" title="Save" @click="submitLeaveEdit(lt.id)"><CheckCircle :size="16" /></button>
                      <button class="rounded p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400" title="Cancel" @click="cancelLeaveEdit"><X :size="16" /></button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
            <!-- Leave Types Pagination -->
            <div v-if="props.leaveTypes.length > 0" class="flex items-center border-t border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
              <div class="flex w-1/3 items-center gap-2">
                <span class="text-sm text-gray-600 dark:text-gray-400">Per page:</span>
                <select v-model="leaveTypePerPage" @change="changeLeaveTypePerPage" class="rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                  <option v-for="n in [5, 10, 25, 50]" :key="n" :value="n">{{ n }}</option>
                </select>
              </div>
              <div class="flex w-1/3 justify-center gap-2">
                <button @click="leaveTypePage--" :disabled="leaveTypePage <= 1" class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700" v-html="'&laquo;'" />
                <button v-for="p in leaveTypeTotalPages" :key="p" @click="leaveTypePage = p" :class="['rounded-lg px-3 py-1 text-sm font-medium transition-colors', leaveTypePage === p ? 'bg-blue-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700']">{{ p }}</button>
                <button @click="leaveTypePage++" :disabled="leaveTypePage >= leaveTypeTotalPages" class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700" v-html="'&raquo;'" />
              </div>
              <div class="flex w-1/3 justify-end">
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ leaveTypeSearch ? `${filteredLeaveTypes.length} of ${props.leaveTypes.length}` : props.leaveTypes.length }} leave types</p>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Leave disabled placeholder -->
      <div v-else class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center dark:border-gray-600 dark:bg-gray-800/50">
        <ToggleLeft :size="36" class="mx-auto mb-3 text-gray-400 dark:text-gray-500" />
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Leave is currently disabled for this company.</p>
        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Toggle the switch above to enable leave and configure approval workflows.</p>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════ OT SECTION ═══ -->
    <div v-else-if="activeSubTab === 'ot'">
      <!-- OT Toggle -->
      <div class="mb-4 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Enable Overtime (OT)</h3>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Allow employees to submit overtime requests. When disabled, OT filing is hidden from employees.</p>
          </div>
          <button
            type="button"
            :disabled="toggleOtForm.processing"
            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-gray-800"
            :class="props.otEnabled ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-600'"
            role="switch"
            :aria-checked="props.otEnabled"
            @click="toggleOtForm.patch(toggleOtAction.url())"
          >
            <span class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="props.otEnabled ? 'translate-x-5' : 'translate-x-0'" />
          </button>
        </div>
      </div>

      <template v-if="props.otEnabled">
        <!-- OT Approval Chain -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
          <div class="mb-4">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">OT Approval Chain</h3>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Define how many approvals an overtime request requires and who approves at each step.</p>
          </div>

          <div class="mb-5">
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Number of Approval Steps</label>
            <div class="flex gap-2">
              <button
                v-for="n in [1, 2, 3]"
                :key="n"
                type="button"
                class="h-9 w-9 rounded-lg border text-sm font-semibold transition-colors"
                :class="otStepCount === n
                  ? 'border-blue-600 bg-blue-600 text-white'
                  : 'border-gray-300 bg-white text-gray-700 hover:border-blue-400 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                @click="setOtStepCount(n)"
              >
                {{ n }}
              </button>
            </div>
          </div>

          <div class="mb-5 space-y-3">
            <div v-for="step in otStepCount" :key="step" class="flex items-center gap-3">
              <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                {{ step }}
              </span>
              <div class="flex-1">
                <select
                  v-model="otSteps[step - 1]"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                  <option :value="null" disabled>Select approver role</option>
                  <option v-for="role in props.roles" :key="role.id" :value="role.id">
                    {{ role.name }}
                  </option>
                </select>
              </div>
              <span class="text-xs text-gray-400 dark:text-gray-500">
                {{ step === 1 ? '1st approval' : step === 2 ? '2nd approval' : '3rd approval' }}
              </span>
            </div>
          </div>

          <button
            type="button"
            :disabled="otChainForm.processing || !otChainValid"
            class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
            @click="saveOtChain"
          >
            {{ otChainForm.processing ? 'Saving...' : 'Save Approval Chain' }}
          </button>
        </div>
      </template>

      <!-- OT disabled placeholder -->
      <div v-else class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center dark:border-gray-600 dark:bg-gray-800/50">
        <ToggleLeft :size="36" class="mx-auto mb-3 text-gray-400 dark:text-gray-500" />
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Overtime is currently disabled for this company.</p>
        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Toggle the switch above to enable overtime and configure approval workflows.</p>
      </div>
    </div>

    <!-- ═══════════════════════════════════════ SHIFTS SECTION ═══ -->
    <div v-else-if="activeSubTab === 'shifts'">
      <!-- Global Shift Swap Toggle -->
      <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Enable Shift Swap</h3>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Allow employees to request shift swaps with peers. Per-shift eligibility can be configured in the templates below.</p>
          </div>
          <button
            type="button"
            :disabled="toggleSwapGlobalForm.processing"
            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-gray-800"
            :class="props.swapEnabled ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-600'"
            role="switch"
            :aria-checked="props.swapEnabled"
            @click="toggleSwapGlobalForm.patch(toggleSwapGlobalAction.url())"
          >
            <span class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="props.swapEnabled ? 'translate-x-5' : 'translate-x-0'" />
          </button>
        </div>
      </div>

      <!-- Schedule Change Approval Chain -->
      <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-4">
          <h3 class="text-base font-semibold text-gray-900 dark:text-white">Schedule Change Approval Chain</h3>
          <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Define how many approvals a one-day schedule change request requires and who approves at each step.</p>
        </div>

        <div class="mb-5">
          <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Number of Approval Steps</label>
          <div class="flex gap-2">
            <button
              v-for="n in [1, 2, 3]"
              :key="n"
              type="button"
              class="h-9 w-9 rounded-lg border text-sm font-semibold transition-colors"
              :class="scheduleChangeStepCount === n
                ? 'border-blue-600 bg-blue-600 text-white'
                : 'border-gray-300 bg-white text-gray-700 hover:border-blue-400 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300'"
              @click="setScheduleChangeStepCount(n)"
            >
              {{ n }}
            </button>
          </div>
        </div>

        <div class="mb-5 space-y-3">
          <div v-for="step in scheduleChangeStepCount" :key="step" class="flex items-center gap-3">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
              {{ step }}
            </span>
            <div class="flex-1">
              <select
                v-model="scheduleChangeSteps[step - 1]"
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              >
                <option :value="null" disabled>Select approver role</option>
                <option v-for="role in props.roles" :key="role.id" :value="role.id">
                  {{ role.name }}
                </option>
              </select>
            </div>
            <span class="text-xs text-gray-400 dark:text-gray-500">
              {{ step === 1 ? '1st approval' : step === 2 ? '2nd approval' : '3rd approval' }}
            </span>
          </div>
        </div>

        <button
          type="button"
          :disabled="scheduleChangeChainForm.processing || !scheduleChangeChainValid"
          class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
          @click="saveScheduleChangeChain"
        >
          {{ scheduleChangeChainForm.processing ? 'Saving...' : 'Save Approval Chain' }}
        </button>
      </div>

      <!-- Section header -->
      <div class="mb-3 flex items-center justify-between">
        <div>
          <h3 class="text-base font-semibold text-gray-900 dark:text-white">Shift Templates</h3>
          <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Configure work shift schedules including lunch-break windows for split-shift DTR tracking.</p>
        </div>
        <button
          class="flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
          @click="showAddShiftForm = true"
        >
          <Plus :size="16" />
          Add Shift Template
        </button>
      </div>
      <div class="mb-3 flex justify-end">
        <input v-model="shiftSearch" type="text" placeholder="Search..." class="w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400" />
      </div>

      <!-- Add Shift Form -->
      <div v-if="showAddShiftForm" class="mb-4 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
        <h4 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">New Shift Template</h4>
        <form class="grid grid-cols-1 gap-4 md:grid-cols-3" @submit.prevent="submitAddShift">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
            <input v-model="addShiftForm.name" type="text" placeholder="e.g. Day Shift" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required />
            <p v-if="addShiftForm.errors.name" class="mt-1 text-xs text-red-600">{{ addShiftForm.errors.name }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Start Time</label>
            <input v-model="addShiftForm.start_time" type="time" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required />
            <p v-if="addShiftForm.errors.start_time" class="mt-1 text-xs text-red-600">{{ addShiftForm.errors.start_time }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">End Time</label>
            <input v-model="addShiftForm.end_time" type="time" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required />
            <p v-if="addShiftForm.errors.end_time" class="mt-1 text-xs text-red-600">{{ addShiftForm.errors.end_time }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Break Duration (minutes)</label>
            <input v-model.number="addShiftForm.break_duration" type="number" min="0" max="180" placeholder="e.g. 60" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
              Break Start Time
              <span class="text-xs text-gray-400">(for split-shift DTR)</span>
            </label>
            <input v-model="addShiftForm.break_start_time" type="time" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
            <p v-if="addShiftForm.errors.break_start_time" class="mt-1 text-xs text-red-600">{{ addShiftForm.errors.break_start_time }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
              Break End Time
              <span class="text-xs text-gray-400">(for split-shift DTR)</span>
            </label>
            <input v-model="addShiftForm.break_end_time" type="time" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
            <p v-if="addShiftForm.errors.break_end_time" class="mt-1 text-xs text-red-600">{{ addShiftForm.errors.break_end_time }}</p>
          </div>
          <div class="flex items-center gap-3 md:col-span-3">
            <label class="flex cursor-pointer items-center gap-2">
              <input v-model="addShiftForm.swap_enabled" type="checkbox" class="rounded" />
              <span class="text-sm text-gray-700 dark:text-gray-300">Enable Shift Swap</span>
            </label>
            <span class="text-xs text-gray-400 dark:text-gray-500">Allow employees assigned to this shift to request shift swaps with peers.</span>
          </div>
          <div class="flex justify-end gap-3 md:col-span-3">
            <button type="button" class="px-4 py-2 font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" @click="cancelAddShift">Cancel</button>
            <button type="submit" :disabled="addShiftForm.processing" class="rounded-lg bg-blue-600 px-6 py-2 font-medium text-white hover:bg-blue-700 disabled:opacity-50">
              {{ addShiftForm.processing ? 'Saving...' : 'Save Shift Template' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Shift Templates Table -->
      <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <table class="w-full text-sm">
          <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Start</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">End</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Break</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Break Window</th>
              <th v-if="props.swapEnabled" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Shift Swap</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-if="props.shiftTemplates.length === 0">
              <td :colspan="props.swapEnabled ? 8 : 7" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">No shift templates configured yet.</td>
            </tr>
            <tr v-else-if="filteredShifts.length === 0">
              <td :colspan="props.swapEnabled ? 8 : 7" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">No shift templates match your search.</td>
            </tr>
            <tr v-for="st in paginatedShifts" :key="st.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
              <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                <span v-if="shiftEditingId !== st.id">{{ st.name }}</span>
                <input v-else v-model="editShiftForm.name" class="w-full rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
              </td>
              <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                <span v-if="shiftEditingId !== st.id">{{ formatTime(st.start_time) }}</span>
                <input v-else v-model="editShiftForm.start_time" type="time" class="rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
              </td>
              <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                <span v-if="shiftEditingId !== st.id">{{ formatTime(st.end_time) }}</span>
                <input v-else v-model="editShiftForm.end_time" type="time" class="rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
              </td>
              <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                <span v-if="shiftEditingId !== st.id">{{ st.break_duration ? st.break_duration + ' min' : '—' }}</span>
                <input v-else v-model.number="editShiftForm.break_duration" type="number" min="0" max="180" class="w-20 rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
              </td>
              <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                <span v-if="shiftEditingId !== st.id">
                  <span v-if="st.break_start_time && st.break_end_time" class="rounded-full bg-blue-50 px-2 py-0.5 text-xs text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                    {{ formatTime(st.break_start_time) }} – {{ formatTime(st.break_end_time) }}
                  </span>
                  <span v-else class="text-gray-400 dark:text-gray-500">—</span>
                </span>
                <div v-else class="flex items-center gap-1">
                  <input v-model="editShiftForm.break_start_time" type="time" class="w-28 rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                  <span class="text-gray-400">–</span>
                  <input v-model="editShiftForm.break_end_time" type="time" class="w-28 rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                </div>
              </td>
              <td v-if="props.swapEnabled" class="px-6 py-4">
                <button
                  v-if="shiftEditingId !== st.id"
                  type="button"
                  class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800"
                  :class="st.swap_enabled ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-600'"
                  role="switch"
                  :aria-checked="st.swap_enabled"
                  :title="st.swap_enabled ? 'Disable shift swap' : 'Enable shift swap'"
                  @click="toggleSwap(st)"
                >
                  <span class="pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="st.swap_enabled ? 'translate-x-4' : 'translate-x-0'" />
                </button>
                <select v-else v-model="editShiftForm.swap_enabled" class="rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                  <option :value="true">Enabled</option>
                  <option :value="false">Disabled</option>
                </select>
              </td>
              <td class="px-6 py-4">
                <span :class="st.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'" class="rounded-full px-2 py-0.5 text-xs font-medium">
                  {{ st.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <div v-if="shiftEditingId !== st.id" class="flex items-center justify-end gap-2">
                  <button class="rounded p-1 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200" title="Edit" @click="startShiftEdit(st)"><Pencil :size="16" /></button>
                  <button v-if="st.is_active" class="rounded p-1 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-200" title="Deactivate" @click="confirmShiftDeactivate(st)"><Trash2 :size="16" /></button>
                </div>
                <div v-else class="flex items-center justify-end gap-2">
                  <button class="rounded p-1 text-green-600 hover:text-green-800 dark:text-green-400" title="Save" @click="submitShiftEdit(st.id)"><CheckCircle :size="16" /></button>
                  <button class="rounded p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400" title="Cancel" @click="cancelShiftEdit"><X :size="16" /></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <!-- Shift Templates Pagination -->
        <div v-if="props.shiftTemplates.length > 0" class="flex items-center border-t border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
          <div class="flex w-1/3 items-center gap-2">
            <span class="text-sm text-gray-600 dark:text-gray-400">Per page:</span>
            <select v-model="shiftPerPage" @change="changeShiftPerPage" class="rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
              <option v-for="n in [5, 10, 25, 50]" :key="n" :value="n">{{ n }}</option>
            </select>
          </div>
          <div class="flex w-1/3 justify-center gap-2">
            <button @click="shiftPage--" :disabled="shiftPage <= 1" class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700" v-html="'&laquo;'" />
            <button v-for="p in shiftTotalPages" :key="p" @click="shiftPage = p" :class="['rounded-lg px-3 py-1 text-sm font-medium transition-colors', shiftPage === p ? 'bg-blue-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700']">{{ p }}</button>
            <button @click="shiftPage++" :disabled="shiftPage >= shiftTotalPages" class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700" v-html="'&raquo;'" />
          </div>
          <div class="flex w-1/3 justify-end">
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ shiftSearch ? `${filteredShifts.length} of ${props.shiftTemplates.length}` : props.shiftTemplates.length }} shift templates</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Leave Type Deactivate Confirmation Modal -->
    <div v-if="deactivatingLeaveType" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
      <div class="mx-4 w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
        <div class="flex items-start gap-4">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
            <AlertTriangle class="h-5 w-5 text-red-600 dark:text-red-400" />
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Deactivate Leave Type</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
              Are you sure you want to deactivate <strong>{{ deactivatingLeaveType.name }}</strong>?
              Existing balances will be preserved. Employees won't be able to request this leave type.
            </p>
          </div>
        </div>
        <div class="mt-6 flex justify-end gap-3">
          <button class="px-4 py-2 font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" @click="deactivatingLeaveType = null">Cancel</button>
          <button :disabled="destroyLeaveForm.processing" class="rounded-lg bg-red-600 px-4 py-2 font-medium text-white hover:bg-red-700 disabled:opacity-50" @click="submitLeaveDeactivate">
            {{ destroyLeaveForm.processing ? 'Deactivating...' : 'Deactivate' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Shift Template Deactivate Confirmation Modal -->
    <div v-if="deactivatingShift" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
      <div class="mx-4 w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
        <div class="flex items-start gap-4">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
            <AlertTriangle class="h-5 w-5 text-red-600 dark:text-red-400" />
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Deactivate Shift Template</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
              Are you sure you want to deactivate <strong>{{ deactivatingShift.name }}</strong>?
              Employees currently assigned to this shift will not be affected immediately.
            </p>
          </div>
        </div>
        <div class="mt-6 flex justify-end gap-3">
          <button class="px-4 py-2 font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" @click="deactivatingShift = null">Cancel</button>
          <button :disabled="destroyShiftForm.processing" class="rounded-lg bg-red-600 px-4 py-2 font-medium text-white hover:bg-red-700 disabled:opacity-50" @click="submitShiftDeactivate">
            {{ destroyShiftForm.processing ? 'Deactivating...' : 'Deactivate' }}
          </button>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { AlertTriangle, CalendarDays, CheckCircle, Clock, LayoutGrid, Pencil, Plus, ToggleLeft, Trash2, X } from 'lucide-vue-next'
import Layout from '@/components/Layout.vue'
import {
  toggleLeave as toggleLeaveAction,
  toggleOt as toggleOtAction,
  toggleSwapGlobal as toggleSwapGlobalAction,
  saveApprovalChain as saveApprovalChainAction,
} from '@/actions/App/Modules/Timekeeping/Controllers/TimekeepingSettingsController'
import {
  store as leaveTypeStore,
  update as leaveTypeUpdate,
  destroy as leaveTypeDestroy,
} from '@/actions/App/Modules/Timekeeping/Controllers/LeaveTypeController'
import {
  store as shiftStore,
  update as shiftUpdate,
  destroy as shiftDestroy,
  toggleSwap as shiftToggleSwapAction,
} from '@/actions/App/Modules/Timekeeping/Controllers/ShiftTemplatesController'

type SubTab = 'leave' | 'ot' | 'shifts'

interface LeaveTypeItem {
  id: number
  name: string
  code: string
  days_per_year: number
  is_paid: boolean
  requires_approval: boolean
  is_active: boolean
}

interface ShiftTemplate {
  id: number
  name: string
  start_time: string
  end_time: string
  break_duration: number | null
  break_start_time: string | null
  break_end_time: string | null
  is_active: boolean
  swap_enabled: boolean
}

interface Role {
  id: number
  name: string
  slug: string
}

interface ApprovalStep {
  order: number
  role_id: number | null
}

const props = defineProps<{
  leaveTypes: LeaveTypeItem[]
  leaveEnabled: boolean
  otEnabled: boolean
  swapEnabled: boolean
  leaveApprovalSteps: ApprovalStep[]
  otApprovalSteps: ApprovalStep[]
  scheduleChangeApprovalSteps: ApprovalStep[]
  roles: Role[]
  shiftTemplates: ShiftTemplate[]
}>()

// ─── Sub-tabs ─────────────────────────────────────────────────────────────────
const urlParams = new URLSearchParams(window.location.search)
const activeSubTab = ref<SubTab>((urlParams.get('sub') as SubTab) || 'leave')

// ─── Search & Pagination ───────────────────────────────────────────────────────
const leaveTypeSearch = ref('')
const leaveTypePage = ref(1)
const leaveTypePerPage = ref(5)
const filteredLeaveTypes = computed(() => {
  const q = leaveTypeSearch.value.toLowerCase()
  if (!q) { return props.leaveTypes }
  return props.leaveTypes.filter(lt =>
    lt.name.toLowerCase().includes(q) || lt.code.toLowerCase().includes(q),
  )
})
const leaveTypeTotalPages = computed(() => Math.max(1, Math.ceil(filteredLeaveTypes.value.length / leaveTypePerPage.value)))
const paginatedLeaveTypes = computed(() => {
  const start = (leaveTypePage.value - 1) * leaveTypePerPage.value
  return filteredLeaveTypes.value.slice(start, start + leaveTypePerPage.value)
})
function changeLeaveTypePerPage(): void { leaveTypePage.value = 1 }
watch(leaveTypeSearch, () => { leaveTypePage.value = 1 })

const shiftSearch = ref('')
const shiftPage = ref(1)
const shiftPerPage = ref(5)
const filteredShifts = computed(() => {
  const q = shiftSearch.value.toLowerCase()
  if (!q) { return props.shiftTemplates }
  return props.shiftTemplates.filter(st => st.name.toLowerCase().includes(q))
})
const shiftTotalPages = computed(() => Math.max(1, Math.ceil(filteredShifts.value.length / shiftPerPage.value)))
const paginatedShifts = computed(() => {
  const start = (shiftPage.value - 1) * shiftPerPage.value
  return filteredShifts.value.slice(start, start + shiftPerPage.value)
})
function changeShiftPerPage(): void { shiftPage.value = 1 }
watch(shiftSearch, () => { shiftPage.value = 1 })

function setSubTab(tab: SubTab): void {
  activeSubTab.value = tab
}

// ─── Toggles ──────────────────────────────────────────────────────────────────
const toggleLeaveForm = useForm({})
const toggleOtForm = useForm({})
const toggleSwapGlobalForm = useForm({})

// ─── Leave Approval Chain ──────────────────────────────────────────────────────
const leaveStepCount = ref(props.leaveApprovalSteps.length || 1)
const leaveSteps = ref<(number | null)[]>(
  Array.from({ length: 3 }, (_, i) => props.leaveApprovalSteps[i]?.role_id ?? null),
)

function setLeaveStepCount(n: number): void {
  leaveStepCount.value = n
}

const leaveChainValid = computed(() =>
  Array.from({ length: leaveStepCount.value }, (_, i) => leaveSteps.value[i]).every(id => id !== null),
)

const leaveChainForm = useForm({ type: 'leave', steps: [] as ApprovalStep[] })

function saveLeaveChain(): void {
  leaveChainForm.steps = Array.from({ length: leaveStepCount.value }, (_, i) => ({
    order: i + 1,
    role_id: leaveSteps.value[i] as number,
  }))
  leaveChainForm.post(saveApprovalChainAction.url())
}

// ─── OT Approval Chain ────────────────────────────────────────────────────────
const otStepCount = ref(props.otApprovalSteps.length || 1)
const otSteps = ref<(number | null)[]>(
  Array.from({ length: 3 }, (_, i) => props.otApprovalSteps[i]?.role_id ?? null),
)

function setOtStepCount(n: number): void {
  otStepCount.value = n
}

const otChainValid = computed(() =>
  Array.from({ length: otStepCount.value }, (_, i) => otSteps.value[i]).every(id => id !== null),
)

const otChainForm = useForm({ type: 'ot', steps: [] as ApprovalStep[] })

function saveOtChain(): void {
  otChainForm.steps = Array.from({ length: otStepCount.value }, (_, i) => ({
    order: i + 1,
    role_id: otSteps.value[i] as number,
  }))
  otChainForm.post(saveApprovalChainAction.url())
}

// ─── Schedule Change Approval Chain ───────────────────────────────────────────
const scheduleChangeStepCount = ref(props.scheduleChangeApprovalSteps.length || 1)
const scheduleChangeSteps = ref<(number | null)[]>(
  Array.from({ length: 3 }, (_, i) => props.scheduleChangeApprovalSteps[i]?.role_id ?? null),
)

function setScheduleChangeStepCount(n: number): void {
  scheduleChangeStepCount.value = n
}

const scheduleChangeChainValid = computed(() =>
  Array.from({ length: scheduleChangeStepCount.value }, (_, i) => scheduleChangeSteps.value[i]).every(id => id !== null),
)

const scheduleChangeChainForm = useForm({ type: 'schedule_change', steps: [] as ApprovalStep[] })

function saveScheduleChangeChain(): void {
  scheduleChangeChainForm.steps = Array.from({ length: scheduleChangeStepCount.value }, (_, i) => ({
    order: i + 1,
    role_id: scheduleChangeSteps.value[i] as number,
  }))
  scheduleChangeChainForm.post(saveApprovalChainAction.url())
}

// ─── Leave Types CRUD ──────────────────────────────────────────────────────────
const showAddLeaveForm = ref(false)
const addLeaveForm = useForm({
  name: '',
  code: '',
  days_per_year: 15,
  is_paid: true,
  requires_approval: true,
})

function submitAddLeave(): void {
  addLeaveForm.post(leaveTypeStore.url(), {
    onSuccess: () => {
      showAddLeaveForm.value = false
      addLeaveForm.reset()
    },
  })
}

const leaveEditingId = ref<number | null>(null)
const editLeaveForm = useForm({
  name: '',
  code: '',
  days_per_year: 15,
  is_paid: true,
  requires_approval: true,
  is_active: true,
})

function startLeaveEdit(lt: LeaveTypeItem): void {
  leaveEditingId.value = lt.id
  editLeaveForm.name = lt.name
  editLeaveForm.code = lt.code
  editLeaveForm.days_per_year = lt.days_per_year
  editLeaveForm.is_paid = lt.is_paid
  editLeaveForm.requires_approval = lt.requires_approval
  editLeaveForm.is_active = lt.is_active
}

function cancelLeaveEdit(): void {
  leaveEditingId.value = null
  editLeaveForm.clearErrors()
}

function submitLeaveEdit(id: number): void {
  editLeaveForm.put(leaveTypeUpdate.url(id), {
    onSuccess: () => { leaveEditingId.value = null },
  })
}

const deactivatingLeaveType = ref<LeaveTypeItem | null>(null)
const destroyLeaveForm = useForm({})

function confirmLeaveDeactivate(lt: LeaveTypeItem): void {
  deactivatingLeaveType.value = lt
}

function submitLeaveDeactivate(): void {
  if (!deactivatingLeaveType.value) { return }
  destroyLeaveForm.delete(leaveTypeDestroy.url(deactivatingLeaveType.value.id), {
    onSuccess: () => { deactivatingLeaveType.value = null },
  })
}

// ─── Shift Templates CRUD ─────────────────────────────────────────────────────
function formatTime(time: string | null): string {
  if (!time) { return '—' }
  return time.substring(0, 5)
}

const showAddShiftForm = ref(false)
const addShiftForm = useForm({
  name: '',
  start_time: '',
  end_time: '',
  break_duration: null as number | null,
  break_start_time: '',
  break_end_time: '',
  swap_enabled: false,
})

function cancelAddShift(): void {
  showAddShiftForm.value = false
  addShiftForm.reset()
  addShiftForm.clearErrors()
}

function submitAddShift(): void {
  addShiftForm.post(shiftStore.url(), {
    onSuccess: () => {
      showAddShiftForm.value = false
      addShiftForm.reset()
    },
  })
}

const shiftEditingId = ref<number | null>(null)
const editShiftForm = useForm({
  name: '',
  start_time: '',
  end_time: '',
  break_duration: null as number | null,
  break_start_time: '',
  break_end_time: '',
  is_active: true,
  swap_enabled: false,
})

function startShiftEdit(st: ShiftTemplate): void {
  shiftEditingId.value = st.id
  editShiftForm.name = st.name
  editShiftForm.start_time = st.start_time ? st.start_time.substring(0, 5) : ''
  editShiftForm.end_time = st.end_time ? st.end_time.substring(0, 5) : ''
  editShiftForm.break_duration = st.break_duration
  editShiftForm.break_start_time = st.break_start_time ? st.break_start_time.substring(0, 5) : ''
  editShiftForm.break_end_time = st.break_end_time ? st.break_end_time.substring(0, 5) : ''
  editShiftForm.is_active = st.is_active
  editShiftForm.swap_enabled = st.swap_enabled
}

function cancelShiftEdit(): void {
  shiftEditingId.value = null
  editShiftForm.clearErrors()
}

function submitShiftEdit(id: number): void {
  editShiftForm.put(shiftUpdate.url(id), {
    onSuccess: () => { shiftEditingId.value = null },
  })
}

const deactivatingShift = ref<ShiftTemplate | null>(null)
const destroyShiftForm = useForm({})

function confirmShiftDeactivate(st: ShiftTemplate): void {
  deactivatingShift.value = st
}

function submitShiftDeactivate(): void {
  if (!deactivatingShift.value) { return }
  destroyShiftForm.delete(shiftDestroy.url(deactivatingShift.value.id), {
    onSuccess: () => { deactivatingShift.value = null },
  })
}

function toggleSwap(st: ShiftTemplate): void {
  router.patch(shiftToggleSwapAction.url(st.id))
}
</script>
