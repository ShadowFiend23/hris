<script setup>

import AppLayout from '@/Layouts/AppLayout.vue';
import { useDialog } from 'primevue/usedialog';
import AddEmployee from '@/Pages/Dialogs/Employees/Add.vue';

const dialog = useDialog();

const props = defineProps({
    departments: {
        type: Array
    },
});

</script>

<template>
    <AppLayout title="Employees">
        <div class="mx-auto pr-4 pl-1">
            test
        </div>
    </AppLayout>
</template>
