import { ref } from 'vue'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api'

export const useApi = () => {
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const request = async (
    endpoint: string,
    options: RequestInit = {},
  ) => {
    isLoading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_BASE_URL}${endpoint}`, {
        headers: {
          'Content-Type': 'application/json',
          ...options.headers,
        },
        ...options,
      })

      if (!response.ok) {
        throw new Error(`HTTP Error: ${response.status}`)
      }

      const data = await response.json()
      return data
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'An error occurred'
      error.value = errorMessage
      throw err
    } finally {
      isLoading.value = false
    }
  }

  return {
    request,
    isLoading,
    error,
  }
}
