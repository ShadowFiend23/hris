<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Plus } from 'lucide-vue-next';

const page = usePage();
const licenses = computed(() => page.props.licenses?.data || []);

const showCreateForm = ref(false);
const isSubmitting = ref(false);

const form = useForm({
    license_key: '',
    type: 'enterprise',
    valid_from: new Date().toISOString().split('T')[0],
    valid_until: new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    user_limit: 100,
    status: 'active',
    notes: '',
});

const statusColor = (status: string) => {
    switch (status) {
        case 'active':
            return 'bg-green-100 text-green-800';
        case 'inactive':
            return 'bg-gray-100 text-gray-800';
        case 'expired':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const isExpiring = (validUntil: string) => {
    const daysUntil = Math.ceil((new Date(validUntil).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24));
    return daysUntil <= 30 && daysUntil > 0;
};

const submitCreateLicense = async () => {
    isSubmitting.value = true;
    try {
        await form.post('/license/licenses', {
            onSuccess: () => {
                showCreateForm.value = false;
                form.reset();
                router.visit('/license/licenses');
            },
            onError: () => {
                isSubmitting.value = false;
            },
        });
    } catch (error) {
        isSubmitting.value = false;
        console.error('Error creating license:', error);
    }
};

const cancelCreateLicense = () => {
    showCreateForm.value = false;
    form.reset();
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold">Licenses</h2>
            <Button @click="showCreateForm = true" :disabled="showCreateForm">
                <Plus :size="18" class="mr-2" />
                Create License
            </Button>
        </div>

        <!-- Create License Form -->
        <div v-if="showCreateForm" class="bg-white p-6 border rounded-lg space-y-4">
            <h3 class="text-lg font-semibold">Create New License</h3>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">License Key</label>
                    <input
                        v-model="form.license_key"
                        type="text"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :disabled="isSubmitting"
                    />
                    <p v-if="form.errors.license_key" class="text-red-600 text-sm mt-1">{{ form.errors.license_key }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select
                        v-model="form.type"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :disabled="isSubmitting"
                    >
                        <option value="standard">Standard</option>
                        <option value="professional">Professional</option>
                        <option value="enterprise">Enterprise</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valid From</label>
                    <input
                        v-model="form.valid_from"
                        type="date"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :disabled="isSubmitting"
                    />
                    <p v-if="form.errors.valid_from" class="text-red-600 text-sm mt-1">{{ form.errors.valid_from }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valid Until</label>
                    <input
                        v-model="form.valid_until"
                        type="date"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :disabled="isSubmitting"
                    />
                    <p v-if="form.errors.valid_until" class="text-red-600 text-sm mt-1">{{ form.errors.valid_until }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">User Limit</label>
                    <input
                        v-model.number="form.user_limit"
                        type="number"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :disabled="isSubmitting"
                    />
                    <p v-if="form.errors.user_limit" class="text-red-600 text-sm mt-1">{{ form.errors.user_limit }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select
                        v-model="form.status"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :disabled="isSubmitting"
                    >
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                <textarea
                    v-model="form.notes"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    rows="3"
                    :disabled="isSubmitting"
                />
            </div>

            <div class="flex gap-2 justify-end pt-4">
                <Button variant="outline" @click="cancelCreateLicense" :disabled="isSubmitting">
                    Cancel
                </Button>
                <Button @click="submitCreateLicense" :disabled="isSubmitting">
                    {{ isSubmitting ? 'Creating...' : 'Create License' }}
                </Button>
            </div>
        </div>

        <!-- Licenses Table -->
        <div class="border rounded-lg">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>License Key</TableHead>
                        <TableHead>Type</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Valid Until</TableHead>
                        <TableHead>Modules</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="license in licenses" :key="license.id">
                        <TableCell class="font-mono text-sm">{{ license.license_key }}</TableCell>
                        <TableCell class="capitalize">{{ license.type }}</TableCell>
                        <TableCell>
                            <Badge :class="statusColor(license.status)">
                                {{ license.status }}
                            </Badge>
                            <Badge v-if="isExpiring(license.valid_until)" class="ml-2 bg-yellow-100 text-yellow-800">
                                Expiring Soon
                            </Badge>
                        </TableCell>
                        <TableCell>{{ new Date(license.valid_until).toLocaleDateString() }}</TableCell>
                        <TableCell>
                            {{ license.modules?.length || 0 }} module(s)
                        </TableCell>
                        <TableCell class="text-right">
                            <Link :href="`/license/licenses/${license.id}`">
                                <Button variant="outline" size="sm">View</Button>
                            </Link>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>
