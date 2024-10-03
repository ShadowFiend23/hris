import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { definePreset } from '@primevue/themes';
import { library, dom } from "@fortawesome/fontawesome-svg-core";
import { fas } from '@fortawesome/free-solid-svg-icons'
import { fab } from '@fortawesome/free-brands-svg-icons';
import { far } from '@fortawesome/free-regular-svg-icons';
import PrimeVue from 'primevue/config';
import DialogService from 'primevue/dialogservice';
import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast';
import DynamicDialog from 'primevue/dynamicdialog';
import Aura from '@primevue/themes/lara';

library.add(fas, far, fab);
dom.watch();
import '../assets/styles.scss';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const MyPreset = definePreset(Aura, {
    semantic: {
        primary: {
            50: '#B7B1F7',
            100: '#A9A2F6',
            200: '#9B93F4',
            300: '#8D84F3',
            400: '#8075F1',
            500: '#7367F0',
            600: '#675DD8',
            700: '#5C52C0',
            800: '#5048A8',
            900: '#453E90',
            950: '#393478'
        }
    }
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(PrimeVue, {
                theme: {
                    preset: MyPreset
                }
            })
            .use(DialogService)
            .use(ToastService)
            .component("DynamicDialog",DynamicDialog)
            .component("Toast",Toast)
            .mount(el)
    },
    progress: {
        color: '#4B5563',
    },
});
