<template>
  <Layout>
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">HR Settings</h1>
      <p class="mt-1 text-gray-600 dark:text-gray-400">Manage leave types, payroll schedules, and other HR configurations.</p>
    </div>

    <!-- Top-level Tabs -->
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
      <nav class="flex gap-6">
        <Link href="/hr-settings/employee-settings" class="border-b-2 border-blue-600 pb-3 text-sm font-medium text-blue-600 dark:border-blue-400 dark:text-blue-400">
          Employee Settings
        </Link>
        <Link href="/hr-settings/leave-types" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
          Timekeeping Settings
        </Link>
        <Link href="/hr-settings/payroll" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
          Payroll Settings
        </Link>
        <Link href="/hr-settings/allowance-types" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
          Allowance Settings
        </Link>
        <Link href="/hr-settings/loan-types" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
          Loan Settings
        </Link>
        <Link href="/hr-settings/holidays" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
          Holidays
        </Link>
      </nav>
    </div>

    <!-- Section Header -->
    <div class="mb-4">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Employee Settings</h2>
      <p class="mt-1 text-gray-600 dark:text-gray-400">Manage departments, positions, and reporting hierarchies.</p>
    </div>

    <!-- Sub-tab buttons -->
    <div class="mb-6 flex gap-2">
      <button
        type="button"
        class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors"
        :class="activeSubTab === 'departments'
          ? 'bg-blue-600 text-white'
          : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'"
        @click="activeSubTab = 'departments'"
      >
        <Building2 :size="16" />
        Departments
      </button>
      <button
        type="button"
        class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors"
        :class="activeSubTab === 'positions'
          ? 'bg-blue-600 text-white'
          : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'"
        @click="activeSubTab = 'positions'"
      >
        <Briefcase :size="16" />
        Positions
      </button>
    </div>

    <!-- Flash messages -->
    <div v-if="flashSuccess" class="mb-6 flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 dark:border-green-700 dark:bg-green-900/30 dark:text-green-300">
      <CheckCircle :size="18" class="shrink-0 text-green-600 dark:text-green-400" />
      {{ flashSuccess }}
    </div>
    <div v-if="flashError" class="mb-6 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800 dark:border-red-700 dark:bg-red-900/30 dark:text-red-300">
      <AlertTriangle :size="18" class="shrink-0 text-red-600 dark:text-red-400" />
      {{ flashError }}
    </div>

    <!-- ════════════════════════════════════ DEPARTMENTS TAB ════════ -->
    <div v-if="activeSubTab === 'departments'">
      <!-- Header + Add button -->
      <div class="mb-4 flex items-center justify-between">
        <div>
          <h3 class="text-base font-semibold text-gray-900 dark:text-white">Departments</h3>
          <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Manage the departments within your organization.</p>
        </div>
        <button
          class="flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
          @click="showAddDeptForm = true"
        >
          <Plus :size="16" />
          Add Department
        </button>
      </div>
      <!-- Search -->
      <div class="mb-3 flex justify-end">
        <input v-model="deptSearch" type="text" placeholder="Search..." class="w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400" />
      </div>

      <!-- Add Form -->
      <div v-if="showAddDeptForm" class="mb-4 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
        <h4 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">New Department</h4>
        <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="submitAddDept">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
            <input v-model="addDeptForm.name" type="text" placeholder="e.g. Information Technology" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required />
            <p v-if="addDeptForm.errors.name" class="mt-1 text-xs text-red-600">{{ addDeptForm.errors.name }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
            <input v-model="addDeptForm.description" type="text" placeholder="Optional description" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
            <p v-if="addDeptForm.errors.description" class="mt-1 text-xs text-red-600">{{ addDeptForm.errors.description }}</p>
          </div>
          <div class="flex justify-end gap-3 md:col-span-2">
            <button type="button" class="px-4 py-2 font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" @click="cancelAddDept">Cancel</button>
            <button type="submit" :disabled="addDeptForm.processing" class="rounded-lg bg-blue-600 px-6 py-2 font-medium text-white hover:bg-blue-700 disabled:opacity-50">
              {{ addDeptForm.processing ? 'Saving...' : 'Save Department' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Departments Table -->
      <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <table class="w-full text-sm">
          <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Description</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Employees</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-if="props.departments.length === 0">
              <td colspan="5" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">No departments configured yet.</td>
            </tr>
            <tr v-else-if="filteredDepts.length === 0">
              <td colspan="5" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">No departments match your search.</td>
            </tr>
            <tr v-for="dept in paginatedDepts" :key="dept.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
              <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                <span v-if="deptEditingId !== dept.id">{{ dept.name }}</span>
                <input v-else v-model="editDeptForm.name" class="w-full rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                <p v-if="deptEditingId === dept.id && editDeptForm.errors.name" class="mt-0.5 text-xs text-red-600">{{ editDeptForm.errors.name }}</p>
              </td>
              <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                <span v-if="deptEditingId !== dept.id">{{ dept.description || '—' }}</span>
                <input v-else v-model="editDeptForm.description" class="w-full rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
              </td>
              <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ dept.employees_count }}</td>
              <td class="px-6 py-4">
                <span :class="dept.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'" class="rounded-full px-2 py-0.5 text-xs font-medium">
                  {{ dept.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <div v-if="deptEditingId !== dept.id" class="flex items-center justify-end gap-2">
                  <button class="rounded p-1 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200" title="Edit" @click="startDeptEdit(dept)"><Pencil :size="16" /></button>
                  <button
                    class="rounded p-1 transition-colors"
                    :class="dept.is_active ? 'text-green-600 hover:text-green-800 dark:text-green-400' : 'text-amber-500 hover:text-amber-700 dark:text-amber-400'"
                    :title="dept.is_active ? 'Deactivate' : 'Activate'"
                    @click="toggleDept(dept)"
                  >
                    <ToggleRight v-if="dept.is_active" :size="20" />
                    <ToggleLeft v-else :size="20" />
                  </button>
                  <button v-if="dept.employees_count === 0" class="rounded p-1 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-200" title="Delete" @click="confirmDeleteDept(dept)"><Trash2 :size="16" /></button>
                </div>
                <div v-else class="flex items-center justify-end gap-2">
                  <button class="rounded p-1 text-green-600 hover:text-green-800 dark:text-green-400" title="Save" @click="submitDeptEdit(dept.id)"><CheckCircle :size="16" /></button>
                  <button class="rounded p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400" title="Cancel" @click="cancelDeptEdit"><X :size="16" /></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <!-- Dept Pagination -->
        <div v-if="props.departments.length > 0" class="flex items-center border-t border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
          <div class="flex w-1/3 items-center gap-2">
            <span class="text-sm text-gray-600 dark:text-gray-400">Per page:</span>
            <select v-model="deptPerPage" @change="changeDeptPerPage" class="rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
              <option v-for="n in [5, 10, 25, 50]" :key="n" :value="n">{{ n }}</option>
            </select>
          </div>
          <div class="flex w-1/3 justify-center gap-2">
            <button @click="deptPage--" :disabled="deptPage <= 1" class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700" v-html="'&laquo;'" />
            <button v-for="p in deptTotalPages" :key="p" @click="deptPage = p" :class="['rounded-lg px-3 py-1 text-sm font-medium transition-colors', deptPage === p ? 'bg-blue-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700']">{{ p }}</button>
            <button @click="deptPage++" :disabled="deptPage >= deptTotalPages" class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700" v-html="'&raquo;'" />
          </div>
          <div class="flex w-1/3 justify-end">
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ deptSearch ? `${filteredDepts.length} of ${props.departments.length}` : props.departments.length }} departments</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════ POSITIONS TAB ═════════ -->
    <div v-else-if="activeSubTab === 'positions'">
      <!-- Header + Add button -->
      <div class="mb-4 flex items-center justify-between">
        <div>
          <h3 class="text-base font-semibold text-gray-900 dark:text-white">Positions</h3>
          <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Define positions and their reporting hierarchy within departments.</p>
        </div>
        <button
          class="flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
          @click="showAddPosForm = true"
        >
          <Plus :size="16" />
          Add Position
        </button>
      </div>
      <!-- Search -->
      <div class="mb-3 flex justify-end">
        <input v-model="posSearch" type="text" placeholder="Search..." class="w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400" />
      </div>

      <!-- Add Form -->
      <div v-if="showAddPosForm" class="mb-4 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
        <h4 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">New Position</h4>
        <form class="grid grid-cols-1 gap-4 md:grid-cols-3" @submit.prevent="submitAddPos">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Position Name</label>
            <input v-model="addPosForm.position_name" type="text" placeholder="e.g. Senior Developer" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required />
            <p v-if="addPosForm.errors.position_name" class="mt-1 text-xs text-red-600">{{ addPosForm.errors.position_name }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Department</label>
            <select v-model="addPosForm.department_id" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
              <option :value="null" disabled>Select department</option>
              <option v-for="dept in activeDepartments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
            </select>
            <p v-if="addPosForm.errors.department_id" class="mt-1 text-xs text-red-600">{{ addPosForm.errors.department_id }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Reports To <span class="text-gray-400">(optional)</span></label>
            <select v-model="addPosForm.reports_to_position_id" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
              <option :value="null">None (top-level)</option>
              <option v-for="pos in activePositions" :key="pos.id" :value="pos.id">{{ pos.position_name }} ({{ pos.department.name }})</option>
            </select>
            <p v-if="addPosForm.errors.reports_to_position_id" class="mt-1 text-xs text-red-600">{{ addPosForm.errors.reports_to_position_id }}</p>
          </div>
          <div class="flex justify-end gap-3 md:col-span-3">
            <button type="button" class="px-4 py-2 font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" @click="cancelAddPos">Cancel</button>
            <button type="submit" :disabled="addPosForm.processing" class="rounded-lg bg-blue-600 px-6 py-2 font-medium text-white hover:bg-blue-700 disabled:opacity-50">
              {{ addPosForm.processing ? 'Saving...' : 'Save Position' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Positions Table -->
      <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <table class="w-full text-sm">
          <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Position</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Department</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Reports To</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Employees</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-if="props.positions.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">No positions configured yet.</td>
            </tr>
            <tr v-else-if="filteredPositions.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">No positions match your search.</td>
            </tr>
            <tr v-for="pos in paginatedPositions" :key="pos.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
              <!-- Position Name -->
              <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                <span v-if="posEditingId !== pos.id">{{ pos.position_name }}</span>
                <div v-else>
                  <input v-model="editPosForm.position_name" class="w-full rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                  <p v-if="editPosForm.errors.position_name" class="mt-0.5 text-xs text-red-600">{{ editPosForm.errors.position_name }}</p>
                </div>
              </td>
              <!-- Department -->
              <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                <span v-if="posEditingId !== pos.id">{{ pos.department.name }}</span>
                <select v-else v-model="editPosForm.department_id" class="w-full rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                  <option v-for="dept in activeDepartments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                </select>
              </td>
              <!-- Reports To -->
              <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                <span v-if="posEditingId !== pos.id">{{ pos.reports_to?.position_name || '—' }}</span>
                <div v-else>
                  <select v-model="editPosForm.reports_to_position_id" class="w-full rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option :value="null">None (top-level)</option>
                    <option v-for="p in activePositions.filter(p => p.id !== pos.id)" :key="p.id" :value="p.id">{{ p.position_name }} ({{ p.department.name }})</option>
                  </select>
                  <p v-if="editPosForm.errors.reports_to_position_id" class="mt-0.5 text-xs text-red-600">{{ editPosForm.errors.reports_to_position_id }}</p>
                </div>
              </td>
              <!-- Employee Count -->
              <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ pos.employees_count }}</td>
              <!-- Status -->
              <td class="px-6 py-4">
                <span :class="pos.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'" class="rounded-full px-2 py-0.5 text-xs font-medium">
                  {{ pos.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <!-- Actions -->
              <td class="px-6 py-4 text-right">
                <div v-if="posEditingId !== pos.id" class="flex items-center justify-end gap-2">
                  <button class="rounded p-1 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200" title="Edit" @click="startPosEdit(pos)"><Pencil :size="16" /></button>
                  <button
                    class="rounded p-1 transition-colors"
                    :class="pos.is_active ? 'text-green-600 hover:text-green-800 dark:text-green-400' : 'text-amber-500 hover:text-amber-700 dark:text-amber-400'"
                    :title="pos.is_active ? 'Deactivate' : 'Activate'"
                    @click="togglePos(pos)"
                  >
                    <ToggleRight v-if="pos.is_active" :size="20" />
                    <ToggleLeft v-else :size="20" />
                  </button>
                  <button v-if="pos.employees_count === 0" class="rounded p-1 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-200" title="Delete" @click="confirmDeletePos(pos)"><Trash2 :size="16" /></button>
                </div>
                <div v-else class="flex items-center justify-end gap-2">
                  <button class="rounded p-1 text-green-600 hover:text-green-800 dark:text-green-400" title="Save" @click="submitPosEdit(pos.id)"><CheckCircle :size="16" /></button>
                  <button class="rounded p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400" title="Cancel" @click="cancelPosEdit"><X :size="16" /></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <!-- Positions Pagination -->
        <div v-if="props.positions.length > 0" class="flex items-center border-t border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
          <div class="flex w-1/3 items-center gap-2">
            <span class="text-sm text-gray-600 dark:text-gray-400">Per page:</span>
            <select v-model="posPerPage" @change="changePosPerPage" class="rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
              <option v-for="n in [5, 10, 25, 50]" :key="n" :value="n">{{ n }}</option>
            </select>
          </div>
          <div class="flex w-1/3 justify-center gap-2">
            <button @click="posPage--" :disabled="posPage <= 1" class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700" v-html="'&laquo;'" />
            <button v-for="p in posTotalPages" :key="p" @click="posPage = p" :class="['rounded-lg px-3 py-1 text-sm font-medium transition-colors', posPage === p ? 'bg-blue-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700']">{{ p }}</button>
            <button @click="posPage++" :disabled="posPage >= posTotalPages" class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700" v-html="'&raquo;'" />
          </div>
          <div class="flex w-1/3 justify-end">
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ posSearch ? `${filteredPositions.length} of ${props.positions.length}` : props.positions.length }} positions</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Department Confirmation Modal -->
    <div v-if="deletingDept" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
      <div class="mx-4 w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
        <div class="flex items-start gap-4">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
            <AlertTriangle class="h-5 w-5 text-red-600 dark:text-red-400" />
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Delete Department</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
              Are you sure you want to delete <strong>{{ deletingDept.name }}</strong>? This action cannot be undone.
            </p>
          </div>
        </div>
        <div class="mt-6 flex justify-end gap-3">
          <button class="px-4 py-2 font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" @click="deletingDept = null">Cancel</button>
          <button :disabled="destroyDeptForm.processing" class="rounded-lg bg-red-600 px-4 py-2 font-medium text-white hover:bg-red-700 disabled:opacity-50" @click="submitDeleteDept">
            {{ destroyDeptForm.processing ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Position Confirmation Modal -->
    <div v-if="deletingPos" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
      <div class="mx-4 w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
        <div class="flex items-start gap-4">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
            <AlertTriangle class="h-5 w-5 text-red-600 dark:text-red-400" />
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Delete Position</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
              Are you sure you want to delete <strong>{{ deletingPos.position_name }}</strong>? This action cannot be undone.
            </p>
          </div>
        </div>
        <div class="mt-6 flex justify-end gap-3">
          <button class="px-4 py-2 font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" @click="deletingPos = null">Cancel</button>
          <button :disabled="destroyPosForm.processing" class="rounded-lg bg-red-600 px-4 py-2 font-medium text-white hover:bg-red-700 disabled:opacity-50" @click="submitDeletePos">
            {{ destroyPosForm.processing ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { AlertTriangle, Briefcase, Building2, CheckCircle, Pencil, Plus, ToggleLeft, ToggleRight, Trash2, X } from 'lucide-vue-next'
import Layout from '@/components/Layout.vue'
import {
  store as deptStore,
  update as deptUpdate,
  toggle as deptToggle,
  destroy as deptDestroy,
} from '@/actions/App/Modules/Core/Controllers/DepartmentController'
import {
  store as posStore,
  update as posUpdate,
  toggle as posToggle,
  destroy as posDestroy,
} from '@/actions/App/Modules/Core/Controllers/PositionController'

interface Department {
  id: number
  name: string
  slug: string
  description: string | null
  is_active: boolean
  employees_count: number
}

interface Position {
  id: number
  department_id: number
  position_name: string
  reports_to_position_id: number | null
  is_active: boolean
  employees_count: number
  department: { id: number; name: string }
  reports_to: { id: number; position_name: string } | null
}

const props = defineProps<{
  departments: Department[]
  positions: Position[]
}>()

const page = usePage()
const flashSuccess = computed(() => (page.props.flash as any)?.success ?? null)
const flashError = computed(() => (page.props.flash as any)?.error ?? null)

const activeSubTab = ref<'departments' | 'positions'>('departments')

const activeDepartments = computed(() => props.departments.filter(d => d.is_active))
const activePositions = computed(() => props.positions.filter(p => p.is_active))

// ─── Search & Pagination ───────────────────────────────────────────────────────
const deptSearch = ref('')
const deptPage = ref(1)
const deptPerPage = ref(5)
const filteredDepts = computed(() => {
  const q = deptSearch.value.toLowerCase()
  if (!q) { return props.departments }
  return props.departments.filter(d =>
    d.name.toLowerCase().includes(q) || (d.description ?? '').toLowerCase().includes(q),
  )
})
const deptTotalPages = computed(() => Math.max(1, Math.ceil(filteredDepts.value.length / deptPerPage.value)))
const paginatedDepts = computed(() => {
  const start = (deptPage.value - 1) * deptPerPage.value
  return filteredDepts.value.slice(start, start + deptPerPage.value)
})
function changeDeptPerPage(): void { deptPage.value = 1 }
watch(deptSearch, () => { deptPage.value = 1 })

const posSearch = ref('')
const posPage = ref(1)
const posPerPage = ref(5)
const filteredPositions = computed(() => {
  const q = posSearch.value.toLowerCase()
  if (!q) { return props.positions }
  return props.positions.filter(p =>
    p.position_name.toLowerCase().includes(q) || p.department.name.toLowerCase().includes(q),
  )
})
const posTotalPages = computed(() => Math.max(1, Math.ceil(filteredPositions.value.length / posPerPage.value)))
const paginatedPositions = computed(() => {
  const start = (posPage.value - 1) * posPerPage.value
  return filteredPositions.value.slice(start, start + posPerPage.value)
})
function changePosPerPage(): void { posPage.value = 1 }
watch(posSearch, () => { posPage.value = 1 })

// ─── Departments ───────────────────────────────────────────────────────────────
const showAddDeptForm = ref(false)
const addDeptForm = useForm({ name: '', description: '' })

function cancelAddDept(): void {
  showAddDeptForm.value = false
  addDeptForm.reset()
  addDeptForm.clearErrors()
}

function submitAddDept(): void {
  addDeptForm.post(deptStore.url(), {
    onSuccess: () => {
      showAddDeptForm.value = false
      addDeptForm.reset()
    },
  })
}

const deptEditingId = ref<number | null>(null)
const editDeptForm = useForm({ name: '', description: '' })

function startDeptEdit(dept: Department): void {
  deptEditingId.value = dept.id
  editDeptForm.name = dept.name
  editDeptForm.description = dept.description ?? ''
}

function cancelDeptEdit(): void {
  deptEditingId.value = null
  editDeptForm.clearErrors()
}

function submitDeptEdit(id: number): void {
  editDeptForm.put(deptUpdate.url(id), {
    onSuccess: () => { deptEditingId.value = null },
  })
}

const toggleDeptForm = useForm({})
function toggleDept(dept: Department): void {
  toggleDeptForm.patch(deptToggle.url(dept.id))
}

const deletingDept = ref<Department | null>(null)
const destroyDeptForm = useForm({})

function confirmDeleteDept(dept: Department): void {
  deletingDept.value = dept
}

function submitDeleteDept(): void {
  if (!deletingDept.value) { return }
  destroyDeptForm.delete(deptDestroy.url(deletingDept.value.id), {
    onSuccess: () => { deletingDept.value = null },
  })
}

// ─── Positions ─────────────────────────────────────────────────────────────────
const showAddPosForm = ref(false)
const addPosForm = useForm({
  position_name: '',
  department_id: null as number | null,
  reports_to_position_id: null as number | null,
})

function cancelAddPos(): void {
  showAddPosForm.value = false
  addPosForm.reset()
  addPosForm.clearErrors()
}

function submitAddPos(): void {
  addPosForm.post(posStore.url(), {
    onSuccess: () => {
      showAddPosForm.value = false
      addPosForm.reset()
    },
  })
}

const posEditingId = ref<number | null>(null)
const editPosForm = useForm({
  position_name: '',
  department_id: null as number | null,
  reports_to_position_id: null as number | null,
})

function startPosEdit(pos: Position): void {
  posEditingId.value = pos.id
  editPosForm.position_name = pos.position_name
  editPosForm.department_id = pos.department_id
  editPosForm.reports_to_position_id = pos.reports_to_position_id
}

function cancelPosEdit(): void {
  posEditingId.value = null
  editPosForm.clearErrors()
}

function submitPosEdit(id: number): void {
  editPosForm.put(posUpdate.url(id), {
    onSuccess: () => { posEditingId.value = null },
  })
}

const togglePosForm = useForm({})
function togglePos(pos: Position): void {
  togglePosForm.patch(posToggle.url(pos.id))
}

const deletingPos = ref<Position | null>(null)
const destroyPosForm = useForm({})

function confirmDeletePos(pos: Position): void {
  deletingPos.value = pos
}

function submitDeletePos(): void {
  if (!deletingPos.value) { return }
  destroyPosForm.delete(posDestroy.url(deletingPos.value.id), {
    onSuccess: () => { deletingPos.value = null },
  })
}
</script>
