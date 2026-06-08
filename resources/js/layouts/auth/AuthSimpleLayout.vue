<script setup lang="ts">
import { computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'

defineProps<{
    title?: string;
    description?: string;
}>()

const page = usePage()
const branding = computed(() => (page.props as any).branding ?? {})
const logoLogin = computed(() => branding.value.logoLogin ?? null)
const favicon = computed(() => branding.value.favicon ?? null)
const companyName = computed(() => branding.value.name || 'HR Core')
</script>

<template>
    <Head v-if="favicon">
        <link rel="icon" :href="favicon" />
    </Head>

    <div class="flex min-h-screen bg-gray-50">
        <!-- Left branding panel -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-blue-600 to-blue-800 flex-col items-center justify-center p-12 text-white">
            <div class="w-full max-w-sm text-center">
                <div v-if="logoLogin" class="mb-8 flex justify-center">
                    <img :src="logoLogin" alt="Company logo" class="h-32 max-w-xs object-contain" />
                </div>
                <div v-else class="mb-8 flex justify-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/20 text-2xl font-bold backdrop-blur-sm">
                        HR
                    </div>
                </div>
                <h1 class="text-3xl font-bold mb-3">{{ companyName }}</h1>
                <p class="text-blue-200 text-lg">Human Resource Information System</p>
                <div class="mt-12 space-y-4 text-left">
                    <div class="flex items-center gap-3 text-blue-100">
                        <div class="w-2 h-2 rounded-full bg-blue-300 shrink-0" />
                        <span class="text-sm">Manage employees &amp; payroll</span>
                    </div>
                    <div class="flex items-center gap-3 text-blue-100">
                        <div class="w-2 h-2 rounded-full bg-blue-300 shrink-0" />
                        <span class="text-sm">Track attendance &amp; timekeeping</span>
                    </div>
                    <div class="flex items-center gap-3 text-blue-100">
                        <div class="w-2 h-2 rounded-full bg-blue-300 shrink-0" />
                        <span class="text-sm">Philippine statutory compliance</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right form panel -->
        <div class="flex flex-1 flex-col items-center justify-center px-6 py-12 lg:px-12">
            <div class="w-full max-w-sm">
                <!-- Mobile logo -->
                <div class="mb-8 flex justify-center lg:hidden">
                    <div v-if="logoLogin">
                        <img :src="logoLogin" alt="Company logo" class="h-12 max-w-44 object-contain" />
                    </div>
                    <div v-else class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-blue-700 text-xl font-bold text-white">
                        HR
                    </div>
                </div>

                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900">{{ title }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ description }}</p>
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>
