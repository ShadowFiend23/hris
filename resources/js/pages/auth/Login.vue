<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import AuthBase from '@/layouts/AuthLayout.vue'
import { register } from '@/routes'
import { store } from '@/routes/login'
import { request } from '@/routes/password'
import { Form, Head, Link } from '@inertiajs/vue3'

defineProps<{
    status?: string
    canResetPassword: boolean
    canRegister: boolean
}>()
</script>

<template>
    <AuthBase
        title="Welcome back"
        description="Sign in to your account to continue"
    >
        <Head title="Log in" />

        <div
            v-if="status"
            class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-center text-sm font-medium text-green-700"
        >
            {{ status }}
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="space-y-5"
        >
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="you@company.com"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                    :class="errors.email ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20' : ''"
                />
                <InputError :message="errors.email" class="mt-1" />
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <Link
                        v-if="canResetPassword"
                        :href="request()"
                        :tabindex="5"
                        class="text-sm text-blue-600 hover:text-blue-700"
                    >
                        Forgot password?
                    </Link>
                </div>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                    :class="errors.password ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20' : ''"
                />
                <InputError :message="errors.password" class="mt-1" />
            </div>

            <div class="flex items-center gap-2">
                <input
                    id="remember"
                    type="checkbox"
                    name="remember"
                    :tabindex="3"
                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <label for="remember" class="text-sm text-gray-600">Remember me</label>
            </div>

            <button
                type="submit"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
                class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <span v-if="processing" class="flex items-center justify-center gap-2">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    Signing in...
                </span>
                <span v-else>Sign in</span>
            </button>

            <p v-if="canRegister" class="text-center text-sm text-gray-500">
                Don't have an account?
                <Link :href="register()" :tabindex="5" class="font-medium text-blue-600 hover:text-blue-700">
                    Sign up
                </Link>
            </p>
        </Form>
    </AuthBase>
</template>
