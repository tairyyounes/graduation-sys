import './bootstrap';
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import App from './App.vue'
import StudentDashboard from './components/StudentDashboard.vue'
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";
import { i18n } from './i18n'
import { ZiggyVue } from 'ziggy-js'

const appRoot = document.getElementById('app')

if (appRoot && appRoot.dataset.page) {
    createInertiaApp({
        resolve: name => {
            const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
            return pages[`./Pages/${name}.vue`]
        },
        setup({ el, App, props, plugin }) {
            const vueApp = createApp({ render: () => h(App, props) })
                .use(plugin)
                .use(i18n)
                .use(Toast, {
                    position: 'top-right',
                    timeout: 3000,
                    closeOnClick: true,
                })

            if (typeof Ziggy !== 'undefined') {
                vueApp.use(ZiggyVue, Ziggy)
            }

            vueApp.mount(el)
        },
    })
} else if (appRoot) {
    const app = createApp(App)
    app.use(Toast)
    app.use(i18n)
    app.mount(appRoot)
}

const studentRoot = document.getElementById('student-dashboard')

if (studentRoot) {
    const app = createApp(StudentDashboard)
    app.use(Toast)
    app.use(i18n)
    app.mount(studentRoot)
}