<template>
  <Layout>
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Help & Documentation</h1>
      <p class="mt-1 text-gray-600 dark:text-gray-400">Learn how to use the HR Core Management System.</p>
    </div>

    <!-- Role badge -->
    <div class="mb-6 flex items-center gap-2">
      <span class="text-sm text-gray-500 dark:text-gray-400">You are viewing docs for:</span>
      <span v-if="isAdmin" class="rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">Admin</span>
      <span v-else-if="isManager" class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">Manager</span>
      <span v-else class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700 dark:bg-green-900/40 dark:text-green-300">Employee</span>
    </div>

    <!-- Tab navigation -->
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
      <nav class="flex gap-1">
        <button
          v-for="tab in visibleTabs"
          :key="tab.id"
          type="button"
          class="px-4 py-2.5 text-sm font-medium transition-colors border-b-2"
          :class="activeTab === tab.id
            ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
            : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
          @click="activeTab = tab.id"
        >
          <component :is="tab.icon" :size="14" class="mr-1.5 inline" />
          {{ tab.label }}
        </button>
      </nav>
    </div>

    <!-- ══════════════════ EMPLOYEE DOCS ══════════════════ -->
    <div v-if="activeTab === 'employee'" class="space-y-6">
      <DocSection title="Dashboard" icon="BarChart3">
        <p>The Dashboard is your home screen. It shows a quick summary of your attendance today, any pending requests, and recent activity.</p>
        <DocTip>Navigate to the Dashboard by clicking <strong>Dashboard</strong> in the left sidebar.</DocTip>
      </DocSection>

      <DocSection title="Viewing Your Attendance (DTR)" icon="Clock">
        <p>Your Daily Time Record (DTR) shows your clock-in and clock-out times for each day, along with total hours worked.</p>
        <ol class="mt-3 space-y-1.5 text-sm">
          <li><span class="step">1</span> Click <strong>Timekeeping</strong> in the sidebar.</li>
          <li><span class="step">2</span> Go to the <strong>Attendance</strong> tab.</li>
          <li><span class="step">3</span> Use the date range filter to view specific periods.</li>
          <li><span class="step">4</span> Click <strong>Download DTR</strong> to export your record as a PDF.</li>
        </ol>
      </DocSection>

      <DocSection title="Filing a Leave Request" icon="CalendarDays">
        <p>Submit a leave request when you need time off. Your request will go through the approval chain configured by your admin.</p>
        <ol class="mt-3 space-y-1.5 text-sm">
          <li><span class="step">1</span> Click <strong>Timekeeping</strong> in the sidebar.</li>
          <li><span class="step">2</span> Go to the <strong>Leave Management</strong> tab.</li>
          <li><span class="step">3</span> Click <strong>File Leave</strong>.</li>
          <li><span class="step">4</span> Select the leave type, start date, end date, and provide a reason.</li>
          <li><span class="step">5</span> Submit — your request will be sent to your approver.</li>
        </ol>
        <DocTip>Check your remaining leave balance on the same Leave Management tab before filing.</DocTip>
      </DocSection>

      <DocSection title="Filing an Overtime (OT) Request" icon="Clock4">
        <p>If you worked beyond your regular hours, file an OT request to have it recorded and compensated.</p>
        <ol class="mt-3 space-y-1.5 text-sm">
          <li><span class="step">1</span> Click <strong>Timekeeping</strong> in the sidebar.</li>
          <li><span class="step">2</span> Go to the <strong>Overtime</strong> tab.</li>
          <li><span class="step">3</span> Click <strong>File OT Request</strong>.</li>
          <li><span class="step">4</span> Select the date, OT type, hours, and reason.</li>
          <li><span class="step">5</span> Submit — your approver will be notified.</li>
        </ol>
      </DocSection>

      <DocSection title="Requesting a Schedule Change" icon="CalendarRange">
        <p>If you need to work on a different shift for a specific day, file a one-day schedule change request.</p>
        <ol class="mt-3 space-y-1.5 text-sm">
          <li><span class="step">1</span> Click <strong>Timekeeping</strong> in the sidebar.</li>
          <li><span class="step">2</span> Go to the <strong>Schedule Change</strong> tab.</li>
          <li><span class="step">3</span> Select the date (must be today or future).</li>
          <li><span class="step">4</span> Choose the shift template you want to work on that day.</li>
          <li><span class="step">5</span> Provide a reason and submit.</li>
        </ol>
        <DocTip>You can only have one pending schedule change per date. Cancel an existing pending request first if you need to change it.</DocTip>
      </DocSection>

      <DocSection v-if="swapEnabled" title="Requesting a Shift Swap" icon="ArrowLeftRight">
        <p>If you need to swap your shift with a colleague for a specific day, you can file a shift swap request — provided your shift has swap enabled.</p>
        <ol class="mt-3 space-y-1.5 text-sm">
          <li><span class="step">1</span> Click <strong>Timekeeping</strong> in the sidebar.</li>
          <li><span class="step">2</span> Go to the <strong>Shift Scheduling</strong> tab.</li>
          <li><span class="step">3</span> Click <strong>Request Shift Swap</strong>.</li>
          <li><span class="step">4</span> Select the date and the colleague you want to swap with.</li>
          <li><span class="step">5</span> Submit — both your manager and the colleague will be notified.</li>
        </ol>
        <DocTip>Shift swap is only available if your assigned shift template has swap enabled by your admin.</DocTip>
      </DocSection>

      <DocSection title="Viewing Your Payslips" icon="FileText">
        <p>Access your payslips to see your earnings, deductions, and net pay for each payroll period.</p>
        <ol class="mt-3 space-y-1.5 text-sm">
          <li><span class="step">1</span> Click <strong>My Payslips</strong> in the sidebar.</li>
          <li><span class="step">2</span> Click on any period to view the full payslip breakdown.</li>
          <li><span class="step">3</span> Download the payslip PDF using the download button.</li>
        </ol>
      </DocSection>

      <DocSection title="Viewing Your Loans" icon="CreditCard">
        <p>Track your company loans, monthly amortization, and remaining balance.</p>
        <ol class="mt-3 space-y-1.5 text-sm">
          <li><span class="step">1</span> Click <strong>Loans</strong> in the sidebar.</li>
          <li><span class="step">2</span> Your active and completed loans are listed with the balance and payment history.</li>
        </ol>
      </DocSection>

      <DocSection title="Updating Your Profile" icon="User">
        <p>Keep your account information up to date.</p>
        <ol class="mt-3 space-y-1.5 text-sm">
          <li><span class="step">1</span> Click your avatar (initials) in the top-right corner.</li>
          <li><span class="step">2</span> Click <strong>My Profile</strong>.</li>
          <li><span class="step">3</span> Update your name, email, or password.</li>
        </ol>
      </DocSection>
    </div>

    <!-- ══════════════════ MANAGER DOCS ══════════════════ -->
    <div v-else-if="activeTab === 'manager'" class="space-y-6">
      <DocSection title="Approving Leave Requests" icon="CalendarDays">
        <p>As a manager, you can approve or reject leave requests filed by your team.</p>
        <ol class="mt-3 space-y-1.5 text-sm">
          <li><span class="step">1</span> Click <strong>Timekeeping</strong> in the sidebar.</li>
          <li><span class="step">2</span> Go to the <strong>Requests</strong> tab.</li>
          <li><span class="step">3</span> Select the <strong>Leave</strong> sub-tab to see all pending leave requests.</li>
          <li><span class="step">4</span> Click <strong>Approve</strong> to approve, or <strong>Reject</strong> and provide a reason to reject.</li>
        </ol>
        <DocTip>The number badge on the button shows how many requests are waiting for your action.</DocTip>
      </DocSection>

      <DocSection title="Approving Overtime Requests" icon="Clock4">
        <p>Review and act on overtime requests from your team.</p>
        <ol class="mt-3 space-y-1.5 text-sm">
          <li><span class="step">1</span> Click <strong>Timekeeping</strong> → <strong>Requests</strong>.</li>
          <li><span class="step">2</span> Select the <strong>Overtime</strong> sub-tab.</li>
          <li><span class="step">3</span> Approve or reject each request.</li>
        </ol>
      </DocSection>

      <DocSection title="Approving Schedule Change Requests" icon="CalendarRange">
        <p>Review one-day shift change requests from your team. Approving a request automatically updates the employee's schedule for that date.</p>
        <ol class="mt-3 space-y-1.5 text-sm">
          <li><span class="step">1</span> Click <strong>Timekeeping</strong> → <strong>Requests</strong>.</li>
          <li><span class="step">2</span> Select the <strong>Schedule Change</strong> sub-tab.</li>
          <li><span class="step">3</span> Review the requested date, requested shift, and reason.</li>
          <li><span class="step">4</span> Click <strong>Approve</strong> — the employee's schedule is updated automatically.</li>
        </ol>
      </DocSection>

      <DocSection title="Viewing Team Attendance" icon="Clock">
        <p>Monitor your team's daily attendance and DTR records.</p>
        <ol class="mt-3 space-y-1.5 text-sm">
          <li><span class="step">1</span> Click <strong>Timekeeping</strong> → <strong>Attendance</strong>.</li>
          <li><span class="step">2</span> Filter by employee or date range to view specific records.</li>
          <li><span class="step">3</span> Download individual DTR reports from the DTR tab.</li>
        </ol>
      </DocSection>

      <DocSection title="Viewing Timekeeping Reports" icon="BarChart3">
        <p>Access summary reports for leave usage, overtime totals, and attendance.</p>
        <ol class="mt-3 space-y-1.5 text-sm">
          <li><span class="step">1</span> Click <strong>Timekeeping</strong> → <strong>Reports</strong>.</li>
          <li><span class="step">2</span> Filter by period or employee to narrow results.</li>
        </ol>
      </DocSection>
    </div>

    <!-- ══════════════════ ADMIN DOCS ══════════════════ -->
    <div v-else-if="activeTab === 'admin'" class="space-y-6">
      <DocSection title="Managing Employees" icon="Users">
        <p>Add, edit, and manage employee records including their department, position, salary, and employment details.</p>
        <ol class="mt-3 space-y-1.5 text-sm">
          <li><span class="step">1</span> Click <strong>Employees</strong> in the sidebar.</li>
          <li><span class="step">2</span> Click <strong>Add Employee</strong> to create a new record.</li>
          <li><span class="step">3</span> Click on an employee row to view/edit their profile, assign a shift, set salary, and manage their modules.</li>
        </ol>
        <DocTip>Deactivating an employee removes their access but preserves all historical records.</DocTip>
      </DocSection>

      <DocSection title="HR Settings — Timekeeping" icon="Settings">
        <p>Configure leave types, approval chains, overtime settings, and shift templates.</p>
        <ol class="mt-3 space-y-1.5 text-sm">
          <li><span class="step">1</span> Click <strong>App Settings</strong> in the sidebar.</li>
          <li><span class="step">2</span> Open the <strong>Timekeeping Settings</strong> tab.</li>
        </ol>
        <ul class="mt-3 space-y-2 text-sm">
          <li><strong>Leave sub-tab</strong> — Toggle leave on/off, set the approval chain (up to 3 steps), and manage leave types (Vacation, Sick, etc.).</li>
          <li><strong>Overtime sub-tab</strong> — Toggle OT on/off and configure who approves OT requests.</li>
          <li><strong>Shift Templates sub-tab</strong> — Define work shifts with start/end times and break windows. Configure the schedule change approval chain here. Toggle shift swap eligibility per template.</li>
        </ul>
      </DocSection>

      <DocSection title="HR Settings — Payroll" icon="PhilippinePeso">
        <p>Configure payroll periods, allowances, loan types, and contribution brackets.</p>
        <ul class="mt-3 space-y-2 text-sm">
          <li><strong>Payroll Settings</strong> (App Settings → Payroll Settings) — Set the payroll period type (semi-monthly, weekly, etc.), working days, and overtime multipliers.</li>
          <li><strong>Allowance Types</strong> (App Settings → Allowance Settings) — Define recurring allowances (rice, transport, etc.).</li>
          <li><strong>Loan Types</strong> (App Settings → Loan Settings) — Configure loan products with interest rates and terms.</li>
          <li><strong>Contribution Settings</strong> (App Settings → Contribution Settings) — Update SSS, PhilHealth, and PagIBIG bracket tables.</li>
          <li><strong>Holidays</strong> (App Settings → Holidays) — Manage official and special holidays that affect payroll computation.</li>
        </ul>
      </DocSection>

      <DocSection title="Running Payroll" icon="FileText">
        <p>Generate payslips for a payroll period.</p>
        <ol class="mt-3 space-y-1.5 text-sm">
          <li><span class="step">1</span> Click <strong>Payroll</strong> in the sidebar.</li>
          <li><span class="step">2</span> Click <strong>New Period</strong> to create a payroll period (set start/end dates).</li>
          <li><span class="step">3</span> Once the period is in <em>draft</em> status, click <strong>Process</strong> — the system will compute attendance, OT, deductions (SSS, PhilHealth, PagIBIG, withholding tax), and generate payslips.</li>
          <li><span class="step">4</span> Review individual payslips. Make manual adjustments if needed.</li>
          <li><span class="step">5</span> Click <strong>Finalize</strong> to lock the period. Employees can then view their payslips.</li>
        </ol>
        <DocTip>Payroll periods follow the lifecycle: <strong>Draft → Processing → Finalized → Closed</strong>. A finalized period cannot be edited.</DocTip>
      </DocSection>

      <DocSection title="Employee Settings" icon="Building2">
        <p>Manage departments and positions used across the system.</p>
        <ol class="mt-3 space-y-1.5 text-sm">
          <li><span class="step">1</span> Go to <strong>App Settings → Employee Settings</strong>.</li>
          <li><span class="step">2</span> Add, edit, or deactivate departments and positions.</li>
        </ol>
      </DocSection>

      <DocSection title="Managing Licenses" icon="Lock">
        <p>Control which modules are active for your company.</p>
        <ol class="mt-3 space-y-1.5 text-sm">
          <li><span class="step">1</span> Click <strong>Licenses</strong> in the sidebar.</li>
          <li><span class="step">2</span> Activate or deactivate modules (Core HRIS, Timekeeping, Payroll).</li>
        </ol>
        <DocTip>Deactivating a module hides it from all users but does not delete any data.</DocTip>
      </DocSection>

      <DocSection title="Payroll Lifecycle Reference" icon="BarChart3">
        <div class="mt-3 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
              <tr>
                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-400">Status</th>
                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-400">What it means</th>
                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-400">Next action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr><td class="px-4 py-2"><span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">Draft</span></td><td class="px-4 py-2 text-gray-600 dark:text-gray-400">Period created, not yet computed</td><td class="px-4 py-2 text-gray-600 dark:text-gray-400">Click Process</td></tr>
              <tr><td class="px-4 py-2"><span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">Processing</span></td><td class="px-4 py-2 text-gray-600 dark:text-gray-400">Payslips generated, open for review</td><td class="px-4 py-2 text-gray-600 dark:text-gray-400">Review then Finalize</td></tr>
              <tr><td class="px-4 py-2"><span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/40 dark:text-green-300">Finalized</span></td><td class="px-4 py-2 text-gray-600 dark:text-gray-400">Locked, visible to employees</td><td class="px-4 py-2 text-gray-600 dark:text-gray-400">Close when disbursed</td></tr>
              <tr><td class="px-4 py-2"><span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-700 dark:text-gray-500">Closed</span></td><td class="px-4 py-2 text-gray-600 dark:text-gray-400">Fully archived</td><td class="px-4 py-2 text-gray-600 dark:text-gray-400">—</td></tr>
            </tbody>
          </table>
        </div>
      </DocSection>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { ArrowLeftRight, BarChart3, Building2, CalendarDays, CalendarRange, Clock, Clock4, CreditCard, FileText, Lock, PhilippinePeso, Settings, User, Users } from 'lucide-vue-next'
import Layout from '@/components/Layout.vue'
import DocSection from '@/components/ui/DocSection.vue'
import DocTip from '@/components/ui/DocTip.vue'

const page = usePage()
const isAdmin = computed(() => (page.props.auth as any).isAdmin)
const isManager = computed(() => (page.props.auth as any).isManager)
const swapEnabled = computed(() => !!(page.props as any).swapEnabled)

type TabId = 'employee' | 'manager' | 'admin'

interface Tab {
  id: TabId
  label: string
  icon: unknown
}

const allTabs: Tab[] = [
  { id: 'employee', label: 'Employee Guide', icon: User },
  { id: 'manager', label: 'Manager Guide', icon: Users },
  { id: 'admin', label: 'Admin Guide', icon: Settings },
]

const visibleTabs = computed<Tab[]>(() => {
  if (isAdmin.value) return allTabs
  if (isManager.value) return allTabs.filter(t => t.id !== 'admin')
  return allTabs.filter(t => t.id === 'employee')
})

const activeTab = ref<TabId>('employee')
</script>
