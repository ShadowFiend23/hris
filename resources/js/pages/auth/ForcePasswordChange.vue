<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import AuthBase from '@/layouts/AuthLayout.vue'
import { Form, Head } from '@inertiajs/vue3'
</script>

<template>
    <AuthBase title="Set Your New Password" description="You must set a new password before continuing.">
        <Head title="Set New Password" />

        <Form
            action="/change-password"
            method="put"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="space-y-5"
        >
            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    New Password
                </label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="new-password"
                    placeholder="••••••••"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                    :class="errors.password ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20' : ''"
                />
                <InputError :message="errors.password" class="mt-1" />
            </div>

            <div>
                <label
                    for="password_confirmation"
                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                    Confirm New Password
                </label>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    :tabindex="2"
                    autocomplete="new-password"
                    placeholder="••••••••"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                    :class="errors.password_confirmation ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20' : ''"
                />
                <InputError :message="errors.password_confirmation" class="mt-1" />
            </div>

            <button
                type="submit"
                :tabindex="3"
                :disabled="processing"
                class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <span v-if="processing" class="flex items-center justify-center gap-2">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                        />
                    </svg>
                    Saving...
                </span>
                <span v-else>Set New Password</span>
            </button>
        </Form>
    </AuthBase>
</template>
