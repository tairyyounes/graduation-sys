import { createApp } from 'vue'
import DepartmentDashboard from './components/DepartmentDashboard.vue'
import router from './router/department'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'

const departmentRoot = document.getElementById('department-dashboard')

if (departmentRoot) {
    const app = createApp(DepartmentDashboard)
    app.use(router)
    app.use(Toast, {
      position: 'top-right',
      timeout: 3000,
      closeOnClick: true,
      pauseOnFocusLoss: true,
      pauseOnHover: true,
      draggable: true,
      draggablePercent: 0.6,
      showCloseButtonOnHover: false,
      hideProgressBar: true,
      closeButton: 'button',
      icon: true,
      rtl: false
    })
    app.mount(departmentRoot)
}
