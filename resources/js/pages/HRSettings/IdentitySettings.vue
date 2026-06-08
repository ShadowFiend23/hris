<template>
  <Layout>
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">App Settings</h1>
      <p class="mt-1 text-gray-600 dark:text-gray-400">Manage leave types, payroll schedules, and other HR configurations.</p>
    </div>

    <!-- Top-level Tabs -->
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
      <nav class="flex gap-6 overflow-x-auto">
        <Link href="/app-settings/employee-settings" class="border-b-2 border-transparent pb-3 text-sm font-medium whitespace-nowrap text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Employee Settings</Link>
        <Link href="/app-settings/timekeeping-settings" class="border-b-2 border-transparent pb-3 text-sm font-medium whitespace-nowrap text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Timekeeping Settings</Link>
        <Link href="/app-settings/payroll" class="border-b-2 border-transparent pb-3 text-sm font-medium whitespace-nowrap text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Payroll Settings</Link>
        <Link href="/app-settings/allowance-types" class="border-b-2 border-transparent pb-3 text-sm font-medium whitespace-nowrap text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Allowance Settings</Link>
        <Link href="/app-settings/loan-types" class="border-b-2 border-transparent pb-3 text-sm font-medium whitespace-nowrap text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Loan Settings</Link>
        <Link href="/app-settings/holidays" class="border-b-2 border-transparent pb-3 text-sm font-medium whitespace-nowrap text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Holidays</Link>
        <Link href="/app-settings/contribution-settings" class="border-b-2 border-transparent pb-3 text-sm font-medium whitespace-nowrap text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Contribution Settings</Link>
        <Link href="/app-settings/identity-settings" class="border-b-2 border-blue-600 pb-3 text-sm font-medium whitespace-nowrap text-blue-600 dark:border-blue-400 dark:text-blue-400">Identity Settings</Link>
      </nav>
    </div>

    <!-- Section Header -->
    <div class="mb-6">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Identity Settings</h2>
      <p class="mt-1 text-gray-600 dark:text-gray-400">Customize your company's logos and branding across the application.</p>
    </div>

    <div class="space-y-6">
      <!-- Company Name -->
      <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Company Name</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Displayed in the sidebar and throughout the application.</p>
        <form class="mt-4 flex items-center gap-3" @submit.prevent="saveName">
          <input
            v-model="nameForm.name"
            type="text"
            maxlength="100"
            placeholder="e.g. Acme Corporation"
            class="w-72 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
            :class="nameForm.errors.name ? 'border-red-400' : ''"
          />
          <button
            type="submit"
            :disabled="nameForm.processing"
            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 transition-colors"
          >
            Save
          </button>
          <p v-if="nameForm.errors.name" class="text-sm text-red-600">{{ nameForm.errors.name }}</p>
        </form>
      </div>

      <!-- Login Logo -->
      <BrandingCard
        title="Login Page Logo"
        description="Displayed on the login, forgot password, and other auth pages. Recommended size: 200×60px."
        :current-url="props.company.logoLogin"
        type="login-logo"
        @upload="handleUpload"
        @remove="handleRemove"
      />

      <!-- Nav Logo -->
      <BrandingCard
        title="Navigation Logo"
        description="Displayed in the sidebar header. Recommended size: 160×40px."
        :current-url="props.company.logoNav"
        type="nav-logo"
        @upload="handleUpload"
        @remove="handleRemove"
      />

      <!-- Favicon -->
      <BrandingCard
        title="Favicon"
        description="Browser tab icon. Use a square image (ICO, PNG, or SVG). Recommended: 32×32px."
        :current-url="props.company.favicon"
        type="favicon"
        @upload="handleUpload"
        @remove="handleRemove"
      />
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import { defineComponent, h, ref } from 'vue'
import Layout from '@/components/Layout.vue'
import { Upload, X, ImageIcon } from 'lucide-vue-next'

const props = defineProps<{
  company: {
    name: string
    logoLogin: string | null
    logoNav: string | null
    favicon: string | null
  }
}>()

const nameForm = useForm({ name: props.company.name })

function saveName(): void {
  nameForm.patch('/app-settings/identity-settings/name')
}

// Inline BrandingCard component
const BrandingCard = defineComponent({
  props: {
    title: String,
    description: String,
    currentUrl: { type: String as () => string | null, default: null },
    type: String,
  },
  emits: ['upload', 'remove'],
  setup(props, { emit }) {
    const fileInput = ref<HTMLInputElement | null>(null)
    const previewUrl = ref<string | null>(props.currentUrl ?? null)
    const isUploading = ref(false)

    const triggerUpload = () => fileInput.value?.click()

    const onFileChange = (e: Event) => {
      const file = (e.target as HTMLInputElement).files?.[0]
      if (!file) { return }
      previewUrl.value = URL.createObjectURL(file)
      emit('upload', props.type, file)
    }

    const onRemove = () => {
      previewUrl.value = null
      emit('remove', props.type)
    }

    return () =>
      h('div', { class: 'rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800' }, [
        h('div', { class: 'flex items-start justify-between gap-6' }, [
          h('div', { class: 'flex-1' }, [
            h('h3', { class: 'text-base font-semibold text-gray-900 dark:text-white' }, props.title),
            h('p', { class: 'mt-1 text-sm text-gray-500 dark:text-gray-400' }, props.description),
            h('div', { class: 'mt-4 flex items-center gap-3' }, [
              h('input', {
                ref: fileInput,
                type: 'file',
                accept: props.type === 'favicon' ? '.ico,.png,.svg,.gif,.jpg,.jpeg' : '.jpg,.jpeg,.png,.svg,.gif,.webp',
                class: 'hidden',
                onChange: onFileChange,
              }),
              h('button', {
                type: 'button',
                class: 'flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors',
                onClick: triggerUpload,
              }, [h(Upload, { size: 16 }), 'Upload']),
              previewUrl.value
                ? h('button', {
                    type: 'button',
                    class: 'flex items-center gap-2 rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-700 dark:hover:bg-red-900/20 transition-colors',
                    onClick: onRemove,
                  }, [h(X, { size: 16 }), 'Remove'])
                : null,
            ]),
          ]),
          h('div', { class: 'shrink-0' }, [
            previewUrl.value
              ? h('img', {
                  src: previewUrl.value,
                  alt: props.title,
                  class: 'h-16 max-w-48 rounded-lg border border-gray-200 object-contain p-2 dark:border-gray-700',
                })
              : h('div', { class: 'flex h-16 w-32 items-center justify-center rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-600' }, [
                  h(ImageIcon, { size: 24, class: 'text-gray-300 dark:text-gray-600' }),
                ]),
          ]),
        ]),
      ])
  },
})

function handleUpload(type: string, file: File): void {
  const form = useForm({ file })
  form.post(`/app-settings/identity-settings/${type}/upload`, { forceFormData: true })
}

function handleRemove(type: string): void {
  const form = useForm({})
  form.delete(`/app-settings/identity-settings/${type}`)
}
</script>
