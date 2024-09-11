<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';

const form = useForm({
    firstName: '',
    lastName: '',
    middleName: '',
    suffix: '',
    employeeID: '',
    department: '',
    position: '',
    dateStart: '',
    status: 'Active'
});

const statusOptions = ref([
    "Active",
    "Inactive"
])

const submit = () => {
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <form @submit.prevent="submit">
        <Panel header="Basic Info">
            <div class="mt-[-10px]">
                <!-- First Row of Employment Info -->
                <div class="flex justify-between">
                    <div class="w-[28%]">
                        <FloatLabel>
                            <InputText class="mt-6 block w-full" v-model="form.firstName" />
                            <label for="firstName">First Name</label>
                        </FloatLabel>
                        <InputError class="mt-2" :message="form.errors.firstName" />
                    </div>
                    <div class="w-[28%]">
                        <FloatLabel>
                            <InputText class="mt-6 block w-full" v-model="form.lastName" />
                            <label for="lastName">Last Name</label>
                        </FloatLabel>
                        <InputError class="mt-2" :message="form.errors.lastName" />
                    </div>
                    <div class="w-[28%]">
                        <FloatLabel>
                            <InputText class="mt-6 block w-full" v-model="form.middleName" />
                            <label for="lastName">Middle Name</label>
                        </FloatLabel>
                    </div>
                    <div class="w-[10%]">
                        <FloatLabel>
                            <InputText class="mt-6 block w-full" v-model="form.suffix" />
                            <label for="lastName">Suffix</label>
                        </FloatLabel>
                    </div>
                </div>

                <!-- Second Row of Basic Info -->
                <div class="flex justify-between">
                </div>
            </div>
        </Panel>
        <Panel class="mt-4" header="Employment Info">
            <!-- First Row of Employment Info -->
            <div class="mt-[-10px]">
                <div class="flex justify-between">

                    <div class="w-[32%]">
                        <FloatLabel>
                            <InputText class="mt-6 block w-full" v-model="form.employeeID" />
                            <label for="employeeID">Employee ID</label>
                        </FloatLabel>
                        <InputError class="mt-2" :message="form.errors.employeeID" />
                    </div>
                    <div class="w-[32%]">
                        <FloatLabel>
                            <InputText class="mt-6 block w-full" v-model="form.department" />
                            <label for="department">Department</label>
                        </FloatLabel>
                        <InputError class="mt-2" :message="form.errors.department" />
                    </div>
                    <div class="w-[32%]">
                        <FloatLabel>
                            <InputText class="mt-6 block w-full" v-model="form.position" />
                            <label for="position">Position</label>
                        </FloatLabel>
                        <InputError class="mt-2" :message="form.errors.position" />
                    </div>
                </div>


                <!-- Second Row of employment info -->
                <div class="flex justify-between mt-3">
                    <div class="w-[32%]">
                        <FloatLabel class="mt-[21px]">
                            <DatePicker v-model="form.dateStart" class="w-full" />
                            <label for="dateStart">Start Date</label>
                        </FloatLabel>
                        <InputError class="mt-2" :message="form.errors.dateStart" />
                    </div>
                    <div class="w-[32%]">
                        <FloatLabel>
                            <InputText class="mt-6 block w-full" v-model="form.employmentType" />
                            <label for="employmentType">Employment Type</label>
                        </FloatLabel>
                        <InputError class="mt-2" :message="form.errors.employmentType" />
                    </div>
                    <div class="w-[32%]">
                        <label for="status" class="text-[12px]">Status</label>
                        <SelectButton class="block w-full statusOptions" v-model="form.status" :options="statusOptions" aria-labelledby="basic" />
                    </div>
                </div>
            </div>
        </Panel>
    </form>
</template>
