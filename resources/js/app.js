import './bootstrap';
import { createApp } from 'vue'
import App from './App.vue'
import StudentDashboard from './components/StudentDashboard.vue'
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";
<<<<<<< Updated upstream
import { i18n } from './i18n'
=======
import i18n from './i18n';
>>>>>>> Stashed changes

const appRoot = document.getElementById('app')

if (appRoot) {
    const app = createApp(App)
    app.use(i18n)
    app.use(Toast)
    app.use(i18n)
    app.mount(appRoot)
}

const studentRoot = document.getElementById('student-dashboard')

if (studentRoot) {
    const app = createApp(StudentDashboard)
    app.use(i18n)
    app.use(Toast)
    app.use(i18n)
    app.mount(studentRoot)
}