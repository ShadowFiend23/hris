<script setup lang="ts">
import Layout from '@/components/Layout.vue';
import { Link } from '@inertiajs/vue3';
import { AlertTriangle, CheckCircle2, Clock, Cpu, Key, MonitorCheck, XCircle } from 'lucide-vue-next';

const props = defineProps<{
    licenseStatus: 'active' | 'expired' | 'tampered' | 'wrong_machine' | null;
    companyName: string | null;
    licenseKey: string | null;
    issuedAt: string | null;
    expiresAt: string | null;
    hardwareHash: string;
    hostname: string;
}>();

function formatDate(value: string | null): string {
    if (!value) { return '—'; }
    if (value === 'lifetime') { return 'Lifetime'; }
    return new Date(value).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

function daysRemaining(expiresAt: string | null): number | null {
    if (!expiresAt || expiresAt === 'lifetime') { return null; }
    return Math.ceil((new Date(expiresAt).getTime() - Date.now()) / 86_400_000);
}

const days = daysRemaining(props.expiresAt);

const statusConfig = {
    active: {
        icon: CheckCircle2,
        label: 'Active',
        iconClass: 'text-green-500',
        badgeClass: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
        bannerClass: 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800',
        message: 'Your license is valid and all features are unlocked.',
    },
    expired: {
        icon: Clock,
        label: 'Expired',
        iconClass: 'text-red-500',
        badgeClass: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
        bannerClass: 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800',
        message: 'This license has expired. Please contact your vendor to renew.',
    },
    tampered: {
        icon: XCircle,
        label: 'Tampered',
        iconClass: 'text-red-500',
        badgeClass: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
        bannerClass: 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800',
        message: 'The license file has been modified and is no longer valid.',
    },
    wrong_machine: {
        icon: AlertTriangle,
        label: 'Wrong Machine',
        iconClass: 'text-orange-500',
        badgeClass: 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
        bannerClass: 'bg-orange-50 border-orange-200 dark:bg-orange-900/20 dark:border-orange-800',
        message: 'This license is bound to a different server. Re-activate on the correct machine.',
    },
} as const;

const config = props.licenseStatus ? statusConfig[props.licenseStatus] : null;
</script>

<template>
    <Layout>
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">License</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Application license status and hardware binding</p>
        </div>

        <!-- No license file -->
        <div
            v-if="!licenseStatus"
            class="rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center dark:border-gray-700 dark:bg-gray-800"
        >
            <Key :size="40" class="mx-auto mb-4 text-gray-400" />
            <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300">No License Found</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                This application has not been activated yet.
            </p>
            <Link
                href="/license/activate"
                class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700"
            >
                Activate License
            </Link>
        </div>

        <template v-else>
            <!-- Status Banner -->
            <div
                :class="['mb-6 flex items-start gap-4 rounded-xl border p-5', config!.bannerClass]"
            >
                <component :is="config!.icon" :size="24" :class="['mt-0.5 shrink-0', config!.iconClass]" />
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="font-semibold text-gray-900 dark:text-white">License Status</span>
                        <span :class="['rounded-full px-2.5 py-0.5 text-xs font-semibold', config!.badgeClass]">
                            {{ config!.label }}
                        </span>
                        <span
                            v-if="licenseStatus === 'active' && days !== null && days <= 30"
                            class="rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300"
                        >
                            Expires in {{ days }} day{{ days === 1 ? '' : 's' }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ config!.message }}</p>
                </div>
                <Link
                    href="/license/activate"
                    class="shrink-0 rounded-lg border border-current px-3 py-1.5 text-xs font-medium opacity-70 hover:opacity-100"
                >
                    Re-activate
                </Link>
            </div>

            <!-- Cards -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- License Info -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-4 flex items-center gap-2 text-gray-500 dark:text-gray-400">
                        <Key :size="18" />
                        <span class="text-sm font-medium uppercase tracking-wide">License</span>
                    </div>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-xs text-gray-400 dark:text-gray-500">Company</dt>
                            <dd class="mt-0.5 font-semibold text-gray-900 dark:text-white">
                                {{ companyName ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 dark:text-gray-500">License Key</dt>
                            <dd class="mt-0.5 font-mono text-sm text-gray-700 dark:text-gray-300">
                                {{ licenseKey ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 dark:text-gray-500">Issued</dt>
                            <dd class="mt-0.5 text-sm text-gray-700 dark:text-gray-300">
                                {{ formatDate(issuedAt) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 dark:text-gray-500">Expires</dt>
                            <dd
                                :class="[
                                    'mt-0.5 text-sm font-medium',
                                    licenseStatus === 'expired'
                                        ? 'text-red-600 dark:text-red-400'
                                        : days !== null && days <= 30
                                          ? 'text-yellow-600 dark:text-yellow-400'
                                          : 'text-gray-700 dark:text-gray-300',
                                ]"
                            >
                                {{ formatDate(expiresAt) }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Server / Hardware Info -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 lg:col-span-2 dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-4 flex items-center gap-2 text-gray-500 dark:text-gray-400">
                        <Cpu :size="18" />
                        <span class="text-sm font-medium uppercase tracking-wide">Bound Server</span>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-gray-400 dark:text-gray-500">Hostname</dt>
                            <dd class="mt-0.5 font-mono text-sm text-gray-700 dark:text-gray-300">
                                {{ hostname }}
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-gray-400 dark:text-gray-500">Hardware ID (SHA-256)</dt>
                            <dd
                                :class="[
                                    'mt-1 break-all rounded-lg border px-3 py-2 font-mono text-xs',
                                    licenseStatus === 'wrong_machine'
                                        ? 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-800 dark:bg-orange-900/20 dark:text-orange-300'
                                        : 'border-gray-100 bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400',
                                ]"
                            >
                                {{ hardwareHash }}
                            </dd>
                        </div>
                    </div>

                    <!-- Wrong machine hint -->
                    <div
                        v-if="licenseStatus === 'wrong_machine'"
                        class="mt-4 flex items-start gap-2 rounded-lg bg-orange-50 p-3 text-xs text-orange-700 dark:bg-orange-900/20 dark:text-orange-300"
                    >
                        <AlertTriangle :size="14" class="mt-0.5 shrink-0" />
                        <span>
                            The license file was generated for a different machine. If you moved the application to this
                            server, you must re-activate with the key on this machine.
                        </span>
                    </div>

                    <!-- Active confirmation -->
                    <div
                        v-if="licenseStatus === 'active'"
                        class="mt-4 flex items-center gap-2 rounded-lg bg-green-50 p-3 text-xs text-green-700 dark:bg-green-900/20 dark:text-green-300"
                    >
                        <MonitorCheck :size="14" class="shrink-0" />
                        <span>This server matches the bound hardware. License is valid on this machine.</span>
                    </div>
                </div>
            </div>
        </template>
    </Layout>
</template>
