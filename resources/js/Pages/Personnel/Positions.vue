<script setup>

import AppLayout from '@/Layouts/AppLayout.vue';
import { useDialog } from 'primevue/usedialog';
import { router } from '@inertiajs/vue3';
import AddPosition from '@/Pages/Dialogs/Positions/Add.vue';
import { watch, ref } from 'vue';
import axios from 'axios';

const dialog = useDialog();
const search = ref('');

const props = defineProps({
    positions: {
        type: Array
    }
});

const positionList = ref(props.positions);

watch(search,async (val) =>{
    let resp = await axios.get(route('positions.search',{ search: val}))
        .then((response) => response)
        .catch((e) => console.log(e));

    if(Array.isArray(resp.data)){
        positionList.value = resp.data
    }
})
// const searchKeyUp = (e) => {
//     form.get(route('positions.search'), {
//         onSuccess: (xhr) => {
//             let response = xhr.props.response;

//         }
//     });
// }

const addPositionClicked = () => {
    dialog.open(AddPosition,{
        props: {
            header: 'Add a Position',
            style: {
                width: '550px'
            },
            modal: true,
            position: 'top'
        },
    });
}

const editPositionClicked = (id) => {

}

const deletePositionClicked = (id) => {

}

</script>

<template>
    <AppLayout title="Positions">
        <div class="mx-auto pr-4 pl-1">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="card">
                    <div class="font-semibold text-xl mb-4">Filtering</div>
                        <DataTable
                            :value="positionList"
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
                                    <Button type="button" icon="pi pi-plus" label="Add" class="mx-2" outlined @click="addPositionClicked()" />
                                    <IconField iconPosition="left">
                                        <InputIcon class="pi pi-search" />
                                        <InputText placeholder="Keyword Search" style="width: 100%" v-model="search"/>
                                    </IconField>
                                </div>
                            </template>
                            <template #empty> No customers found. </template>
                            <template #loading> Loading customers data. Please wait. </template>
                            <Column field="position" header="">
                                <template #body="slotProps">
                                    {{ slotProps.index + 1 }}
                                </template>
                            </Column>
                            <Column field="position" header="Position"></Column>
                            <Column field="abbreviation" header="Abbreviation"></Column>
                            <Column field="id" header="Action" class="lg:max-w-[6rem] xl:max-w-[5rem]">
                                <template #body="data">
                                    <ButtonGroup class="flex justify-center">
                                        <Button severity="" label="Edit" icon="fa fa-pen" @click="editPositionClicked(data)" />
                                        <Button severity="danger" label="Delete" icon="fa fa-trash" @click="deletePositionClicked(data)" />
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
