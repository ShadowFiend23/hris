import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useModuleAccess() {
    const page = usePage();

    const hasModuleAccess = (moduleCode: string): boolean => {
        const modules = page.props.modules || [];
        const module = modules.find((m: any) => m.code === moduleCode);
        return module ? module.enabled : false;
    };

    const getEnabledModules = computed(() => {
        const modules = page.props.modules || [];
        return modules.filter((m: any) => m.enabled);
    });

    const getDisabledModules = computed(() => {
        const modules = page.props.modules || [];
        return modules.filter((m: any) => !m.enabled);
    });

    return {
        hasModuleAccess,
        getEnabledModules,
        getDisabledModules,
    };
}
