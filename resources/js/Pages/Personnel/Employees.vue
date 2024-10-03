<script setup>

import AppLayout from '@/Layouts/AppLayout.vue';
import { useDialog } from 'primevue/usedialog';
import AddEmployee from '@/Pages/Dialogs/Employees/Add.vue';
import { watch, ref } from 'vue';

const dialog = useDialog();
const search = ref('');

const props = defineProps({
    employees: {
        type: Array
    },
});

const employeesList = ref(props.employees);

watch(search,async (val) =>{
    let resp = await axios.get(route('employees.search',{ search: val}))
        .then((response) => response)
        .catch((e) => console.log(e));

    if(Array.isArray(resp.data)){
        employeesList.value = resp.data
    }
})

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

const editEmployeeClicked = () => {

}

const deleteEmployeeClicked = () => {

}

</script>

<template>
    <AppLayout title="Employees">
        <div class="mx-auto pr-4 pl-1">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="card">
                    <div class="font-semibold text-xl mb-4">Filtering</div>
                        <DataTable
                            :value="employeesList"
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
                                        <InputText placeholder="Keyword Search" style="width: 100%" v-model="search"/>
                                    </IconField>
                                </div>
                            </template>
                            <template #empty> No customers found. </template>
                            <template #loading> Loading customers data. Please wait. </template>
                            <Column field="fullName" header="Full Name"></Column>
                            <Column field="department" header="Department"></Column>
                            <Column field="position" header="Position"></Column>
                            <Column field="id" header="Action" class="lg:max-w-[6rem] xl:max-w-[5rem]">
                                <template #body="data">
                                    <ButtonGroup class="flex justify-center">
                                        <Button severity="" label="Edit" icon="fa fa-pen" @click="editEmployeeClicked(data)" />
                                        <Button severity="danger" label="Delete" icon="fa fa-trash" @click="deleteEmployeeClicked(data)" />
                                    </ButtonGroup>
                                </template>
                            </Column>
                        </DataTable>
                </div>
            </div>
        </div>

        <DynamicDialog />
    </AppLayout>
</template>
