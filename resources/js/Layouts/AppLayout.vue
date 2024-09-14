<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import AppSidebar from './AppSidebar.vue';
import AppTopBar from './AppTopBar.vue';

defineProps({
    title: String,
});

const showingNavigationDropdown = ref(false);

const switchToTeam = (team) => {
    router.put(route('current-team.update'), {
        team_id: team.id,
    }, {
        preserveState: false,
    });
};

const logout = () => {
    router.post(route('logout'));
};

</script>

<template>
    <div>
        <Head :title="title" />
        <Banner />
        <div class="min-h-screen bg-gray-100">
            <div class="flex">
                <app-sidebar ></app-sidebar>
                <div class="block whole-main">
                    <app-top-bar></app-top-bar>

                    <main class="main-layout w-full">
                        <div class="mt-5">
                            <slot class="w-[100%]"/>
                        </div>
                    </main>
                </div>

            </div>
        </div>
    </div>
    <Toast group="tr" />
</template>
