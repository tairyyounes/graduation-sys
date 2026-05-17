import './bootstrap';
import { createApp } from 'vue'
import App from './App.vue'
import StudentDashboard from './components/StudentDashboard.vue'
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";

const appRoot = document.getElementById('app')

if (appRoot) {
    const app = createApp(App)
    app.use(Toast)
    app.mount(appRoot)
}

const studentRoot = document.getElementById('student-dashboard')

if (studentRoot) {
    const app = createApp(StudentDashboard)
    app.use(Toast)
    app.mount(studentRoot)
}