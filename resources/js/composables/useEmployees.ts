import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useApi } from './useApi'

export interface Employee {
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
  company_id: number
  department_id: number
  position_id: number
  department?: { id: number; name: string }
  position?: { id: number; position_name: string }
  company?: { id: number; name: string }
}

export interface EmployeeFilters {
  search?: string
  department_id?: number
  position_id?: number
  employment_status?: string
  is_active?: boolean
  sort_by?: string
  sort_direction?: 'asc' | 'desc'
}

export interface CreateEmployeeData {
  employee_id?: string
  first_name: string
  middle_name?: string
  last_name: string
  email: string
  phone?: string
  date_of_birth?: string
  gender?: string
  address?: string
  city?: string
  province?: string
  postal_code?: string
  date_hired: string
  employment_status?: string
  employment_type?: string
  salary?: number
  bank_account?: string
  tin?: string
  sss_number?: string
  philhealth_number?: string
  pagibig_number?: string
  department_id: number
  position_id: number
}

export function useEmployees() {
  const { request } = useApi()
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  /**
   * Fetch employees with filters (API endpoint)
   */
  const fetchEmployees = async (filters: EmployeeFilters = {}) => {
    isLoading.value = true
    error.value = null

    try {
      const params = new URLSearchParams()
      if (filters.search) params.append('search', filters.search)
      if (filters.department_id) params.append('department_id', String(filters.department_id))
      if (filters.position_id) params.append('position_id', String(filters.position_id))
      if (filters.employment_status) params.append('employment_status', filters.employment_status)
      if (filters.is_active !== undefined) params.append('is_active', String(filters.is_active))

      const response = await request(`/api/core/employees?${params.toString()}`)
      return response
    } catch (err) {
      error.value = 'Failed to fetch employees'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Create a new employee (API endpoint)
   */
  const createEmployee = async (data: CreateEmployeeData) => {
    isLoading.value = true
    error.value = null

    try {
      const response = await request('/api/core/employees', {
        method: 'POST',
        body: JSON.stringify(data),
        headers: {
          'Content-Type': 'application/json',
        },
      })
      return response
    } catch (err) {
      error.value = 'Failed to create employee'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Update an employee (API endpoint)
   */
  const updateEmployee = async (id: number, data: Partial<CreateEmployeeData>) => {
    isLoading.value = true
    error.value = null

    try {
      const response = await request(`/api/core/employees/${id}`, {
        method: 'PUT',
        body: JSON.stringify(data),
        headers: {
          'Content-Type': 'application/json',
        },
      })
      return response
    } catch (err) {
      error.value = 'Failed to update employee'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Delete an employee (API endpoint)
   */
  const deleteEmployee = async (id: number) => {
    isLoading.value = true
    error.value = null

    try {
      await request(`/api/core/employees/${id}`, {
        method: 'DELETE',
      })
      return true
    } catch (err) {
      error.value = 'Failed to delete employee'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Search employees by name or ID
   */
  const searchEmployees = async (query: string) => {
    return fetchEmployees({ search: query })
  }

  /**
   * Format employee status for display
   */
  const formatEmployeeStatus = (status: string): string => {
    const statusMap: Record<string, string> = {
      active: 'Active',
      inactive: 'Inactive',
      resigned: 'Resigned',
      terminated: 'Terminated',
      retired: 'Retired',
    }
    return statusMap[status] || status
  }

  /**
   * Get status color class
   */
  const getStatusColor = (status: string): string => {
    const colorMap: Record<string, string> = {
      active: 'bg-green-100 text-green-700',
      inactive: 'bg-gray-100 text-gray-600',
      resigned: 'bg-yellow-100 text-yellow-700',
      terminated: 'bg-red-100 text-red-700',
      retired: 'bg-blue-100 text-blue-700',
    }
    return colorMap[status] || 'bg-gray-100 text-gray-700'
  }

  /**
   * Format salary with currency
   */
  const formatSalary = (amount: number | null): string => {
    if (amount === null || amount === undefined) return '-'
    return new Intl.NumberFormat('en-PH', {
      style: 'currency',
      currency: 'PHP',
    }).format(amount)
  }

  /**
   * Format employment type for display
   */
  const formatEmploymentType = (type: string | null): string => {
    if (!type) return '-'
    const typeMap: Record<string, string> = {
      full_time: 'Full Time',
      part_time: 'Part Time',
      contract: 'Contract',
      probationary: 'Probationary',
    }
    return typeMap[type] || type
  }

  /**
   * Generate employee ID (client-side preview)
   */
  const generateEmployeeIdPreview = (lastId?: string): string => {
    const prefix = 'EMP'
    if (!lastId) return `${prefix}-0001`

    const match = lastId.match(/EMP-(\d+)/)
    if (match) {
      const nextNum = parseInt(match[1], 10) + 1
      return `${prefix}-${String(nextNum).padStart(4, '0')}`
    }
    return `${prefix}-0001`
  }

  /**
   * Validate TIN format (Philippine)
   */
  const validateTin = (tin: string): boolean => {
    return /^[0-9-]+$/.test(tin)
  }

  /**
   * Validate SSS number format
   */
  const validateSssNumber = (sss: string): boolean => {
    return /^[0-9-]+$/.test(sss)
  }

  /**
   * Validate PhilHealth number format
   */
  const validatePhilhealthNumber = (philhealth: string): boolean => {
    return /^[0-9-]+$/.test(philhealth)
  }

  /**
   * Validate Pag-IBIG number format
   */
  const validatePagibigNumber = (pagibig: string): boolean => {
    return /^[0-9-]+$/.test(pagibig)
  }

  /**
   * Format date for display
   */
  const formatDate = (date: string | null): string => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    })
  }

  /**
   * Get full name
   */
  const getFullName = (employee: Partial<Employee>): string => {
    const parts = [employee.first_name, employee.middle_name, employee.last_name].filter(Boolean)
    return parts.join(' ')
  }

  return {
    isLoading,
    error,
    fetchEmployees,
    createEmployee,
    updateEmployee,
    deleteEmployee,
    searchEmployees,
    formatEmployeeStatus,
    getStatusColor,
    formatSalary,
    formatEmploymentType,
    generateEmployeeIdPreview,
    validateTin,
    validateSssNumber,
    validatePhilhealthNumber,
    validatePagibigNumber,
    formatDate,
    getFullName,
  }
}
