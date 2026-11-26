<template>
  <div class="not-found">
    <div class="not-found-code">404</div>
    <h1>Página no encontrada</h1>
    <p>La dirección <code>{{ route.fullPath }}</code> no existe en BarnaTrasteros.</p>
    <router-link to="/" class="btn btn-primary">Volver al Panel de Control</router-link>
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
const route = useRoute()
</script>

<style scoped>
.not-found {
  max-width: 520px;
  margin: 4rem auto;
  text-align: center;
  background: #fff;
  border-radius: var(--radio);
  box-shadow: var(--sombra);
  padding: 2.5rem 1.5rem;
}
.not-found-code {
  font-size: 4rem;
  font-weight: 800;
  color: var(--rojo);
  line-height: 1;
  margin-bottom: .5rem;
}
.not-found h1 { font-size: 1.4rem; margin-bottom: .75rem; }
.not-found p { color: var(--gris-texto); margin-bottom: 1.5rem; word-break: break-all; }
</style>
