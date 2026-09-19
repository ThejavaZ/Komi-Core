<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <!-- Header -->
    <div v-if="$slots.header || searchable || exportable" class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
      <slot name="header" />
      <div class="flex items-center gap-3">
        <a v-if="exportable" :href="exportUrl" class="text-xs bg-green-50 text-green-700 px-3 py-1.5 rounded hover:bg-green-100">Exportar CSV</a>
        <input
          v-if="searchable"
          v-model="search"
          type="text"
          :placeholder="searchPlaceholder"
          class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
          @input="$emit('search', search)"
        />
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-100 text-left text-gray-500">
            <th
              v-for="col in columns"
              :key="col.key"
              class="px-5 py-3 font-medium select-none"
              :class="[
                col.align === 'right' ? 'text-right' : '',
                col.sortable !== false ? 'cursor-pointer hover:text-gray-700' : '',
              ]"
              @click="col.sortable !== false && toggleSort(col.key)"
            >
              <div class="flex items-center gap-1.5" :class="col.align === 'right' ? 'justify-end' : ''">
                <span>{{ col.label }}</span>
                <template v-if="col.sortable !== false && sortKey === col.key">
                  <svg v-if="sortDir === 'asc'" class="w-3.5 h-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
                  <svg v-else class="w-3.5 h-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </template>
              </div>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td :colspan="columns.length" class="px-5 py-8 text-center text-gray-400">
              <div class="flex items-center justify-center gap-2">
                <svg class="animate-spin h-4 w-4 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                Cargando...
              </div>
            </td>
          </tr>
          <tr v-else-if="items.length === 0">
            <td :colspan="columns.length" class="px-5 py-8 text-center text-gray-400">{{ emptyText }}</td>
          </tr>
          <tr
            v-for="(item, idx) in items"
            :key="item.id || idx"
            class="border-b border-gray-50 hover:bg-gray-50 transition-colors"
          >
            <td
              v-for="col in columns"
              :key="col.key"
              class="px-5 py-3"
              :class="col.class || ''"
            >
              <slot :name="`cell-${col.key}`" :item="item" :value="item[col.key]">
                <span :class="col.textClass || 'text-gray-700'" v-html="item[col.key] ?? ''"></span>
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination && pagination.last_page > 1" class="px-5 py-3 border-t border-gray-100 flex items-center justify-between">
      <div class="text-xs text-gray-400">
        Mostrando {{ (pagination.current_page - 1) * pagination.per_page + 1 }}-{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} de {{ pagination.total }}
      </div>
      <div class="flex items-center gap-1.5">
        <!-- Per page -->
        <select
          :value="pagination.per_page"
          class="border border-gray-200 rounded px-1.5 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500"
          @change="$emit('per-page', Number($event.target.value))"
        >
          <option :value="10">10</option>
          <option :value="15">15</option>
          <option :value="25">25</option>
          <option :value="50">50</option>
          <option :value="100">100</option>
        </select>

        <!-- First -->
        <button
          class="px-2 py-1 rounded text-xs border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed"
          :disabled="pagination.current_page <= 1"
          @click="$emit('page', 1)"
        >
          &laquo;
        </button>

        <!-- Prev -->
        <button
          class="px-2 py-1 rounded text-xs border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed"
          :disabled="pagination.current_page <= 1"
          @click="$emit('page', pagination.current_page - 1)"
        >
          &lsaquo;
        </button>

        <!-- Page numbers -->
        <template v-for="p in visiblePages" :key="p">
          <span v-if="p === '...'" class="px-2 py-1 text-xs text-gray-400">...</span>
          <button
            v-else
            class="px-2.5 py-1 rounded text-xs border transition-colors"
            :class="p === pagination.current_page
              ? 'bg-indigo-600 text-white border-indigo-600'
              : 'border-gray-200 hover:bg-gray-50 text-gray-600'"
            @click="$emit('page', p)"
          >
            {{ p }}
          </button>
        </template>

        <!-- Next -->
        <button
          class="px-2 py-1 rounded text-xs border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed"
          :disabled="pagination.current_page >= pagination.last_page"
          @click="$emit('page', pagination.current_page + 1)"
        >
          &rsaquo;
        </button>

        <!-- Last -->
        <button
          class="px-2 py-1 rounded text-xs border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed"
          :disabled="pagination.current_page >= pagination.last_page"
          @click="$emit('page', pagination.last_page)"
        >
          &raquo;
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  columns: { type: Array, required: true },
  items: { type: Array, required: true },
  loading: { type: Boolean, default: false },
  pagination: { type: Object, default: null },
  sortKey: { type: String, default: '' },
  sortDir: { type: String, default: 'desc' },
  searchable: { type: Boolean, default: false },
  searchPlaceholder: { type: String, default: 'Buscar...' },
  exportable: { type: Boolean, default: false },
  exportUrl: { type: String, default: '' },
  emptyText: { type: String, default: 'No se encontraron registros' },
});

const emit = defineEmits(['sort', 'page', 'per-page', 'search']);

const search = ref('');

function toggleSort(key) {
  const dir = props.sortKey === key && props.sortDir === 'asc' ? 'desc' : 'asc';
  emit('sort', { key, dir });
}

const visiblePages = computed(() => {
  if (!props.pagination) return [];
  const { current_page, last_page } = props.pagination;
  if (last_page <= 7) return Array.from({ length: last_page }, (_, i) => i + 1);

  const pages = [];
  pages.push(1);
  if (current_page > 3) pages.push('...');
  for (let i = Math.max(2, current_page - 1); i <= Math.min(last_page - 1, current_page + 1); i++) {
    pages.push(i);
  }
  if (current_page < last_page - 2) pages.push('...');
  pages.push(last_page);
  return pages;
});
</script>
