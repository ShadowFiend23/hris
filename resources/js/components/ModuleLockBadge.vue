<script setup lang="ts">
import { Lock } from 'lucide-vue-next';

defineProps<{
    moduleName: string;
    isLocked: boolean;
}>();
</script>

<template>
    <div v-if="isLocked" class="flex items-center gap-2 rounded-md bg-yellow-50 px-3 py-2 text-sm text-yellow-800 border border-yellow-200">
        <Lock class="h-4 w-4" />
        <div>
            <p class="font-medium">{{ moduleName }} is locked</p>
            <p class="text-xs opacity-80">Contact your administrator or upgrade your license to unlock this module.</p>
        </div>
    </div>
</template>
