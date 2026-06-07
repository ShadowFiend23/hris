<template>
  <Layout>
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">App Settings</h1>
      <p class="mt-1 text-gray-600 dark:text-gray-400">Manage leave types, payroll schedules, and other HR configurations.</p>
    </div>

    <!-- Top-level Tabs -->
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
      <nav class="flex gap-6">
        <Link href="/app-settings/employee-settings" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Employee Settings</Link>
        <Link href="/app-settings/leave-types" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Timekeeping Settings</Link>
        <Link href="/app-settings/payroll" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Payroll Settings</Link>
        <Link href="/app-settings/allowance-types" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Allowance Settings</Link>
        <Link href="/app-settings/loan-types" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Loan Settings</Link>
        <Link href="/app-settings/holidays" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Holidays</Link>
        <Link href="/app-settings/contribution-settings" class="border-b-2 border-blue-600 pb-3 text-sm font-medium text-blue-600 dark:border-blue-400 dark:text-blue-400">Contribution Settings</Link>
      </nav>
    </div>

    <!-- Section Header -->
    <div class="mb-4">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Contribution Settings</h2>
      <p class="mt-1 text-gray-600 dark:text-gray-400">Manage statutory contribution rates for SSS, PhilHealth, Pag-IBIG, and withholding tax.</p>
    </div>

    <!-- Sub-tab buttons -->
    <div class="mb-6 flex gap-2">
      <button v-for="tab in subTabs" :key="tab.key" type="button"
        class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors"
        :class="activeTab === tab.key ? 'bg-blue-600 text-white' : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'"
        @click="activeTab = tab.key; cancelEdit()"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- ── SSS ── -->
    <template v-if="activeTab === 'sss'">
      <div class="mb-4 flex items-center justify-between">
        <div>
          <h3 class="text-base font-semibold text-gray-900 dark:text-white">SSS Contribution Brackets</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">2025 SSS table — employee 4.5%, employer 9.5%.</p>
        </div>
        <button @click="openAddBracket('sss')" class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
          <Plus :size="16" /> Add Bracket
        </button>
      </div>
      <div class="mb-3 flex justify-end">
        <input v-model="sssSearch" type="text" placeholder="Search..."
          class="w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400" />
      </div>
      <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <table class="w-full text-sm">
          <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Min Salary</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Max Salary</th>
              <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Employee</th>
              <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Employer</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Notes</th>
              <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-if="props.sss.length === 0">
              <td colspan="6" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">No SSS brackets configured.</td>
            </tr>
            <tr v-else-if="filteredSss.length === 0">
              <td colspan="6" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">No brackets match your search.</td>
            </tr>
            <tr v-for="row in paginatedSss" :key="row.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
              <template v-if="editingId !== row.id">
                <td class="px-4 py-3 text-gray-900 dark:text-white">{{ fmt(row.min_salary) }}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ row.max_salary ? fmt(row.max_salary) : '—' }}</td>
                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ row.employee_amount ? fmt(row.employee_amount) : pct(row.employee_rate) }}</td>
                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ row.employer_amount ? fmt(row.employer_amount) : pct(row.employer_rate) }}</td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ row.notes ?? '—' }}</td>
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button @click="startEdit(row)" class="rounded p-1 text-blue-600 hover:text-blue-800 dark:text-blue-400"><Pencil :size="15" /></button>
                    <button @click="deletingBracket = row" class="rounded p-1 text-red-500 hover:text-red-700 dark:text-red-400"><Trash2 :size="15" /></button>
                  </div>
                </td>
              </template>
              <template v-else>
                <td class="px-4 py-3"><input v-model.number="editForm.min_salary" type="number" min="0" step="0.01" class="w-28 rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" /></td>
                <td class="px-4 py-3"><input v-model.number="editForm.max_salary" type="number" min="0" step="0.01" placeholder="none" class="w-28 rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" /></td>
                <td class="px-4 py-3 text-right"><input v-model.number="editForm.employee_amount" type="number" min="0" step="0.01" placeholder="₱ amount" class="w-24 rounded border border-gray-300 px-2 py-1 text-right text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" /></td>
                <td class="px-4 py-3 text-right"><input v-model.number="editForm.employer_amount" type="number" min="0" step="0.01" placeholder="₱ amount" class="w-24 rounded border border-gray-300 px-2 py-1 text-right text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" /></td>
                <td class="px-4 py-3"><input v-model="editForm.notes" type="text" class="w-full rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" /></td>
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button @click="submitEdit(row.id, 'sss')" class="rounded p-1 text-green-600 hover:text-green-800 dark:text-green-400"><CheckCircle :size="15" /></button>
                    <button @click="cancelEdit" class="rounded p-1 text-gray-400 hover:text-gray-600"><X :size="15" /></button>
                  </div>
                </td>
              </template>
            </tr>
          </tbody>
        </table>
        <div v-if="props.sss.length > 0" class="flex items-center border-t border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
          <div class="flex w-1/3 items-center gap-2">
            <span class="text-sm text-gray-600 dark:text-gray-400">Per page:</span>
            <select v-model="sssPerPage" @change="changeSssPerPage"
              class="rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
              <option v-for="n in [5, 10, 25, 50]" :key="n" :value="n">{{ n }}</option>
            </select>
          </div>
          <div class="flex w-1/3 justify-center gap-2">
            <button @click="sssPage--" :disabled="sssPage <= 1"
              class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
              v-html="'&laquo;'" />
            <button v-for="p in sssTotalPages" :key="p" @click="sssPage = p"
              :class="['rounded-lg px-3 py-1 text-sm font-medium transition-colors', sssPage === p ? 'bg-blue-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700']">
              {{ p }}
            </button>
            <button @click="sssPage++" :disabled="sssPage >= sssTotalPages"
              class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
              v-html="'&raquo;'" />
          </div>
          <div class="flex w-1/3 justify-end">
            <p class="text-sm text-gray-600 dark:text-gray-400">
              {{ sssSearch ? `${filteredSss.length} of ${props.sss.length}` : props.sss.length }} items
            </p>
          </div>
        </div>
      </div>
    </template>

    <!-- ── PhilHealth ── -->
    <template v-if="activeTab === 'philhealth'">
      <div class="mb-4">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">PhilHealth Premium Rate</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">2023 UHC Law — premium shared equally between employee and employer.</p>
      </div>
      <div class="max-w-lg rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
        <form @submit.prevent="submitPhilhealth">
          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Employee Rate (%)</label>
                <input v-model.number="philForm.employee_rate_pct" type="number" min="0" max="100" step="0.01"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Employer Rate (%)</label>
                <input v-model.number="philForm.employer_rate_pct" type="number" min="0" max="100" step="0.01"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Min Monthly Employee Share (₱)</label>
                <input v-model.number="philForm.min_contribution" type="number" min="0" step="0.01"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Total monthly floor = {{ fmtNum(philForm.min_contribution * 2) }}</p>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Max Monthly Employee Share (₱)</label>
                <input v-model.number="philForm.max_contribution" type="number" min="0" step="0.01"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Total monthly cap = {{ fmtNum(philForm.max_contribution * 2) }}</p>
              </div>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
              <input v-model="philForm.notes" type="text" placeholder="e.g. 2024 amendment"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
            </div>
          </div>
          <div class="mt-5 flex items-center gap-3">
            <button type="submit" :disabled="philHealthForm.processing"
              class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
              {{ philHealthForm.processing ? 'Saving...' : 'Save PhilHealth Settings' }}
            </button>
            <p class="text-xs text-gray-500 dark:text-gray-400">Saving creates a new effective record and deactivates the old one.</p>
          </div>
        </form>
      </div>
    </template>

    <!-- ── Pag-IBIG ── -->
    <template v-if="activeTab === 'pagibig'">
      <div class="mb-4">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Pag-IBIG Contribution Brackets</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Employee 1% (salary ≤ threshold) or 2% (above), max ₱200/month. Employer 2%.</p>
      </div>
      <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <table class="w-full text-sm">
          <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Min Salary</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Max Salary</th>
              <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Employee Rate</th>
              <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Employer Rate</th>
              <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Max Contribution</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Notes</th>
              <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-if="props.pagibig.length === 0">
              <td colspan="7" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">No Pag-IBIG brackets configured.</td>
            </tr>
            <tr v-for="row in props.pagibig" :key="row.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
              <template v-if="editingId !== row.id">
                <td class="px-4 py-3 text-gray-900 dark:text-white">{{ fmt(row.min_salary) }}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ row.max_salary ? fmt(row.max_salary) : '—' }}</td>
                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ pct(row.employee_rate) }}</td>
                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ pct(row.employer_rate) }}</td>
                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ row.max_contribution ? fmt(row.max_contribution) : '—' }}</td>
                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ row.notes ?? '—' }}</td>
                <td class="px-4 py-3 text-right">
                  <button @click="startEdit(row)" class="rounded p-1 text-blue-600 hover:text-blue-800 dark:text-blue-400"><Pencil :size="15" /></button>
                </td>
              </template>
              <template v-else>
                <td class="px-4 py-3"><input v-model.number="editForm.min_salary" type="number" min="0" step="0.01" class="w-24 rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" /></td>
                <td class="px-4 py-3"><input v-model.number="editForm.max_salary" type="number" min="0" step="0.01" placeholder="none" class="w-24 rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" /></td>
                <td class="px-4 py-3 text-right"><input v-model.number="editForm.employee_rate_pct" type="number" min="0" max="100" step="0.01" class="w-20 rounded border border-gray-300 px-2 py-1 text-right text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" /><span class="ml-1 text-gray-500">%</span></td>
                <td class="px-4 py-3 text-right"><input v-model.number="editForm.employer_rate_pct" type="number" min="0" max="100" step="0.01" class="w-20 rounded border border-gray-300 px-2 py-1 text-right text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" /><span class="ml-1 text-gray-500">%</span></td>
                <td class="px-4 py-3 text-right"><input v-model.number="editForm.max_contribution" type="number" min="0" step="0.01" class="w-24 rounded border border-gray-300 px-2 py-1 text-right text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" /></td>
                <td class="px-4 py-3"><input v-model="editForm.notes" type="text" class="w-full rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" /></td>
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button @click="submitEdit(row.id, 'pagibig')" class="rounded p-1 text-green-600 hover:text-green-800 dark:text-green-400"><CheckCircle :size="15" /></button>
                    <button @click="cancelEdit" class="rounded p-1 text-gray-400 hover:text-gray-600"><X :size="15" /></button>
                  </div>
                </td>
              </template>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- ── Withholding Tax ── -->
    <template v-if="activeTab === 'tax'">
      <div class="mb-4 flex items-center justify-between">
        <div>
          <h3 class="text-base font-semibold text-gray-900 dark:text-white">Withholding Tax Brackets</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">TRAIN Law (RA 10963) — annualized method. Annual income brackets.</p>
        </div>
        <button @click="openAddBracket('tax')" class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
          <Plus :size="16" /> Add Bracket
        </button>
      </div>
      <div class="mb-3 flex justify-end">
        <input v-model="taxSearch" type="text" placeholder="Search..."
          class="w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400" />
      </div>
      <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <table class="w-full text-sm">
          <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Min Annual Income</th>
              <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Base Tax</th>
              <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Rate on Excess</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Notes</th>
              <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-if="props.tax.length === 0">
              <td colspan="5" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">No tax brackets configured.</td>
            </tr>
            <tr v-else-if="filteredTax.length === 0">
              <td colspan="5" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">No brackets match your search.</td>
            </tr>
            <tr v-for="row in paginatedTax" :key="row.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
              <template v-if="editingId !== row.id">
                <td class="px-4 py-3 text-gray-900 dark:text-white">{{ fmt(row.min_salary) }}</td>
                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ fmt(row.employee_amount ?? 0) }}</td>
                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ pct(row.employee_rate) }}</td>
                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ row.notes ?? '—' }}</td>
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button @click="startEdit(row)" class="rounded p-1 text-blue-600 hover:text-blue-800 dark:text-blue-400"><Pencil :size="15" /></button>
                    <button @click="deletingBracket = row" class="rounded p-1 text-red-500 hover:text-red-700 dark:text-red-400"><Trash2 :size="15" /></button>
                  </div>
                </td>
              </template>
              <template v-else>
                <td class="px-4 py-3"><input v-model.number="editForm.min_salary" type="number" min="0" step="1" class="w-32 rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" /></td>
                <td class="px-4 py-3 text-right"><input v-model.number="editForm.employee_amount" type="number" min="0" step="0.01" class="w-28 rounded border border-gray-300 px-2 py-1 text-right text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" /></td>
                <td class="px-4 py-3 text-right"><input v-model.number="editForm.employee_rate_pct" type="number" min="0" max="100" step="0.01" class="w-20 rounded border border-gray-300 px-2 py-1 text-right text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" /><span class="ml-1 text-gray-500">%</span></td>
                <td class="px-4 py-3"><input v-model="editForm.notes" type="text" class="w-full rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" /></td>
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button @click="submitEdit(row.id, 'tax')" class="rounded p-1 text-green-600 hover:text-green-800 dark:text-green-400"><CheckCircle :size="15" /></button>
                    <button @click="cancelEdit" class="rounded p-1 text-gray-400 hover:text-gray-600"><X :size="15" /></button>
                  </div>
                </td>
              </template>
            </tr>
          </tbody>
        </table>
        <div v-if="props.tax.length > 0" class="flex items-center border-t border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
          <div class="flex w-1/3 items-center gap-2">
            <span class="text-sm text-gray-600 dark:text-gray-400">Per page:</span>
            <select v-model="taxPerPage" @change="changeTaxPerPage"
              class="rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
              <option v-for="n in [5, 10, 25, 50]" :key="n" :value="n">{{ n }}</option>
            </select>
          </div>
          <div class="flex w-1/3 justify-center gap-2">
            <button @click="taxPage--" :disabled="taxPage <= 1"
              class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
              v-html="'&laquo;'" />
            <button v-for="p in taxTotalPages" :key="p" @click="taxPage = p"
              :class="['rounded-lg px-3 py-1 text-sm font-medium transition-colors', taxPage === p ? 'bg-blue-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700']">
              {{ p }}
            </button>
            <button @click="taxPage++" :disabled="taxPage >= taxTotalPages"
              class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
              v-html="'&raquo;'" />
          </div>
          <div class="flex w-1/3 justify-end">
            <p class="text-sm text-gray-600 dark:text-gray-400">
              {{ taxSearch ? `${filteredTax.length} of ${props.tax.length}` : props.tax.length }} items
            </p>
          </div>
        </div>
      </div>
    </template>

    <!-- Add Bracket Modal (SSS / Tax) -->
    <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="showAddModal = false">
      <div class="mx-4 w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
        <div class="mb-4 flex items-center justify-between">
          <h3 class="text-base font-semibold text-gray-900 dark:text-white">Add {{ addType === 'sss' ? 'SSS' : 'Tax' }} Bracket</h3>
          <button @click="showAddModal = false" class="text-gray-400 hover:text-gray-600"><X :size="18" /></button>
        </div>
        <form @submit.prevent="submitAdd" class="space-y-4">
          <template v-if="addType === 'sss'">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Min Salary (₱)</label>
                <input v-model.number="addForm.min_salary" type="number" min="0" step="0.01" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Max Salary (₱) <span class="font-normal text-gray-400">optional</span></label>
                <input v-model.number="addForm.max_salary" type="number" min="0" step="0.01" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Employee Contribution (₱)</label>
                <input v-model.number="addForm.employee_amount" type="number" min="0" step="0.01" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Employer Contribution (₱)</label>
                <input v-model.number="addForm.employer_amount" type="number" min="0" step="0.01" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
              </div>
            </div>
          </template>
          <template v-else>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Min Annual Income (₱)</label>
                <input v-model.number="addForm.min_salary" type="number" min="0" step="1" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Base Tax (₱)</label>
                <input v-model.number="addForm.employee_amount" type="number" min="0" step="0.01" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
              </div>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Rate on Excess (%)</label>
              <input v-model.number="addForm.employee_rate_pct" type="number" min="0" max="100" step="0.01" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
            </div>
          </template>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Notes <span class="font-normal text-gray-400">optional</span></label>
            <input v-model="addForm.notes" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
          </div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showAddModal = false" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400">Cancel</button>
            <button type="submit" :disabled="bracketAddForm.processing" class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
              {{ bracketAddForm.processing ? 'Saving...' : 'Add Bracket' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div v-if="deletingBracket" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="deletingBracket = null">
      <div class="mx-4 w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
        <div class="flex items-start gap-4">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
            <AlertTriangle class="h-5 w-5 text-red-600 dark:text-red-400" />
          </div>
          <div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Remove Bracket</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Remove the bracket starting at <strong>{{ fmt(deletingBracket.min_salary) }}</strong>? This cannot be undone.</p>
          </div>
        </div>
        <div class="mt-6 flex justify-end gap-3">
          <button @click="deletingBracket = null" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400">Cancel</button>
          <button @click="confirmDelete" :disabled="bracketDeleteForm.processing" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50">
            {{ bracketDeleteForm.processing ? 'Removing...' : 'Remove' }}
          </button>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import { AlertTriangle, CheckCircle, Pencil, Plus, Trash2, X } from 'lucide-vue-next'
import Layout from '@/components/Layout.vue'

interface Bracket {
  id: number
  type: string
  min_salary: string | number
  max_salary: string | number | null
  employee_rate: string | number | null
  employer_rate: string | number | null
  employee_amount: string | number | null
  employer_amount: string | number | null
  min_contribution: string | number | null
  max_contribution: string | number | null
  notes: string | null
}

const props = defineProps<{
  sss: Bracket[]
  philhealth: Bracket | null
  pagibig: Bracket[]
  tax: Bracket[]
}>()

// ── SSS search + pagination ──
const sssSearch = ref('')
const sssPage = ref(1)
const sssPerPage = ref(5)

const filteredSss = computed(() => {
  const q = sssSearch.value.toLowerCase()
  if (!q) { return props.sss }
  return props.sss.filter(row =>
    fmt(row.min_salary).toLowerCase().includes(q) ||
    fmt(row.max_salary).toLowerCase().includes(q) ||
    (row.notes ?? '').toLowerCase().includes(q),
  )
})

const sssTotalPages = computed(() => Math.max(1, Math.ceil(filteredSss.value.length / sssPerPage.value)))
const paginatedSss = computed(() => {
  const start = (sssPage.value - 1) * sssPerPage.value
  return filteredSss.value.slice(start, start + sssPerPage.value)
})

function changeSssPerPage(): void { sssPage.value = 1 }
watch(sssSearch, () => { sssPage.value = 1 })

// ── Tax search + pagination ──
const taxSearch = ref('')
const taxPage = ref(1)
const taxPerPage = ref(5)

const filteredTax = computed(() => {
  const q = taxSearch.value.toLowerCase()
  if (!q) { return props.tax }
  return props.tax.filter(row =>
    fmt(row.min_salary).toLowerCase().includes(q) ||
    (row.notes ?? '').toLowerCase().includes(q),
  )
})

const taxTotalPages = computed(() => Math.max(1, Math.ceil(filteredTax.value.length / taxPerPage.value)))
const paginatedTax = computed(() => {
  const start = (taxPage.value - 1) * taxPerPage.value
  return filteredTax.value.slice(start, start + taxPerPage.value)
})

function changeTaxPerPage(): void { taxPage.value = 1 }
watch(taxSearch, () => { taxPage.value = 1 })

const subTabs = [
  { key: 'sss', label: 'SSS' },
  { key: 'philhealth', label: 'PhilHealth' },
  { key: 'pagibig', label: 'Pag-IBIG' },
  { key: 'tax', label: 'Withholding Tax' },
]
const activeTab = ref('sss')

// Formatters
const fmt = (v: string | number | null | undefined) =>
  v !== null && v !== undefined
    ? new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', currencyDisplay: 'narrowSymbol' }).format(Number(v))
    : '—'

const fmtNum = (v: number) =>
  new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', currencyDisplay: 'narrowSymbol' }).format(v)

const pct = (v: string | number | null | undefined) =>
  v !== null && v !== undefined ? `${(Number(v) * 100).toFixed(2)}%` : '—'

// ── PhilHealth form ──
const philForm = ref({
  employee_rate_pct: props.philhealth ? Number(props.philhealth.employee_rate) * 100 : 2.5,
  employer_rate_pct: props.philhealth ? Number(props.philhealth.employer_rate) * 100 : 2.5,
  min_contribution: props.philhealth ? Number(props.philhealth.min_contribution) : 250,
  max_contribution: props.philhealth ? Number(props.philhealth.max_contribution) : 2500,
  notes: props.philhealth?.notes ?? '',
})

const philHealthForm = useForm({})

const submitPhilhealth = () => {
  philHealthForm
    .transform(() => ({
      employee_rate: philForm.value.employee_rate_pct / 100,
      employer_rate: philForm.value.employer_rate_pct / 100,
      min_contribution: philForm.value.min_contribution,
      max_contribution: philForm.value.max_contribution,
      notes: philForm.value.notes,
    }))
    .put('/app-settings/contribution-settings/philhealth', {})
}

// ── Inline edit (SSS, Pag-IBIG, Tax) ──
const editingId = ref<number | null>(null)
const editForm = ref({
  min_salary: 0,
  max_salary: null as number | null,
  employee_amount: null as number | null,
  employer_amount: null as number | null,
  employee_rate_pct: 0,
  employer_rate_pct: 0,
  max_contribution: null as number | null,
  notes: '',
})

const startEdit = (row: Bracket) => {
  editingId.value = row.id
  editForm.value = {
    min_salary: Number(row.min_salary),
    max_salary: row.max_salary !== null ? Number(row.max_salary) : null,
    employee_amount: row.employee_amount !== null ? Number(row.employee_amount) : null,
    employer_amount: row.employer_amount !== null ? Number(row.employer_amount) : null,
    employee_rate_pct: row.employee_rate !== null ? Number(row.employee_rate) * 100 : 0,
    employer_rate_pct: row.employer_rate !== null ? Number(row.employer_rate) * 100 : 0,
    max_contribution: row.max_contribution !== null ? Number(row.max_contribution) : null,
    notes: row.notes ?? '',
  }
}

const cancelEdit = () => { editingId.value = null }

const bracketEditForm = useForm({})

const submitEdit = (id: number, type: string) => {
  const f = editForm.value
  const payload: Record<string, unknown> = {
    min_salary: f.min_salary,
    max_salary: f.max_salary,
    notes: f.notes,
  }

  if (type === 'sss') {
    payload.employee_amount = f.employee_amount
    payload.employer_amount = f.employer_amount
    payload.employee_rate = f.employee_rate_pct / 100
    payload.employer_rate = f.employer_rate_pct / 100
  } else if (type === 'pagibig') {
    payload.employee_rate = f.employee_rate_pct / 100
    payload.employer_rate = f.employer_rate_pct / 100
    payload.max_contribution = f.max_contribution
  } else if (type === 'tax') {
    payload.employee_amount = f.employee_amount
    payload.employee_rate = f.employee_rate_pct / 100
  }

  bracketEditForm
    .transform(() => payload)
    .put(`/app-settings/contribution-settings/brackets/${id}`, {
      onSuccess: cancelEdit,
    })
}

// ── Add bracket modal (SSS / Tax) ──
const showAddModal = ref(false)
const addType = ref<'sss' | 'tax'>('sss')
const addForm = ref({
  min_salary: 0,
  max_salary: null as number | null,
  employee_amount: null as number | null,
  employer_amount: null as number | null,
  employee_rate_pct: 0,
  notes: '',
})

const openAddBracket = (type: 'sss' | 'tax') => {
  addType.value = type
  addForm.value = { min_salary: 0, max_salary: null, employee_amount: null, employer_amount: null, employee_rate_pct: 0, notes: '' }
  showAddModal.value = true
}

const bracketAddForm = useForm({})

const submitAdd = () => {
  const f = addForm.value
  const payload: Record<string, unknown> = { type: addType.value, notes: f.notes }

  if (addType.value === 'sss') {
    payload.min_salary = f.min_salary
    payload.max_salary = f.max_salary
    payload.employee_amount = f.employee_amount
    payload.employer_amount = f.employer_amount
  } else {
    payload.min_salary = f.min_salary
    payload.employee_amount = f.employee_amount
    payload.employee_rate = f.employee_rate_pct / 100
  }

  bracketAddForm
    .transform(() => payload)
    .post('/app-settings/contribution-settings/brackets', {
      onSuccess: () => { showAddModal.value = false },
    })
}

// ── Delete bracket ──
const deletingBracket = ref<Bracket | null>(null)
const bracketDeleteForm = useForm({})

const confirmDelete = () => {
  if (!deletingBracket.value) { return }
  bracketDeleteForm.delete(`/app-settings/contribution-settings/brackets/${deletingBracket.value.id}`, {
    onSuccess: () => { deletingBracket.value = null },
  })
}
</script>
