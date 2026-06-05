import { ref, computed, reactive } from 'vue';

export interface AttendanceRecord {
    id: number;
    employee_id: number;
    company_id: number;
    date: string;
    clock_in: string | null;
    clock_out: string | null;
    total_hours: number | null;
    break_duration: number;
    status: 'present' | 'late' | 'absent' | 'half_day' | 'on_leave';
    notes: string | null;
    employee?: Employee;
}

export interface Employee {
    id: number;
    first_name: string;
    last_name: string;
    employee_id: string;
    department?: { name: string };
    position?: { position_name: string };
}

export interface LeaveType {
    id: number;
    name: string;
    days_per_year: number;
    is_paid: boolean;
}

export interface LeaveBalance {
    id: number;
    leave_type_id: number;
    year: number;
    total_days: number;
    used_days: number;
    remaining_days: number;
    leave_type: LeaveType;
}

export interface LeaveRequest {
    id: number;
    employee_id: number;
    leave_type_id: number;
    start_date: string;
    end_date: string;
    total_days: number;
    reason: string | null;
    status: 'pending' | 'approved' | 'rejected' | 'cancelled';
    leave_type: LeaveType;
    employee?: Employee;
}

export interface ShiftTemplate {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
    duration_hours: number;
}

export interface EmployeeSchedule {
    id: number;
    employee_id: number;
    date: string;
    shift_template_id: number;
    start_time: string;
    end_time: string;
    status: string;
    shift_template: ShiftTemplate;
}

export interface OvertimeRecord {
    id: number;
    employee_id: number;
    date: string;
    hours: number;
    overtime_type: 'weekday' | 'weekend' | 'holiday';
    status: 'pending' | 'approved' | 'rejected' | 'paid';
    reason: string | null;
}

export interface TimekeepingState {
    attendance: {
        today: AttendanceRecord | null;
        isClockedIn: boolean;
        loading: boolean;
    };
    leave: {
        types: LeaveType[];
        balances: LeaveBalance[];
        loading: boolean;
    };
    schedule: {
        items: EmployeeSchedule[];
        loading: boolean;
    };
}

const state = reactive<TimekeepingState>({
    attendance: {
        today: null,
        isClockedIn: false,
        loading: false,
    },
    leave: {
        types: [],
        balances: [],
        loading: false,
    },
    schedule: {
        items: [],
        loading: false,
    },
});

export function useTimekeeping() {
    const error = ref<string | null>(null);

    // ==========================================
    // Attendance Functions
    // ==========================================

    const fetchTodayAttendance = async () => {
        state.attendance.loading = true;
        error.value = null;

        try {
            const response = await fetch('/api/timekeeping/attendance/today', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) throw new Error('Failed to fetch attendance');

            const data = await response.json();
            state.attendance.today = data.data;
            state.attendance.isClockedIn = data.is_clocked_in;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Unknown error';
        } finally {
            state.attendance.loading = false;
        }
    };

    const clockIn = async () => {
        state.attendance.loading = true;
        error.value = null;

        try {
            const response = await fetch('/api/timekeeping/attendance/clock-in', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.error || 'Failed to clock in');
            }

            const data = await response.json();
            state.attendance.today = data.data;
            state.attendance.isClockedIn = true;
            return data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Unknown error';
            throw e;
        } finally {
            state.attendance.loading = false;
        }
    };

    const clockOut = async () => {
        state.attendance.loading = true;
        error.value = null;

        try {
            const response = await fetch('/api/timekeeping/attendance/clock-out', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.error || 'Failed to clock out');
            }

            const data = await response.json();
            state.attendance.today = data.data;
            state.attendance.isClockedIn = false;
            return data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Unknown error';
            throw e;
        } finally {
            state.attendance.loading = false;
        }
    };

    const recordBreak = async (minutes: number) => {
        state.attendance.loading = true;
        error.value = null;

        try {
            const response = await fetch('/api/timekeeping/attendance/break', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify({ minutes }),
            });

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.error || 'Failed to record break');
            }

            const data = await response.json();
            state.attendance.today = data.data;
            return data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Unknown error';
            throw e;
        } finally {
            state.attendance.loading = false;
        }
    };

    const fetchAttendanceHistory = async (filters: Record<string, any> = {}) => {
        const params = new URLSearchParams(filters);
        const response = await fetch(`/api/timekeeping/attendance/history?${params}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) throw new Error('Failed to fetch attendance history');

        return response.json();
    };

    const fetchAttendanceSummary = async (startDate: string, endDate: string) => {
        const params = new URLSearchParams({ start_date: startDate, end_date: endDate });
        const response = await fetch(`/api/timekeeping/attendance/summary?${params}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) throw new Error('Failed to fetch attendance summary');

        return response.json();
    };

    // ==========================================
    // Leave Functions
    // ==========================================

    const fetchLeaveTypes = async () => {
        state.leave.loading = true;

        try {
            const response = await fetch('/api/timekeeping/leave/types', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) throw new Error('Failed to fetch leave types');

            const data = await response.json();
            state.leave.types = data.data;
        } finally {
            state.leave.loading = false;
        }
    };

    const fetchLeaveBalance = async (year?: number) => {
        state.leave.loading = true;

        try {
            const params = year ? new URLSearchParams({ year: year.toString() }) : '';
            const response = await fetch(`/api/timekeeping/leave/balance?${params}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) throw new Error('Failed to fetch leave balance');

            const data = await response.json();
            state.leave.balances = data.data;
        } finally {
            state.leave.loading = false;
        }
    };

    const submitLeaveRequest = async (data: {
        leave_type_id: number;
        start_date: string;
        end_date: string;
        reason?: string;
    }) => {
        const response = await fetch('/api/timekeeping/leave/request', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify(data),
        });

        if (!response.ok) {
            const result = await response.json();
            throw new Error(result.error || 'Failed to submit leave request');
        }

        return response.json();
    };

    const cancelLeaveRequest = async (requestId: number) => {
        const response = await fetch(`/api/timekeeping/leave/request/${requestId}/cancel`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            const result = await response.json();
            throw new Error(result.error || 'Failed to cancel leave request');
        }

        return response.json();
    };

    const fetchLeaveHistory = async (filters: Record<string, any> = {}) => {
        const params = new URLSearchParams(filters);
        const response = await fetch(`/api/timekeeping/leave/history?${params}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) throw new Error('Failed to fetch leave history');

        return response.json();
    };

    // ==========================================
    // Schedule Functions
    // ==========================================

    const fetchMySchedule = async (startDate?: string, endDate?: string) => {
        state.schedule.loading = true;

        try {
            const params = new URLSearchParams();
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);

            const response = await fetch(`/api/timekeeping/shift/my-schedule?${params}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) throw new Error('Failed to fetch schedule');

            const data = await response.json();
            state.schedule.items = data.data;
        } finally {
            state.schedule.loading = false;
        }
    };

    const fetchWeeklySchedule = async (weekStart?: string) => {
        const params = weekStart ? new URLSearchParams({ week_start: weekStart }) : '';
        const response = await fetch(`/api/timekeeping/shift/weekly?${params}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) throw new Error('Failed to fetch weekly schedule');

        return response.json();
    };

    const requestShiftSwap = async (data: {
        my_schedule_id: number;
        target_schedule_id: number;
        reason?: string;
    }) => {
        const response = await fetch('/api/timekeeping/shift/swap/request', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify(data),
        });

        if (!response.ok) {
            const result = await response.json();
            throw new Error(result.error || 'Failed to request shift swap');
        }

        return response.json();
    };

    // ==========================================
    // Overtime Functions
    // ==========================================

    const submitOvertimeRequest = async (data: {
        date: string;
        hours: number;
        overtime_type?: string;
        reason?: string;
    }) => {
        const response = await fetch('/api/timekeeping/overtime/request', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify(data),
        });

        if (!response.ok) {
            const result = await response.json();
            throw new Error(result.error || 'Failed to submit overtime request');
        }

        return response.json();
    };

    const fetchOvertimeHistory = async (filters: Record<string, any> = {}) => {
        const params = new URLSearchParams(filters);
        const response = await fetch(`/api/timekeeping/overtime/history?${params}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) throw new Error('Failed to fetch overtime history');

        return response.json();
    };

    const fetchOvertimeSummary = async (startDate: string, endDate: string) => {
        const params = new URLSearchParams({ start_date: startDate, end_date: endDate });
        const response = await fetch(`/api/timekeeping/overtime/summary?${params}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) throw new Error('Failed to fetch overtime summary');

        return response.json();
    };

    const cancelOvertimeRequest = async (overtimeId: number) => {
        const response = await fetch(`/api/timekeeping/overtime/${overtimeId}/cancel`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            const result = await response.json();
            throw new Error(result.error || 'Failed to cancel overtime request');
        }

        return response.json();
    };

    // ==========================================
    // Report Functions
    // ==========================================

    const fetchMySummary = async (startDate?: string, endDate?: string) => {
        const params = new URLSearchParams();
        if (startDate) params.append('start_date', startDate);
        if (endDate) params.append('end_date', endDate);

        const response = await fetch(`/api/timekeeping/reports/my-summary?${params}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) throw new Error('Failed to fetch summary');

        return response.json();
    };

    // ==========================================
    // Utility Functions
    // ==========================================

    const getCsrfToken = (): string => {
        const cookie = document.cookie
            .split('; ')
            .find(row => row.startsWith('XSRF-TOKEN='));
        return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
    };

    // Computed properties
    const isClockedIn = computed(() => state.attendance.isClockedIn);
    const todayAttendance = computed(() => state.attendance.today);
    const leaveTypes = computed(() => state.leave.types);
    const leaveBalances = computed(() => state.leave.balances);
    const schedule = computed(() => state.schedule.items);
    const isLoading = computed(() =>
        state.attendance.loading ||
        state.leave.loading ||
        state.schedule.loading
    );

    return {
        // State
        state,
        error,

        // Computed
        isClockedIn,
        todayAttendance,
        leaveTypes,
        leaveBalances,
        schedule,
        isLoading,

        // Attendance
        fetchTodayAttendance,
        clockIn,
        clockOut,
        recordBreak,
        fetchAttendanceHistory,
        fetchAttendanceSummary,

        // Leave
        fetchLeaveTypes,
        fetchLeaveBalance,
        submitLeaveRequest,
        cancelLeaveRequest,
        fetchLeaveHistory,

        // Schedule
        fetchMySchedule,
        fetchWeeklySchedule,
        requestShiftSwap,

        // Overtime
        submitOvertimeRequest,
        fetchOvertimeHistory,
        fetchOvertimeSummary,
        cancelOvertimeRequest,

        // Reports
        fetchMySummary,
    };
}
