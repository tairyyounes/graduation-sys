import { createApp } from 'vue'
import AdminDashboard from './components/AdminDashboard.vue'
import router from './router/admin'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'
import i18n from './i18n'

const adminRoot = document.getElementById('admin-dashboard')

if (adminRoot) {
    const app = createApp(AdminDashboard)
    app.use(router)
    app.use(i18n)
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
    app.mount(adminRoot)
}
