<script setup>
import { ref, inject } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import InputError from '@/Components/InputError.vue';

const dialogRef = inject('dialogRef');
const toast = useToast();

const form = useForm({
    position: '',
    abbreviation: ''
});

const submit = () => {
    form.post(route('positions.store'), {
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
</script>

<template>
    <form @submit.prevent="submit">
        <Panel header="Position Info">
            <div class="mt-[-10px]">
                <div class="flex justify-between">
                    <div class="w-[70%] mx-1">
                        <FloatLabel>
                            <InputText class="mt-6 block w-full" v-model="form.position"/>
                            <label for="position">Position</label>
                        </FloatLabel>
                        <InputError class="mt-2" :message="form.errors.position" />
                    </div>
                    <div class="w-[30%] mx-1">
                        <FloatLabel>
                            <InputText class="mt-6 block w-full" v-model="form.abbreviation" />
                            <label for="abbreviation">Abbreviation</label>
                        </FloatLabel>
                        <InputError class="mt-2" :message="form.errors.abbreviation" />
                    </div>
                </div>
            </div>
        </Panel>

        <Button type="button" label="Submit" class="mt-8 float-right min-w-28" outlined @click="submit" />
    </form>
</template>
