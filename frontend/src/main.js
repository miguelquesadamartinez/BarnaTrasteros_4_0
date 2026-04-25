import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import './style.css'

import Toast, { useToast } from 'vue-toastification'
import 'vue-toastification/dist/index.css'

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.use(Toast, {
	position: 'top-right',
	timeout: 3500,
	closeOnClick: true,
	pauseOnHover: true,
	draggable: true,
	showCloseButtonOnHover: false,
	hideProgressBar: false,
	closeButton: 'button',
	icon: true,
	rtl: false
})
app.mount('#app')
