<template>
  <div class="search-select-wrapper" ref="wrapperRef">
    <input
      class="search-select-input"
      type="text"
      :placeholder="placeholder"
      v-model="query"
      @focus="open = true"
      @input="open = true"
      :disabled="disabled"
      autocomplete="off"
    />
    <div v-if="open && !disabled" class="search-select-dropdown">
      <div
        v-if="allowClear && modelValue"
        class="search-select-option"
        @mousedown.prevent="select(null)"
      >
        — Sin asignar —
      </div>
      <div
        v-for="opt in filtered"
        :key="opt.value"
        class="search-select-option"
        :class="{ selected: opt.value === modelValue }"
        @mousedown.prevent="select(opt.value)"
      >
        {{ opt.label }}
      </div>
      <div v-if="filtered.length === 0" class="search-select-empty">
        Sin resultados
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  modelValue: { default: null },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Buscar...' },
  disabled: { type: Boolean, default: false },
  allowClear: { type: Boolean, default: true },
})

const emit = defineEmits(['update:modelValue'])

const query = ref('')
const open = ref(false)
const wrapperRef = ref(null)

const filtered = computed(() => {
  const q = query.value.toLowerCase()
  return props.options.filter((o) => o.label.toLowerCase().includes(q))
})

function select(val) {
  emit('update:modelValue', val)
  open.value = false
  const found = props.options.find((o) => o.value === val)
  query.value = found ? found.label : ''
}

function handleOutside(e) {
  if (wrapperRef.value && !wrapperRef.value.contains(e.target)) {
    open.value = false
    // Restaurar label si hay valor seleccionado
    const found = props.options.find((o) => o.value === props.modelValue)
    query.value = found ? found.label : ''
  }
}

watch(
  () => props.modelValue,
  (val) => {
    const found = props.options.find((o) => o.value === val)
    query.value = found ? found.label : ''
  },
  { immediate: true }
)

watch(
  () => props.options,
  () => {
    const found = props.options.find((o) => o.value === props.modelValue)
    query.value = found ? found.label : ''
  }
)

onMounted(() => document.addEventListener('click', handleOutside))
onBeforeUnmount(() => document.removeEventListener('click', handleOutside))
</script>
