<template>
  <div id="app-root">
    <nav class="navbar">
      <router-link to="/" class="navbar-brand">BarnaTrasteros</router-link>
      <ul class="navbar-menu">
        <li><router-link to="/trasteros">Trasteros</router-link></li>
        <li><router-link to="/pisos">Pisos</router-link></li>
        <li><router-link to="/clientes">Clientes</router-link></li>
        <li><router-link to="/pagos">Pagos</router-link></li>
        <li><router-link to="/gastos">Gastos</router-link></li>
        <li><router-link to="/relatorios">Relatorios</router-link></li>
        <li class="nav-dropdown" :class="{ open: dropdownOpen }">
          <button class="nav-dropdown-toggle" @click="dropdownOpen = !dropdownOpen">⚙️ Mantenimiento ▾</button>
          <ul class="nav-dropdown-menu">
            <li><router-link to="/mantenimiento/tamanyo-trasteros" @click="dropdownOpen = false">📐 Tamaños de Trasteros</router-link></li>
            <li><router-link to="/mantenimiento/facturas" @click="dropdownOpen = false">🧾 Facturas del Mes</router-link></li>
            <li><router-link to="/mantenimiento/generar-pagos" @click="dropdownOpen = false">💰 Generar Pagos</router-link></li>
            <li><router-link to="/mantenimiento/backup" @click="dropdownOpen = false">🗄️ Backup BD</router-link></li>
          </ul>
        </li>
      </ul>
    </nav>
    <main class="main-content">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
const dropdownOpen = ref(false)
function handleOutsideClick(e) {
  if (!e.target.closest('.nav-dropdown')) dropdownOpen.value = false
}
onMounted(() => document.addEventListener('click', handleOutsideClick))
onUnmounted(() => document.removeEventListener('click', handleOutsideClick))
</script>
