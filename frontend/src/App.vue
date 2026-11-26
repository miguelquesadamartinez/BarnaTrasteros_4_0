<template>
  <div id="app-root">
    <nav class="navbar">
      <router-link to="/" class="navbar-brand">BarnaTrasteros</router-link>
      <button
        class="navbar-toggle"
        type="button"
        :aria-expanded="mobileOpen"
        aria-label="Abrir menú"
        @click.stop="mobileOpen = !mobileOpen"
      >{{ mobileOpen ? '✕' : '☰' }}</button>
      <ul class="navbar-menu" :class="{ 'mobile-open': mobileOpen }">
        <li><router-link to="/busqueda">🔍 Búsqueda</router-link></li>
        <li class="nav-dropdown" :class="{ open: openMenu === 'inmuebles' }" @mouseenter="openDropdown('inmuebles')" @mouseleave="scheduleClose">
          <button class="nav-dropdown-toggle" @click="toggleMenu('inmuebles')">🏢 Inmuebles y Clientes ▾</button>
          <ul class="nav-dropdown-menu">
            <li><router-link to="/trasteros" @click="openMenu = null">📦 Trasteros</router-link></li>
            <li><router-link to="/pisos" @click="openMenu = null">🏠 Pisos</router-link></li>
            <li><router-link to="/clientes" @click="openMenu = null">👥 Clientes</router-link></li>
            <li><router-link to="/lista-espera" @click="openMenu = null">⏳ Lista de Espera</router-link></li>
          </ul>
        </li>
        <li class="nav-dropdown" :class="{ open: openMenu === 'fianzas' }" @mouseenter="openDropdown('fianzas')" @mouseleave="scheduleClose">
          <button class="nav-dropdown-toggle" @click="toggleMenu('fianzas')">💰 Fianzas ▾</button>
          <ul class="nav-dropdown-menu">
            <li><router-link to="/fianzas" @click="openMenu = null">✅ Fianzas Activas</router-link></li>
            <li><router-link to="/fianzas/devueltas" @click="openMenu = null">↩️ Fianzas Devueltas</router-link></li>
          </ul>
        </li>
        <li class="nav-dropdown" :class="{ open: openMenu === 'contabilidad' }" @mouseenter="openDropdown('contabilidad')" @mouseleave="scheduleClose">
          <button class="nav-dropdown-toggle" @click="toggleMenu('contabilidad')">📒 Contabilidad ▾</button>
          <ul class="nav-dropdown-menu">
            <li><router-link to="/pagos" @click="openMenu = null">💳 Pagos</router-link></li>
            <li><router-link to="/gastos" @click="openMenu = null">💸 Gastos</router-link></li>
            <li><router-link to="/mantenimiento/facturas" @click="openMenu = null">🧾 Facturas</router-link></li>
            <li><router-link to="/contabilidad/avisar-impagos" @click="openMenu = null">📣 Avisar Impagos</router-link></li>
          </ul>
        </li>
        <li><router-link to="/relatorios">📊 Relatorios</router-link></li>
        <li class="nav-dropdown" :class="{ open: openMenu === 'mantenimiento' }" @mouseenter="openDropdown('mantenimiento')" @mouseleave="scheduleClose">
          <button class="nav-dropdown-toggle" @click="toggleMenu('mantenimiento')">⚙️ Mantenimiento ▾</button>
          <ul class="nav-dropdown-menu">
            <li><router-link to="/mantenimiento/tamanyo-trasteros" @click="openMenu = null">📐 Tamaños de Trasteros</router-link></li>
            <li><router-link to="/mantenimiento/clientes-archivados" @click="openMenu = null">🗄️ Clientes Archivados</router-link></li>
            <li><router-link to="/mantenimiento/generar-pagos" @click="openMenu = null">🧮 Generar Pagos</router-link></li>
            <li><router-link to="/mantenimiento/revision-precio" @click="openMenu = null">📈 Revisión de Precio</router-link></li>
            <li><router-link to="/mantenimiento/historial-precios" @click="openMenu = null">🕘 Historial de Precios</router-link></li>
            <li><router-link to="/mantenimiento/backup" @click="openMenu = null">💾 Copias de seguridad</router-link></li>
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
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
const openMenu = ref(null)
// En pantallas estrechas el menú no cabía en la barra (quedaba fuera de la vista):
// se agrupa tras un botón ☰ y se cierra al navegar.
const mobileOpen = ref(false)
const route = useRoute()
watch(() => route.fullPath, () => {
  mobileOpen.value = false
  openMenu.value = null
})
let closeTimer = null

// En pantallas táctiles el toque dispara mouseenter y luego click: el hover abría el
// submenú y el click (toggle) lo volvía a cerrar. El hover solo aplica con ratón.
const conRaton = () => window.matchMedia('(hover: hover) and (pointer: fine)').matches
function openDropdown(key) {
  if (!conRaton()) return
  clearTimeout(closeTimer)
  openMenu.value = key
}
function scheduleClose() {
  if (!conRaton()) return
  clearTimeout(closeTimer)
  closeTimer = setTimeout(() => { openMenu.value = null }, 250)
}
function toggleMenu(key) {
  clearTimeout(closeTimer)
  openMenu.value = openMenu.value === key ? null : key
}
function handleOutsideClick(e) {
  if (!e.target.closest('.nav-dropdown')) {
    clearTimeout(closeTimer)
    openMenu.value = null
  }
  if (!e.target.closest('.navbar')) {
    mobileOpen.value = false
  }
}
onMounted(() => document.addEventListener('click', handleOutsideClick))
onUnmounted(() => document.removeEventListener('click', handleOutsideClick))
</script>
