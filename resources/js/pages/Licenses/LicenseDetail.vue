<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { AlertCircle, Trash2, Plus } from 'lucide-vue-next';

const page = usePage();
const license = computed(() => page.props.license);
const availableModules = computed(() => page.props.availableModules || []);

const attachedModuleIds = computed(() =>
    license.value?.modules?.map((m: any) => m.id) || []
);

const unattachedModules = computed(() =>
    availableModules.value.filter((m: any) => !attachedModuleIds.value.includes(m.id))
);

const loadingModuleId = ref<number | null>(null);

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

const daysUntilExpiration = computed(() => {
    if (!license.value) return 0;
    const days = Math.ceil(
        (new Date(license.value.valid_until).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24)
    );
    return Math.max(0, days);
});

const isExpiringSoon = computed(() => daysUntilExpiration.value <= 30 && daysUntilExpiration.value > 0);

const attachModule = async (moduleId: number) => {
    loadingModuleId.value = moduleId;
    try {
        const response = await fetch(`/license/licenses/${license.value.id}/modules`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': page.props.csrf_token,
            },
            body: JSON.stringify({ module_id: moduleId }),
        });

        if (response.ok) {
            router.visit(`/license/licenses/${license.value.id}`);
        } else {
            const error = await response.json();
            alert(error.message || 'Failed to attach module');
        }
    } catch (error) {
        console.error('Error attaching module:', error);
        alert('Error attaching module');
    } finally {
        loadingModuleId.value = null;
    }
};

const detachModule = async (moduleId: number) => {
    if (!confirm('Are you sure you want to remove this module?')) {
        return;
    }

    loadingModuleId.value = moduleId;
    try {
        const response = await fetch(`/license/licenses/${license.value.id}/modules/${moduleId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': page.props.csrf_token,
            },
        });

        if (response.ok) {
            router.visit(`/license/licenses/${license.value.id}`);
        } else {
            const error = await response.json();
            alert(error.message || 'Failed to detach module');
        }
    } catch (error) {
        console.error('Error detaching module:', error);
        alert('Error detaching module');
    } finally {
        loadingModuleId.value = null;
    }
};
</script>

<template>
    <div class="space-y-6" v-if="license">
        <div>
            <h2 class="text-2xl font-bold">License Details</h2>
            <p class="text-gray-600">{{ license.license_key }}</p>
        </div>

        <!-- License Status Card -->
        <Card>
            <CardHeader>
                <CardTitle>License Information</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Status</p>
                        <Badge :class="statusColor(license.status)">
                            {{ license.status }}
                        </Badge>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Type</p>
                        <p class="capitalize">{{ license.type }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Valid From</p>
                        <p>{{ new Date(license.valid_from).toLocaleDateString() }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Valid Until</p>
                        <p>{{ new Date(license.valid_until).toLocaleDateString() }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">User Limit</p>
                        <p>{{ license.user_limit }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Days Remaining</p>
                        <p :class="isExpiringSoon ? 'text-orange-600 font-semibold' : ''">
                            {{ daysUntilExpiration }} days
                        </p>
                    </div>
                </div>

                <div v-if="license.notes" class="pt-4 border-t">
                    <p class="text-sm font-medium text-gray-600 mb-2">Notes</p>
                    <p class="text-sm text-gray-700">{{ license.notes }}</p>
                </div>
            </CardContent>
        </Card>

        <!-- Attached Modules -->
        <Card>
            <CardHeader>
                <CardTitle>Attached Modules</CardTitle>
                <CardDescription>Modules enabled in this license</CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="license.modules && license.modules.length > 0" class="space-y-2">
                    <div v-for="module in license.modules" :key="module.id" class="flex items-center justify-between p-3 border rounded">
                        <div>
                            <p class="font-medium">{{ module.name }}</p>
                            <p class="text-sm text-gray-600">{{ module.code }}</p>
                        </div>
                        <Button 
                            variant="destructive" 
                            size="sm"
                            @click="detachModule(module.id)"
                            :disabled="loadingModuleId === module.id"
                        >
                            <Trash2 :size="16" class="mr-2" />
                            {{ loadingModuleId === module.id ? 'Removing...' : 'Remove' }}
                        </Button>
                    </div>
                </div>
                <p v-else class="text-gray-500">No modules attached to this license.</p>
            </CardContent>
        </Card>

        <!-- Available Modules to Attach -->
        <Card v-if="unattachedModules.length > 0">
            <CardHeader>
                <CardTitle>Available Modules</CardTitle>
                <CardDescription>Add more modules to this license</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="space-y-2">
                    <div v-for="module in unattachedModules" :key="module.id" class="flex items-center justify-between p-3 border rounded">
                        <div>
                            <p class="font-medium">{{ module.name }}</p>
                            <p class="text-sm text-gray-600">{{ module.code }}</p>
                        </div>
                        <Button 
                            size="sm"
                            @click="attachModule(module.id)"
                            :disabled="loadingModuleId === module.id"
                        >
                            <Plus :size="16" class="mr-2" />
                            {{ loadingModuleId === module.id ? 'Adding...' : 'Add' }}
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
