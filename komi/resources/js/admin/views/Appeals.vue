<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
      <h2 class="font-semibold text-gray-800">Apelaciones</h2>
      <select
        v-model="statusFilter"
        class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
        @change="fetchAppeals"
      >
        <option value="">Todas</option>
        <option value="pending">Pendientes</option>
        <option value="approved">Aprobadas</option>
        <option value="rejected">Rechazadas</option>
      </select>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-100 text-left text-gray-500">
            <th class="px-5 py-3 font-medium">ID</th>
            <th class="px-5 py-3 font-medium">Usuario</th>
            <th class="px-5 py-3 font-medium">Tipo</th>
            <th class="px-5 py-3 font-medium">Razón</th>
            <th class="px-5 py-3 font-medium">Estado</th>
            <th class="px-5 py-3 font-medium">Revisado por</th>
            <th class="px-5 py-3 font-medium">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="7" class="px-5 py-8 text-center text-gray-400">Cargando...</td>
          </tr>
          <tr v-for="appeal in appeals" :key="appeal.id" class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
            <td class="px-5 py-3 text-gray-500">#{{ appeal.id }}</td>
            <td class="px-5 py-3">
              <div>
                <p class="text-sm font-medium text-gray-700">{{ appeal.user?.name || 'N/A' }}</p>
                <p class="text-xs text-gray-400">@{{ appeal.user?.username }}</p>
              </div>
            </td>
            <td class="px-5 py-3">
              <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ appeal.type }}</span>
            </td>
            <td class="px-5 py-3 max-w-xs">
              <p class="text-sm text-gray-700 truncate">{{ appeal.reason }}</p>
            </td>
            <td class="px-5 py-3">
              <span class="text-xs px-2 py-1 rounded-full" :class="statusBadge(appeal.status)">{{ appeal.status }}</span>
            </td>
            <td class="px-5 py-3 text-xs text-gray-400">{{ appeal.reviewer?.name || '—' }}</td>
            <td class="px-5 py-3">
              <div v-if="appeal.status === 'pending'" class="flex gap-2">
                <button @click="resolveAppeal(appeal.id, 'approved')" class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded hover:bg-green-100">Aprobar</button>
                <button @click="resolveAppeal(appeal.id, 'rejected')" class="text-xs bg-red-50 text-red-700 px-2 py-1 rounded hover:bg-red-100">Rechazar</button>
              </div>
              <span v-else class="text-xs text-gray-400">—</span>
            </td>
          </tr>
          <tr v-if="!loading && appeals.length === 0">
            <td colspan="7" class="px-5 py-8 text-center text-gray-400">No hay apelaciones</td>
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
const appeals = ref([]);
const statusFilter = ref('');
const currentPage = ref(1);
const totalPages = ref(1);

async function fetchAppeals(page = 1) {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (statusFilter.value) params.set('status', statusFilter.value);
    params.set('page', page);
    const res = await fetch(`/admin/api/appeals?${params}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    appeals.value = data.data || [];
    currentPage.value = data.current_page || 1;
    totalPages.value = data.last_page || 1;
  } catch (e) {
    console.error('Error:', e);
  } finally {
    loading.value = false;
  }
}

async function resolveAppeal(id, status) {
  try {
    await fetch(`/admin/api/appeals/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ status }),
    });
    fetchAppeals(currentPage.value);
  } catch (e) {
    console.error('Error:', e);
  }
}

function statusBadge(status) {
  return { pending: 'bg-yellow-100 text-yellow-700', approved: 'bg-green-100 text-green-700', rejected: 'bg-red-100 text-red-700' }[status] || 'bg-gray-100 text-gray-600';
}

function goPage(page) {
  if (page >= 1 && page <= totalPages.value) fetchAppeals(page);
}

onMounted(() => fetchAppeals());
</script>
