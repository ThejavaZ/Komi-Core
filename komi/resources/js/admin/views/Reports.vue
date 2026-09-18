<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
      <h2 class="font-semibold text-gray-800">Reportes</h2>
      <select
        v-model="statusFilter"
        class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
        @change="fetchReports"
      >
        <option value="">Todos</option>
        <option value="pending">Pendientes</option>
        <option value="resolved">Resueltos</option>
        <option value="dismissed">Descartados</option>
      </select>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-100 text-left text-gray-500">
            <th class="px-5 py-3 font-medium">ID</th>
            <th class="px-5 py-3 font-medium">Reportado por</th>
            <th class="px-5 py-3 font-medium">Tipo</th>
            <th class="px-5 py-3 font-medium">Razón</th>
            <th class="px-5 py-3 font-medium">Estado</th>
            <th class="px-5 py-3 font-medium">Fecha</th>
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
              <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ report.reportable_type?.split('\\').pop() }}</span>
            </td>
            <td class="px-5 py-3 text-gray-700 max-w-xs truncate">{{ report.reason }}</td>
            <td class="px-5 py-3">
              <span class="text-xs px-2 py-1 rounded-full" :class="statusBadge(report.status)">{{ report.status }}</span>
            </td>
            <td class="px-5 py-3 text-gray-400 text-xs">{{ report.created_at }}</td>
            <td class="px-5 py-3">
              <div v-if="report.status === 'pending'" class="flex gap-2">
                <button @click="resolveReport(report.id, 'resolved')" class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded hover:bg-green-100">Resolver</button>
                <button @click="resolveReport(report.id, 'dismissed')" class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded hover:bg-gray-200">Descartar</button>
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
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const loading = ref(true);
const reports = ref([]);
const statusFilter = ref('');

async function fetchReports() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (statusFilter.value) params.set('status', statusFilter.value);
    const res = await fetch(`/admin/api/reports?${params}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    reports.value = data.data || [];
  } catch (e) {
    console.error('Error fetching reports:', e);
  } finally {
    loading.value = false;
  }
}

async function resolveReport(id, status) {
  try {
    await fetch(`/admin/api/reports/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ status }),
    });
    fetchReports();
  } catch (e) {
    console.error('Error resolving report:', e);
  }
}

function statusBadge(status) {
  return { pending: 'bg-yellow-100 text-yellow-700', resolved: 'bg-green-100 text-green-700', dismissed: 'bg-gray-100 text-gray-600' }[status] || 'bg-gray-100 text-gray-600';
}

onMounted(fetchReports);
</script>
