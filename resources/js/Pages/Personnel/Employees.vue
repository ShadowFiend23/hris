<script setup>

import AppLayout from '@/Layouts/AppLayout.vue';
import { useDialog } from 'primevue/usedialog';
import AddEmployee from '@/Pages/Dialogs/Employees/Add.vue';

const dialog = useDialog();

const props = defineProps({
    employees: {
        type: Array
    },
});

const addEmployeeClicked = () => {
    dialog.open(AddEmployee,{
        props: {
            header: 'Add Employee',
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
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="card">
                    <div class="font-semibold text-xl mb-4">Filtering</div>
                        <DataTable
                            :value="props.employees"
                            :paginator="true"
                            :rows="10"
                            dataKey="ID"
                            :rowHover="true"
                            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} Employees"
                            tableClass="headerTableTextCenter"
                            showGridlines
                        >
                            <template #header>
                                <div class="flex justify-content-end flex-column sm:flex-row">
                                    <Button type="button" icon="pi pi-plus" label="Add" class="mx-2" outlined @click="addEmployeeClicked()" />
                                    <IconField iconPosition="left">
                                        <InputIcon class="pi pi-search" />
                                        <InputText placeholder="Keyword Search" style="width: 100%" />
                                    </IconField>
                                </div>
                            </template>
                            <template #empty> No customers found. </template>
                            <template #loading> Loading customers data. Please wait. </template>
                            <Column field="fullName" header="Full Name"></Column>
                            <Column field="department" header="Department"></Column>
                            <Column field="position" header="Position"></Column>
                        </DataTable>
                </div>
            </div>
        </div>

        <DynamicDialog />
    </AppLayout>
</template>
