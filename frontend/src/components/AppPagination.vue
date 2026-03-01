<template>
  <div v-if="lastPage > 1 || total > 0" class="pagination-wrapper">
    <div class="pagination-info">
      <span v-if="total > 0">
        Mostrando <strong>{{ from }}</strong>–<strong>{{ to }}</strong> de <strong>{{ total }}</strong> registros
      </span>
    </div>

    <div v-if="lastPage > 1" class="pagination-controls">
      <!-- Primera página -->
      <button
        class="page-btn"
        :disabled="currentPage <= 1"
        title="Primera página"
        @click="$emit('change', 1)"
      >«</button>

      <!-- Anterior -->
      <button
        class="page-btn"
        :disabled="currentPage <= 1"
        title="Página anterior"
        @click="$emit('change', currentPage - 1)"
      >‹</button>

      <!-- Páginas numeradas -->
      <template v-for="p in pages" :key="p">
        <span v-if="p === '...'" class="page-ellipsis">…</span>
        <button
          v-else
          class="page-btn"
          :class="{ 'page-btn--active': p === currentPage }"
          @click="$emit('change', p)"
        >{{ p }}</button>
      </template>

      <!-- Siguiente -->
      <button
        class="page-btn"
        :disabled="currentPage >= lastPage"
        title="Página siguiente"
        @click="$emit('change', currentPage + 1)"
      >›</button>

      <!-- Última página -->
      <button
        class="page-btn"
        :disabled="currentPage >= lastPage"
        title="Última página"
        @click="$emit('change', lastPage)"
      >»</button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  currentPage: { type: Number, required: true },
  lastPage:    { type: Number, required: true },
  total:       { type: Number, default: 0 },
  from:        { type: Number, default: 0 },
  to:          { type: Number, default: 0 },
})

defineEmits(['change'])

const pages = computed(() => {
  const n = props.lastPage
  const c = props.currentPage

  if (n <= 7) {
    return Array.from({ length: n }, (_, i) => i + 1)
  }

  if (c <= 4) {
    return [1, 2, 3, 4, 5, '...', n]
  }

  if (c >= n - 3) {
    return [1, '...', n - 4, n - 3, n - 2, n - 1, n]
  }

  return [1, '...', c - 1, c, c + 1, '...', n]
})
</script>
