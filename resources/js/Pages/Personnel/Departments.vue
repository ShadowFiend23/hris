<script setup>
import { ref, getCurrentInstance } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useDialog } from 'primevue/usedialog';
import AddDepartment from '@/Pages/Dialogs/Departments/Add.vue';

const dialog = useDialog();

const props = defineProps({
    departments: {
        type: Array
    }
});

const departmentsPopOverBtn = ref({});


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

const addDepartmentClicked = () => {
    dialog.open(AddDepartment,{
        props: {
            header: 'Add Department',
            style: {
                width: '60vw'
            },
            breakpoints: {
                '960px': '75vw',
                '640px': '90vw'
            },
            modal: true,
            position: 'top'
        },
    });
}
</script>

<template>
    <AppLayout title="Employees">
        <div class="mx-auto pr-4 pl-1">
            <Toolbar>
                <template #start>
                    <Button icon="pi pi-plus" class="mr-2" severity="default" label="Add" outlined @click="addDepartmentClicked"/>
                    <Button icon="pi pi-print" class="mr-2" severity="info" label="Print"/>
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
                    <div class="cardHolder w-[23%] mx-4 mt-3" v-for="(row,i) in props.departments" :key="row['departments.id']">
                        <Card>
                            <template #title>{{ row["departments.name"]}}</template>
                            <template #content>
                                <p class="m-0">
                                    {{ `${row["employees.firstName"]} ${row["employees.lastName"]}` }}
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

        <DynamicDialog />
    </AppLayout>
</template>
