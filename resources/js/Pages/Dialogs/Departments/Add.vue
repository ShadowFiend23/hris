<script setup>
import { ref, inject, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import InputError from '@/Components/InputError.vue';

const dialogRef = inject('dialogRef');
const toast = useToast();

const form = useForm({
    name: '',
    departmentHead: '',
    positions: []
});

const departmentHeadList = ref([]);
const positionList = ref([]);
const positionChipList = ref([]);

const submit = () => {
    form.post(route('departments.store'), {
        onSuccess: (xhr) => {
            let response = xhr.props.response;

            toast.add({
                severity: response.type ,
                summary: response.message,
                group: 'tr',
                life: 5000
            });

            dialogRef.value.close();
        }
    });
};

const searchEmployees = (val) =>{
    setTimeout( async () => {
        let resp = await axios.get(route('employees.search',{ search: val.query }))
            .then((response) => response)
            .catch((e) => console.log(e));

        if(Array.isArray(resp.data)){
            departmentHeadList.value = resp.data.map((val) =>{
                return {
                    name: `${val.firstName} ${val.lastName}`,
                    id: val.id
                }
            })
        }
    },500)
}

const searchPosition = async (val) =>{
    setTimeout( async () => {
        let resp = await axios.get(route('positions.search',{ search: val.query }))
            .then((response) => response)
            .catch((e) => console.log(e));

        if(Array.isArray(resp.data)){
            positionList.value = resp.data.map((val) =>{
                return {
                    name: val.position,
                    id: val.id
                }
            })
        }
    },500)
}

</script>

<template>
    <form @submit.prevent="submit">
        <Panel header="Position Info">
            <div class="mt-[-10px]">
                <div class="w-full mx-1">
                    <FloatLabel>
                        <InputText class="mt-6 block w-full" v-model="form.name"/>
                        <label for="position">Department Name</label>
                    </FloatLabel>
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>
                <div class="w-full mx-1 mt-3">
                    <label for="departmentHead" class="text-[12px] ml-3">Head of the Department</label>
                    <AutoComplete class="block w-full autocomplete-custom" v-model="form.departmentHead" inputId="departmentHead" forceSelection optionLabel="name" :suggestions="departmentHeadList" @complete="searchEmployees" />
                    <InputError class="mt-2" :message="form.errors.departmentHead" />
                </div>
                <div class="w-full mx-1 mt-3">
                    <label for="positionList" class="text-[12px] ml-3">List of positions</label>
                    <AutoComplete class="block w-full autocomplete-custom-chip" v-model="form.positions" inputId="positionList" forceSelection optionLabel="name" multiple :suggestions="positionList" @complete="searchPosition"/>
                    <InputError class="mt-2" :message="form.errors.positions" />
                </div>
            </div>
        </Panel>

        <Button type="button" label="Submit" class="mt-8 float-right min-w-28" outlined @click="submit" />
    </form>
</template>
