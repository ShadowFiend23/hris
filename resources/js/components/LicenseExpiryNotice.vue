<script setup lang="ts">
import { AlertCircle, AlertTriangle } from 'lucide-vue-next';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const licenseInfo = computed(() => page.props.licenseInfo || {});

// Show notice if isExpiringSoon is true (covers both expiring and expired licenses)
const isVisible = computed(() => licenseInfo.value.isExpiringSoon);

// Determine if license is expired (daysRemaining <= 0)
const isExpired = computed(() => licenseInfo.value.daysRemaining <= 0);

// Determine banner styling based on expiry status
const bannerClass = computed(() => {
  if (isExpired.value) {
    return 'rounded-md border border-red-200 bg-red-50 p-4';
  }
  return 'rounded-md border border-orange-200 bg-orange-50 p-4';
});

const iconClass = computed(() => {
  if (isExpired.value) {
    return 'text-red-600';
  }
  return 'text-orange-600';
});

const headingClass = computed(() => {
  if (isExpired.value) {
    return 'font-semibold text-red-900';
  }
  return 'font-semibold text-orange-900';
});

const textClass = computed(() => {
  if (isExpired.value) {
    return 'text-sm text-red-800 mt-1';
  }
  return 'text-sm text-orange-800 mt-1';
});

const smallTextClass = computed(() => {
  if (isExpired.value) {
    return 'text-xs text-red-700 mt-2';
  }
  return 'text-xs text-orange-700 mt-2';
});

const buttonClass = computed(() => {
  if (isExpired.value) {
    return 'mt-3 px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700';
  }
  return 'mt-3 px-3 py-1 text-sm bg-orange-600 text-white rounded hover:bg-orange-700';
});

const heading = computed(() => {
  if (isExpired.value) {
    return 'License Expired';
  }
  return 'License Expiring Soon';
});

const message = computed(() => {
  if (isExpired.value) {
    return `Your license expired ${Math.abs(licenseInfo.value.daysRemaining)} day${Math.abs(licenseInfo.value.daysRemaining) !== 1 ? 's' : ''} ago.`;
  }
  return `Your license will expire in ${licenseInfo.value.daysRemaining} day${licenseInfo.value.daysRemaining !== 1 ? 's' : ''}.`;
});

const icon = computed(() => {
  if (isExpired.value) {
    return AlertTriangle;
  }
  return AlertCircle;
});
</script>

<template>
    <div v-if="isVisible" :class="bannerClass">
        <div class="flex items-start gap-3">
            <component :is="icon" class="h-5 w-5 flex-shrink-0 mt-0.5" :class="iconClass" />
            <div class="flex-1">
                <h3 :class="headingClass">{{ heading }}</h3>
                <p :class="textClass">
                    {{ message }}
                </p>
                <p :class="smallTextClass">Expires on: {{ new Date(licenseInfo.validUntil).toLocaleDateString() }}</p>
                <button :class="buttonClass">
                    {{ isExpired ? 'Renew License Now' : 'Renew License' }}
                </button>
            </div>
        </div>
    </div>
</template>
