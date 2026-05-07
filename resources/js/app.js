import { createApp } from 'vue'
import App from './App.vue'

const appRoot = document.getElementById('app')

if (appRoot) {
    createApp(App).mount(appRoot)
}