<template>
  <VDatePicker
    v-model="internalDate"
    mode="date"
    :min-date="minDate || undefined"
    :max-date="maxDate || undefined"
    :is-required="required"
    :is-dark="isDark"
  >
    <template #default="{ inputValue, inputEvents }">
      <input
        :value="inputValue"
        v-on="inputEvents"
        :disabled="disabled"
        :placeholder="placeholder || 'Select a date'"
        :class="[
          'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500',
          error
            ? 'border-red-500'
            : 'border-gray-300 dark:border-gray-600',
          'bg-white text-gray-900 placeholder-gray-400',
          'dark:bg-gray-700 dark:text-white dark:placeholder-gray-400',
          disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
        ]"
        autocomplete="off"
        readonly
      />
    </template>
  </VDatePicker>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { DatePicker as VDatePicker } from 'v-calendar'

const props = withDefaults(defineProps<{
  modelValue?: string | null
  disabled?: boolean
  minDate?: string
  maxDate?: string
  required?: boolean
  placeholder?: string
  error?: boolean
}>(), {
  modelValue: null,
  disabled: false,
  required: false,
  error: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  'change': [value: string]
}>()

const isDark = ref(false)
let observer: MutationObserver | null = null

onMounted(() => {
  isDark.value = document.documentElement.classList.contains('dark')
  observer = new MutationObserver(() => {
    isDark.value = document.documentElement.classList.contains('dark')
  })
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
})

onUnmounted(() => {
  observer?.disconnect()
})

const internalDate = computed({
  get(): Date | null {
    if (!props.modelValue) { return null }
    const [year, month, day] = props.modelValue.split('-').map(Number)
    return new Date(year, month - 1, day)
  },
  set(val: Date | null): void {
    const str = val
      ? `${val.getFullYear()}-${String(val.getMonth() + 1).padStart(2, '0')}-${String(val.getDate()).padStart(2, '0')}`
      : ''
    emit('update:modelValue', str)
    emit('change', str)
  },
})
</script>
