<script setup>
import { ref, getCurrentInstance } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useDialog } from 'primevue/usedialog';
import AddEmployee from '@/Pages/Dialogs/Employees/Add.vue';

const dialog = useDialog();

const props = defineProps({
    departments: {
        type: Array
    }
});

const departmentsPopOverBtn = ref({});

const cardItems = ref([
    {
        title : "Card 1",
        content: 'Content 1'
    },
    {
        title : "Card 2",
        content: 'Content 2'
    },
    {
        title : "Card 3",
        content: 'Content 3'
    },
    {
        title : "Card 4",
        content: 'Content 4'
    },
    {
        title : "Card 5",
        content: 'Content 5'
    },
    {
        title : "Card 6",
        content: 'Content 6'
    },
    {
        title : "Card 7",
        content: 'Content 7'
    }
]);

const exportDropdown = ref([
    {
        label: 'PDF',
        icon: 'fa-solid fa-file-pdf'
    },
    {
        label: 'Supervisor+',
        icon: 'fa-solid fa-person-circle-plus'
    },
    {
        label: 'All+',
        icon: 'fa-solid fa-people-line'
    }
]);

const departmentPopOver = ref([
    {
        label: 'Edit',
        icon: 'fa-solid fa-edit'
    },
    {
        label: 'Delete',
        icon: 'fa-solid fa-trash'
    }
]);

const departmentPopOverToggle = (index, event) => {
    departmentsPopOverBtn.value[index].toggle(event);
};

</script>

<template>
    <AppLayout title="Employees">
        <div class="mx-auto pr-4 pl-1">
            <Toolbar>
                <template #start>
                    <Button icon="pi pi-plus" class="mr-2" severity="secondary" text />
                    <Button icon="pi pi-print" class="mr-2" severity="secondary" text />
                </template>

                <template #center>
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText placeholder="Search" />
                    </IconField>
                </template>

                <template #end> <SplitButton label="Export" :model="exportDropdown" icon="fa-solid fa-file-export"></SplitButton></template>
            </Toolbar>

            <Panel header="Departments" class="mt-3" >
                <div class="flex flex-wrap">
                    <div class="cardHolder w-[23%] mx-4 mt-3" v-for="(card,i) in cardItems" :key="card.title">
                        <Card>
                            <template #title>{{ card.title }}</template>
                            <template #content>
                                <p class="m-0">
                                    {{ card.content }}
                                </p>
                            </template>
                            <template #footer>
                                <div class="float-right">
                                    <Button type="button" icon="pi pi-ellipsis-v" @click="departmentPopOverToggle(i, $event)" aria-haspopup="true" aria-controls="overlay_menu" size="small" />
                                    <Menu :ref="(el) => (departmentsPopOverBtn[i] = el)" id="overlay_menu" :model="departmentPopOver" :popup="true" />
                                </div>
                            </template>
                        </Card>
                    </div>
                </div>
            </Panel>
        </div>
    </AppLayout>
</template>
