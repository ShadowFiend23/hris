<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue'
import InputError from '@/components/InputError.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps<{
    hardwareHash: string
    hostname: string
    licenseStatus: 'active' | 'expired' | 'tampered' | 'wrong_machine' | null
    expiresAt: string | null
    companyName: string | null
}>()

const form = useForm({
    license_key: '',
})

const showHash = ref(false)

function formatExpiry(value: string | null): string {
    if (!value || value === 'lifetime') { return 'Lifetime' }
    return new Date(value).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })
}

const statusMessage = computed(() => {
    switch (props.licenseStatus) {
        case 'expired':
            return {
                type: 'warning',
                text: `License for "${props.companyName}" expired on ${formatExpiry(props.expiresAt)}. Re-activate with a new key.`,
            }
        case 'tampered':
            return { type: 'error', text: 'License file has been tampered with. Please re-activate.' }
        case 'wrong_machine':
            return { type: 'error', text: 'License is bound to a different server. Contact support.' }
        default:
            return null
    }
})

function submit() {
    form.post('/license/activate', {
        onSuccess: () => form.reset(),
    })
}
</script>

<template>
    <AuthLayout
        title="Activate License"
        description="Enter your license key to unlock this HRIS installation"
    >
        <Head title="Activate License" />

        <!-- Status banner for existing-but-invalid license -->
        <div
            v-if="statusMessage"
            :class="[
                'mb-5 rounded-lg border px-4 py-3 text-sm',
                statusMessage.type === 'warning'
                    ? 'border-yellow-200 bg-yellow-50 text-yellow-800'
                    : 'border-red-200 bg-red-50 text-red-700',
            ]"
        >
            {{ statusMessage.text }}
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <!-- License key input -->
            <div>
                <label for="license_key" class="block text-sm font-medium text-gray-700 mb-1">
                    License Key
                </label>
                <input
                    id="license_key"
                    v-model="form.license_key"
                    type="text"
                    autofocus
                    autocomplete="off"
                    placeholder="XXXX-XXXX-XXXX-XXXX"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 font-mono text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                    :class="form.errors.license_key ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20' : ''"
                    :disabled="form.processing"
                />
                <InputError :message="form.errors.license_key" class="mt-1" />
            </div>

            <!-- Submit -->
            <button
                type="submit"
                :disabled="form.processing || !form.license_key"
                class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <span v-if="form.processing" class="flex items-center justify-center gap-2">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    Activating...
                </span>
                <span v-else>Activate License</span>
            </button>
        </form>

        <!-- Hardware info (collapsible — useful for giving to the vendor) -->
        <div class="mt-6 border-t border-gray-200 pt-4">
            <button
                type="button"
                class="flex w-full items-center justify-between text-xs text-gray-500 hover:text-gray-700"
                @click="showHash = !showHash"
            >
                <span>Server Hardware Info</span>
                <svg
                    :class="['h-4 w-4 transition-transform', showHash ? 'rotate-180' : '']"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div v-if="showHash" class="mt-3 space-y-2 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600">
                <div class="flex items-start gap-2">
                    <span class="w-24 shrink-0 font-medium text-gray-500">Hostname</span>
                    <span class="font-mono break-all">{{ hostname }}</span>
                </div>
                <div class="flex items-start gap-2">
                    <span class="w-24 shrink-0 font-medium text-gray-500">Hardware ID</span>
                    <span class="font-mono break-all">{{ hardwareHash }}</span>
                </div>
                <p class="mt-1 text-gray-400">
                    Share this with your vendor if they need to register this server.
                </p>
            </div>
        </div>
    </AuthLayout>
</template>
