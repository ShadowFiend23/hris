<script setup>
import { ref } from 'vue';

import AppMenuItem from './AppMenuItem.vue';

const model = ref([
    {
        items: [
            {   label: 'Dashboard',
                icon: 'pi pi-fw pi-home',
                to: route('dashboard'),
                prefix: 'dashboard'
            },
            {
                label: 'Personnel',
                to: route('employees.index'),
                icon: 'fa-solid fa-users',
                prefix: 'personnel'
            },
            {
                label: 'Timekeeping',
                to: route('employees.index'),
                icon: 'fa-solid fa-calendar-days',
                prefix: 'timekeeping'
            },
            {
                label: 'Payroll',
                to: route('employees.index'),
                icon: 'fa-solid fa-credit-card',
                prefix: 'payroll'
            }
        ]
    },

]);
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="item">
            <app-menu-item v-if="!item.separator" :item="item" :index="i"></app-menu-item>
            <li v-if="item.separator" class="menu-separator"></li>
        </template>
    </ul>
</template>
<style lang="scss" scoped></style>
