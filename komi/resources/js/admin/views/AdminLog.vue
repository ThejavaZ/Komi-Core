<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
      <h2 class="font-semibold text-gray-800">Historial de Actividad</h2>
      <select
        v-model="actionFilter"
        class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
        @change="fetchLogs"
      >
        <option value="">Todas las acciones</option>
        <option value="user.update">Editar usuario</option>
        <option value="user.warn">Advertir usuario</option>
        <option value="user.bulk.ban">Banear (bulk)</option>
        <option value="post.delete">Eliminar post</option>
        <option value="report.resolve">Resolver reporte</option>
        <option value="community.delete">Eliminar comunidad</option>
        <option value="appeal.resolve">Resolver apelación</option>
      </select>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-100 text-left text-gray-500">
            <th class="px-5 py-3 font-medium">Admin</th>
            <th class="px-5 py-3 font-medium">Acción</th>
            <th class="px-5 py-3 font-medium">Objetivo</th>
            <th class="px-5 py-3 font-medium">Detalle</th>
            <th class="px-5 py-3 font-medium">IP</th>
            <th class="px-5 py-3 font-medium">Fecha</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="6" class="px-5 py-8 text-center text-gray-400">Cargando...</td>
          </tr>
          <tr v-for="log in logs" :key="log.id" class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
            <td class="px-5 py-3">
              <p class="text-sm font-medium text-gray-700">{{ log.admin?.name || 'N/A' }}</p>
            </td>
            <td class="px-5 py-3">
              <span class="text-xs px-2 py-1 rounded-full" :class="actionBadge(log.action)">{{ log.action }}</span>
            </td>
            <td class="px-5 py-3 text-xs text-gray-500">
              {{ log.target_type?.split('\\').pop() || '—' }} #{{ log.target_id || '—' }}
            </td>
            <td class="px-5 py-3 max-w-xs">
              <p v-if="log.old_values" class="text-xs text-gray-400">Antes: {{ JSON.stringify(log.old_values) }}</p>
              <p v-if="log.new_values" class="text-xs text-gray-600">Ahora: {{ JSON.stringify(log.new_values) }}</p>
              <p v-if="!log.old_values && !log.new_values" class="text-xs text-gray-400">—</p>
            </td>
            <td class="px-5 py-3 text-xs text-gray-400">{{ log.ip_address || '—' }}</td>
            <td class="px-5 py-3 text-xs text-gray-400">{{ log.created_at }}</td>
          </tr>
          <tr v-if="!loading && logs.length === 0">
            <td colspan="6" class="px-5 py-8 text-center text-gray-400">No hay registros</td>
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
</template>

<script setup>
import { ref, onMounted } from 'vue';

const loading = ref(true);
const logs = ref([]);
const actionFilter = ref('');
const currentPage = ref(1);
const totalPages = ref(1);

async function fetchLogs(page = 1) {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (actionFilter.value) params.set('action', actionFilter.value);
    params.set('page', page);
    const res = await fetch(`/admin/api/logs?${params}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    logs.value = data.data || [];
    currentPage.value = data.current_page || 1;
    totalPages.value = data.last_page || 1;
  } catch (e) {
    console.error('Error:', e);
  } finally {
    loading.value = false;
  }
}

function actionBadge(action) {
  if (action?.includes('delete')) return 'bg-red-100 text-red-700';
  if (action?.includes('warn')) return 'bg-orange-100 text-orange-700';
  if (action?.includes('ban')) return 'bg-red-100 text-red-800';
  if (action?.includes('resolve')) return 'bg-green-100 text-green-700';
  if (action?.includes('update')) return 'bg-blue-100 text-blue-700';
  return 'bg-gray-100 text-gray-600';
}

function goPage(page) {
  if (page >= 1 && page <= totalPages.value) fetchLogs(page);
}

onMounted(() => fetchLogs());
</script>
