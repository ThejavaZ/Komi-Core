<template>
  <div class="space-y-6">
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">Base de datos</p>
        <p class="text-lg font-bold" :class="health.database === 'ok' ? 'text-green-600' : 'text-red-600'">{{ health.database === 'ok' ? 'Conectada' : 'Error' }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">Jobs pendientes</p>
        <p class="text-lg font-bold text-gray-900">{{ health.jobs_pending ?? '--' }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">Cache keys</p>
        <p class="text-lg font-bold text-gray-900">{{ health.cache_keys ?? '--' }}</p>
        <button @click="clearCache" :disabled="clearingCache" class="mt-2 text-xs bg-red-50 text-red-700 px-2 py-1 rounded hover:bg-red-100 disabled:opacity-40">{{ clearingCache ? 'Limpiando...' : 'Limpiar Cache' }}</button>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">Disco usado</p>
        <p class="text-lg font-bold text-gray-900">{{ health.disk_used_percent ?? '--' }}%</p>
        <p class="text-xs text-gray-400">{{ health.disk_free_gb }}GB libres</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">Entorno</p>
        <p class="text-lg font-bold text-gray-900">{{ health.app_env ?? '--' }}</p>
        <p class="text-xs text-gray-400">PHP {{ health.php_version }}</p>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="font-semibold text-gray-800">Jobs Pendientes</h2>
          <p class="text-sm text-gray-500 mt-1">{{ health.jobs_pending ?? '--' }} jobs en cola</p>
        </div>
        <router-link to="/admin/jobs" class="text-xs bg-indigo-50 text-indigo-700 px-3 py-1.5 rounded hover:bg-indigo-100">Ver jobs</router-link>
      </div>
    </div>

    <DataTable
      :columns="columns"
      :items="errors"
      :loading="loadingErrors"
      :pagination="errorPagination"
      :sort-key="sortKey"
      :sort-dir="sortDir"
      searchable
      search-placeholder="Buscar errores..."
      empty-text="No hay errores"
      @sort="onSort"
      @page="onPage"
      @per-page="onPerPage"
      @search="onSearch"
    >
      <template #header>
        <h2 class="font-semibold text-gray-800">Errores de la App</h2>
      </template>
      <template #cell-message="{ item }">
        <div class="max-w-md">
          <p class="text-sm text-gray-700 truncate">{{ item.message }}</p>
          <p v-if="item.stack_trace" class="text-xs text-gray-400 mt-1 font-mono truncate">{{ item.stack_trace }}</p>
        </div>
      </template>
      <template #cell-occurrences_count="{ value }">
        <span class="text-xs px-2 py-1 rounded-full bg-red-100 text-red-700">{{ value }}</span>
      </template>
      <template #cell-app_version="{ value }">
        {{ value || '--' }}
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DataTable from '../components/DataTable.vue';
import { api } from '../api';

const loadingHealth = ref(true);
const loadingErrors = ref(true);
const clearingCache = ref(false);
const health = ref({});
const errors = ref([]);
const errorPagination = ref(null);
const sortKey = ref('last_seen_at');
const sortDir = ref('desc');
let searchVal = '';

const columns = [
  { key: 'message', label: 'Mensaje', sortable: false },
  { key: 'occurrences_count', label: 'Ocurrencias' },
  { key: 'app_version', label: 'Version' },
  { key: 'last_seen_at', label: 'Ultima vez' },
];

async function fetchHealth() {
  loadingHealth.value = true;
  try {
    const res = await api('/admin/api/system/health');
    health.value = await res.json();
  } catch (e) { console.error('Error:', e); } finally { loadingHealth.value = false; }
}

async function fetchErrors(page = 1, perPage = 15) {
  loadingErrors.value = true;
  try {
    const params = new URLSearchParams({ page, per_page: perPage, sort: sortKey.value, direction: sortDir.value });
    if (searchVal) params.set('search', searchVal);
    const res = await api(`/admin/api/system/errors?${params}`);
    const data = await res.json();
    errors.value = data.data || [];
    errorPagination.value = { current_page: data.current_page, last_page: data.last_page, per_page: data.per_page, total: data.total };
  } catch (e) { console.error('Error:', e); } finally { loadingErrors.value = false; }
}

function onSearch(val) { searchVal = val; fetchErrors(1, errorPagination.value?.per_page || 15); }
function onSort({ key, dir }) { sortKey.value = key; sortDir.value = dir; fetchErrors(errorPagination.value?.current_page || 1, errorPagination.value?.per_page || 15); }
function onPage(p) { fetchErrors(p, errorPagination.value?.per_page || 15); }
function onPerPage(p) { fetchErrors(1, p); }

async function clearCache() {
  clearingCache.value = true;
  try {
    await api('/admin/api/system/cache/clear', { method: 'POST' });
    fetchHealth();
  } catch (e) { console.error('Error:', e); } finally { clearingCache.value = false; }
}

onMounted(() => { fetchHealth(); fetchErrors(); });
</script>
