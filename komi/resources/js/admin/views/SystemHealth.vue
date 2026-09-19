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
        <button @click="showClearCacheDialog = true" :disabled="clearingCache" class="mt-2 text-xs bg-red-50 text-red-700 px-2 py-1 rounded hover:bg-red-100 disabled:opacity-40 flex items-center gap-1"><TrashIcon class="w-3.5 h-3.5"/> {{ clearingCache ? 'Limpiando...' : 'Limpiar Cache' }}</button>
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

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="p-5 pb-0">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h2 class="font-semibold text-gray-800">Errores de la App</h2>
            <p class="text-sm text-gray-500 mt-1">{{ errorPagination?.total ?? 0 }} errores registrados</p>
          </div>
          <input v-model="searchVal" @input="debouncedSearch" type="text" placeholder="Buscar errores..." class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-300" />
        </div>
      </div>

      <div v-if="loadingErrors" class="p-8 text-center text-gray-400">Cargando errores...</div>
      <div v-else-if="errors.length === 0" class="p-8 text-center text-gray-400">No hay errores registrados</div>
      <div v-else>
        <div v-for="error in errors" :key="error.id" class="border-t border-gray-100">
          <div @click="toggleExpand(error.id)" class="flex items-center gap-4 px-5 py-3 cursor-pointer hover:bg-gray-50 transition-colors">
            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-red-100 text-red-700 shrink-0">{{ error.occurrences_count }}x</span>
            <div class="flex-1 min-w-0">
              <p class="text-sm text-gray-800 font-medium truncate">{{ error.message }}</p>
              <p v-if="error.stack_trace" class="text-xs text-gray-400 mt-0.5 font-mono truncate">{{ error.stack_trace }}</p>
            </div>
            <span class="text-xs text-gray-400 shrink-0">{{ error.app_version || '--' }}</span>
            <span class="text-xs text-gray-400 shrink-0">{{ formatDate(error.last_seen_at) }}</span>
            <button @click.stop="copyError(error)" class="text-xs bg-blue-50 text-blue-700 px-2.5 py-1 rounded hover:bg-blue-100 shrink-0 flex items-center gap-1">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
              Copiar
            </button>
            <button class="text-gray-400 shrink-0">
              <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': expandedId === error.id }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
          </div>
          <div v-if="expandedId === error.id" class="px-5 pb-4 bg-gray-50 border-t border-gray-100">
            <div class="mt-3 space-y-3">
              <div>
                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Mensaje</p>
                <p class="text-sm text-gray-800 whitespace-pre-wrap break-all">{{ error.message }}</p>
              </div>
              <div v-if="error.stack_trace">
                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Stack Trace</p>
                <pre class="text-xs text-gray-600 bg-white p-3 rounded-lg border border-gray-200 overflow-x-auto whitespace-pre-wrap break-all font-mono max-h-64 overflow-y-auto">{{ error.stack_trace }}</pre>
              </div>
              <div class="flex items-center gap-4 text-xs text-gray-500">
                <span>Ocurrencias: <strong class="text-gray-800">{{ error.occurrences_count }}</strong></span>
                <span>Version: <strong class="text-gray-800">{{ error.app_version || 'N/A' }}</strong></span>
                <span>Hash: <code class="text-gray-600">{{ error.error_hash }}</code></span>
                <span>Ultima vez: <strong class="text-gray-800">{{ formatDate(error.last_seen_at) }}</strong></span>
              </div>
              <button @click="copyError(error)" class="text-xs bg-blue-600 text-white px-4 py-1.5 rounded-lg hover:bg-blue-700 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                Copiar error completo
              </button>
            </div>
          </div>
        </div>

        <div v-if="errorPagination && errorPagination.last_page > 1" class="flex items-center justify-between px-5 py-3 border-t border-gray-100">
          <p class="text-xs text-gray-500">Pagina {{ errorPagination.current_page }} de {{ errorPagination.last_page }} ({{ errorPagination.total }} errores)</p>
          <div class="flex gap-1">
            <button v-for="p in visiblePages" :key="p" @click="fetchErrors(p, errorPagination.per_page)" class="text-xs px-2.5 py-1 rounded" :class="p === errorPagination.current_page ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">{{ p }}</button>
          </div>
        </div>
      </div>
    </div>

    <ConfirmDialog v-model="showClearCacheDialog" title="Limpiar cache" subtitle="Se eliminaran todas las claves de cache de la aplicacion." :icon="TrashIcon" confirm-text="Limpiar" @confirm="clearCache"/>
  </div>
</template>
<script setup>
import { showErrorToast } from '../api';
import { ref, computed, onMounted } from 'vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import { api } from '../api';
import { TrashIcon } from '@heroicons/vue/24/outline';

const loadingHealth = ref(true);
const loadingErrors = ref(true);
const clearingCache = ref(false);
const health = ref({});
const errors = ref([]);
const errorPagination = ref(null);
const searchVal = ref('');
const expandedId = ref(null);
const showClearCacheDialog = ref(false);
let searchTimeout = null;

const visiblePages = computed(() => {
  if (!errorPagination.value) return [];
  const { current_page, last_page } = errorPagination.value;
  const pages = [];
  const start = Math.max(1, current_page - 2);
  const end = Math.min(last_page, current_page + 2);
  for (let i = start; i <= end; i++) pages.push(i);
  return pages;
});

function toggleExpand(id) {
  expandedId.value = expandedId.value === id ? null : id;
}

function formatDate(dateStr) {
  if (!dateStr) return '--';
  const d = new Date(dateStr);
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function copyError(error) {
  const parts = [
    '[Error] ' + error.message,
    error.stack_trace ? '[Stack Trace]\n' + error.stack_trace : '',
    '[Ocurrencias] ' + error.occurrences_count,
    '[Version App] ' + (error.app_version || 'N/A'),
    '[Hash] ' + (error.error_hash || 'N/A'),
    '[Ultima vez] ' + formatDate(error.last_seen_at),
  ].filter(Boolean).join('\n\n');

  navigator.clipboard.writeText(parts).then(() => {
    const toast = document.createElement('div');
    toast.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:99999;background:#22c55e;color:white;padding:10px 20px;border-radius:8px;font-size:13px;font-weight:bold;box-shadow:0 4px 12px rgba(0,0,0,0.3);';
    toast.textContent = 'Error copiado al portapapeles';
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2000);
  });
}

function debouncedSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => fetchErrors(1, errorPagination.value?.per_page || 15), 300);
}

async function fetchHealth() {
  loadingHealth.value = true;
  try {
    const res = await api('/admin/api/system/health');
    health.value = await res.json();
  } catch (e) { showErrorToast(e, 'SystemHealth'); } finally { loadingHealth.value = false; }
}

async function fetchErrors(page = 1, perPage = 15) {
  loadingErrors.value = true;
  try {
    const params = new URLSearchParams({ page, per_page: perPage, sort: 'last_seen_at', direction: 'desc' });
    if (searchVal.value) params.set('search', searchVal.value);
    const res = await api(`/admin/api/system/errors?${params}`);
    const data = await res.json();
    errors.value = data.data || [];
    errorPagination.value = { current_page: data.current_page, last_page: data.last_page, per_page: data.per_page, total: data.total };
  } catch (e) { showErrorToast(e, 'SystemHealth'); } finally { loadingErrors.value = false; }
}

async function clearCache() {
  clearingCache.value = true;
  try {
    await api('/admin/api/system/cache/clear', { method: 'POST' });
    fetchHealth();
  } catch (e) { showErrorToast(e, 'SystemHealth'); } finally { clearingCache.value = false; }
}

onMounted(() => { fetchHealth(); fetchErrors(); });
</script>
