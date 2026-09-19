<template>
  <div class="space-y-6">
    <!-- Health Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">Base de datos</p>
        <p class="text-lg font-bold" :class="health.database === 'ok' ? 'text-green-600' : 'text-red-600'">
          {{ health.database === 'ok' ? 'Conectada' : 'Error' }}
        </p>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">Jobs pendientes</p>
        <p class="text-lg font-bold text-gray-900">{{ health.jobs_pending ?? '—' }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">Caché keys</p>
        <p class="text-lg font-bold text-gray-900">{{ health.cache_keys ?? '—' }}</p>
        <button
          @click="clearCache"
          :disabled="clearingCache"
          class="mt-2 text-xs bg-red-50 text-red-700 px-2 py-1 rounded hover:bg-red-100 disabled:opacity-40"
        >
          {{ clearingCache ? 'Limpiando...' : 'Limpiar Caché' }}
        </button>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">Disco usado</p>
        <p class="text-lg font-bold text-gray-900">{{ health.disk_used_percent ?? '—' }}%</p>
        <p class="text-xs text-gray-400">{{ health.disk_free_gb }}GB libres</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">Entorno</p>
        <p class="text-lg font-bold text-gray-900">{{ health.app_env ?? '—' }}</p>
        <p class="text-xs text-gray-400">PHP {{ health.php_version }}</p>
      </div>
    </div>

    <!-- Jobs Pending -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="font-semibold text-gray-800">Jobs Pendientes</h2>
          <p class="text-sm text-gray-500 mt-1">{{ health.jobs_pending ?? '—' }} jobs en cola</p>
        </div>
        <router-link to="/admin/jobs" class="text-xs bg-indigo-50 text-indigo-700 px-3 py-1.5 rounded hover:bg-indigo-100">Ver jobs</router-link>
      </div>
    </div>

    <!-- Client Errors -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-800">Errores de la App</h2>
        <input
          v-model="search"
          type="text"
          placeholder="Buscar..."
          class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-48"
          @input="debouncedFetch"
        />
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 text-left text-gray-500">
              <th class="px-5 py-3 font-medium">Mensaje</th>
              <th class="px-5 py-3 font-medium">Ocurrencias</th>
              <th class="px-5 py-3 font-medium">Versión</th>
              <th class="px-5 py-3 font-medium">Última vez</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loadingErrors">
              <td colspan="4" class="px-5 py-8 text-center text-gray-400">Cargando...</td>
            </tr>
            <tr v-for="error in errors" :key="error.id" class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3 max-w-md">
                <p class="text-sm text-gray-700 truncate">{{ error.message }}</p>
                <p v-if="error.stack_trace" class="text-xs text-gray-400 mt-1 font-mono truncate">{{ error.stack_trace }}</p>
              </td>
              <td class="px-5 py-3">
                <span class="text-xs px-2 py-1 rounded-full bg-red-100 text-red-700">{{ error.occurrences_count }}</span>
              </td>
              <td class="px-5 py-3 text-xs text-gray-400">{{ error.app_version || '—' }}</td>
              <td class="px-5 py-3 text-xs text-gray-400">{{ error.last_seen_at }}</td>
            </tr>
            <tr v-if="!loadingErrors && errors.length === 0">
              <td colspan="4" class="px-5 py-8 text-center text-gray-400">No hay errores</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="totalPages > 1" class="px-5 py-3 border-t border-gray-100 flex items-center justify-between text-sm text-gray-500">
        <span>Página {{ currentPage }} de {{ totalPages }}</span>
        <div class="flex gap-2">
          <button @click="goPage(currentPage - 1)" :disabled="currentPage <= 1" class="px-3 py-1 rounded border border-gray-200 hover:bg-gray-50 disabled:opacity-40">Anterior</button>
          <button @click="goPage(currentPage + 1)" :disabled="currentPage >= totalPages" class="px-3 py-1 rounded border border-gray-200 hover:bg-gray-50 disabled:opacity-40">Siguiente</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const loadingHealth = ref(true);
const loadingErrors = ref(true);
const clearingCache = ref(false);
const health = ref({});
const errors = ref([]);
const search = ref('');
const currentPage = ref(1);
const totalPages = ref(1);
let debounceTimer = null;

async function fetchHealth() {
  loadingHealth.value = true;
  try {
    const res = await fetch('/admin/api/system/health', { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    health.value = await res.json();
  } catch (e) {
    console.error('Error:', e);
  } finally {
    loadingHealth.value = false;
  }
}

async function fetchErrors(page = 1) {
  loadingErrors.value = true;
  try {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    params.set('page', page);
    const res = await fetch(`/admin/api/system/errors?${params}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    errors.value = data.data || [];
    currentPage.value = data.current_page || 1;
    totalPages.value = data.last_page || 1;
  } catch (e) {
    console.error('Error:', e);
  } finally {
    loadingErrors.value = false;
  }
}

function debouncedFetch() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => fetchErrors(), 300);
}

function goPage(page) {
  if (page >= 1 && page <= totalPages.value) fetchErrors(page);
}

async function clearCache() {
  clearingCache.value = true;
  try {
    await fetch('/admin/api/system/cache/clear', {
      method: 'POST',
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    fetchHealth();
  } catch (e) {
    console.error('Error clearing cache:', e);
  } finally {
    clearingCache.value = false;
  }
}

onMounted(() => {
  fetchHealth();
  fetchErrors();
});
</script>
