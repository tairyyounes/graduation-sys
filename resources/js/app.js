import './bootstrap';
import { createApp } from 'vue'
import App from './App.vue'
import StudentDashboard from './components/StudentDashboard.vue'

const appRoot = document.getElementById('app')

if (appRoot) {
    createApp(App).mount(appRoot)
}

const studentRoot = document.getElementById('student-dashboard')

if (studentRoot) {
    createApp(StudentDashboard).mount(studentRoot)
}