<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
      <h2 class="font-semibold text-gray-800">Cola de Moderación</h2>
      <div class="flex gap-2">
        <select
          v-model="statusFilter"
          class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
          @change="fetchReports"
        >
          <option value="pending">Pendientes</option>
          <option value="resolved">Resueltos</option>
          <option value="dismissed">Descartados</option>
        </select>
        <select
          v-model="typeFilter"
          class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
          @change="fetchReports"
        >
          <option value="">Todos los tipos</option>
          <option value="App\Models\Post">Posts</option>
          <option value="App\Models\Comment">Comentarios</option>
        </select>
      </div>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-100 text-left text-gray-500">
            <th class="px-5 py-3 font-medium">ID</th>
            <th class="px-5 py-3 font-medium">Reportado por</th>
            <th class="px-5 py-3 font-medium">Tipo</th>
            <th class="px-5 py-3 font-medium">Contenido</th>
            <th class="px-5 py-3 font-medium">Razón</th>
            <th class="px-5 py-3 font-medium">Estado</th>
            <th class="px-5 py-3 font-medium">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="7" class="px-5 py-8 text-center text-gray-400">Cargando...</td>
          </tr>
          <tr v-for="report in reports" :key="report.id" class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
            <td class="px-5 py-3 text-gray-500">#{{ report.id }}</td>
            <td class="px-5 py-3 text-gray-700">{{ report.reporter?.name || 'N/A' }}</td>
            <td class="px-5 py-3">
              <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ typeName(report.reportable_type) }}</span>
            </td>
            <td class="px-5 py-3 max-w-xs">
              <p class="text-sm text-gray-700 truncate">{{ report.reportable?.content || 'Contenido eliminado' }}</p>
              <p v-if="report.reportable?.user" class="text-xs text-gray-400 mt-0.5">por @{{ report.reportable.user.username }}</p>
            </td>
            <td class="px-5 py-3">
              <p class="text-sm text-gray-700">{{ report.reason }}</p>
              <p v-if="report.description" class="text-xs text-gray-400 mt-0.5">{{ report.description }}</p>
            </td>
            <td class="px-5 py-3">
              <span class="text-xs px-2 py-1 rounded-full" :class="statusBadge(report.status)">{{ report.status }}</span>
            </td>
            <td class="px-5 py-3">
              <div v-if="report.status === 'pending'" class="flex gap-1 flex-wrap">
                <button @click="moderate(report, 'delete_post')" class="text-xs bg-red-50 text-red-700 px-2 py-1 rounded hover:bg-red-100">Eliminar</button>
                <button @click="moderate(report, 'warn_user')" class="text-xs bg-orange-50 text-orange-700 px-2 py-1 rounded hover:bg-orange-100">Advertir</button>
                <button @click="moderate(report, 'ban_user')" class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded hover:bg-red-200">Banear</button>
                <button @click="moderate(report, 'dismiss')" class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded hover:bg-gray-200">Descartar</button>
              </div>
              <span v-else class="text-xs text-gray-400">—</span>
            </td>
          </tr>
          <tr v-if="!loading && reports.length === 0">
            <td colspan="7" class="px-5 py-8 text-center text-gray-400">No hay reportes</td>
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
const reports = ref([]);
const statusFilter = ref('pending');
const typeFilter = ref('');
const currentPage = ref(1);
const totalPages = ref(1);

async function fetchReports(page = 1) {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    params.set('status', statusFilter.value);
    if (typeFilter.value) params.set('type', typeFilter.value);
    params.set('page', page);
    const res = await fetch(`/admin/api/moderation?${params}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    reports.value = data.data || [];
    currentPage.value = data.current_page || 1;
    totalPages.value = data.last_page || 1;
  } catch (e) {
    console.error('Error:', e);
  } finally {
    loading.value = false;
  }
}

async function moderate(report, action) {
  try {
    await fetch(`/admin/api/moderation/${report.id}/action`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ action }),
    });
    fetchReports(currentPage.value);
  } catch (e) {
    console.error('Error:', e);
  }
}

function typeName(type) {
  if (!type) return 'N/A';
  return type.split('\\').pop();
}

function statusBadge(status) {
  return { pending: 'bg-yellow-100 text-yellow-700', resolved: 'bg-green-100 text-green-700', dismissed: 'bg-gray-100 text-gray-600' }[status] || 'bg-gray-100 text-gray-600';
}

function goPage(page) {
  if (page >= 1 && page <= totalPages.value) fetchReports(page);
}

onMounted(() => fetchReports());
</script>
