<template>
  <DataTable
    :columns="columns"
    :items="reports"
    :loading="loading"
    :pagination="pagination"
    :sort-key="sortKey"
    :sort-dir="sortDir"
    empty-text="No hay reportes"
    @sort="onSort"
    @page="onPage"
    @per-page="onPerPage"
  >
    <template #header>
      <div class="flex items-center gap-3">
        <h2 class="font-semibold text-gray-800">Reportes</h2>
        <select v-model="statusFilter" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" @change="fetchReports(1)">
          <option value="">Todos</option>
          <option value="pending">Pendientes</option>
          <option value="resolved">Resueltos</option>
          <option value="dismissed">Descartados</option>
        </select>
      </div>
    </template>
    <template #cell-status="{ value }">
      <span class="text-xs px-2 py-1 rounded-full" :class="statusBadge(value)">{{ value }}</span>
    </template>
    <template #cell-reporter="{ item }">
      {{ item.reporter?.name || '—' }}
    </template>
    <template #cell-actions="{ item }">
      <div class="flex gap-1">
        <button v-if="item.status === 'pending'" @click="resolveReport(item.id, 'resolved')" class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded hover:bg-green-100">Resolver</button>
        <button v-if="item.status === 'pending'" @click="resolveReport(item.id, 'dismissed')" class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded hover:bg-gray-200">Descartar</button>
      </div>
    </template>
  </DataTable>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DataTable from '../components/DataTable.vue';

const loading = ref(true);
const reports = ref([]);
const pagination = ref(null);
const sortKey = ref('created_at');
const sortDir = ref('desc');
const statusFilter = ref('');

const columns = [
  { key: 'id', label: 'ID' },
  { key: 'reporter', label: 'Reportado por', sortable: false },
  { key: 'reportable_type', label: 'Tipo' },
  { key: 'reason', label: 'Razón' },
  { key: 'status', label: 'Estado' },
  { key: 'created_at', label: 'Fecha' },
  { key: 'actions', label: 'Acciones', sortable: false },
];

async function fetchReports(page = 1, perPage = 15) {
  loading.value = true;
  try {
    const params = new URLSearchParams({ page, per_page: perPage, sort: sortKey.value, direction: sortDir.value });
    if (statusFilter.value) params.set('status', statusFilter.value);
    const res = await fetch(`/admin/api/reports?${params}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    reports.value = data.data || [];
    pagination.value = { current_page: data.current_page, last_page: data.last_page, per_page: data.per_page, total: data.total };
  } catch (e) {
    console.error('Error:', e);
  } finally {
    loading.value = false;
  }
}

async function resolveReport(id, status) {
  await fetch(`/admin/api/reports/${id}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    body: JSON.stringify({ status }),
  });
  fetchReports(pagination.value?.current_page || 1, pagination.value?.per_page || 15);
}

function onSort({ key, dir }) { sortKey.value = key; sortDir.value = dir; fetchReports(pagination.value?.current_page || 1, pagination.value?.per_page || 15); }
function onPage(p) { fetchReports(p, pagination.value?.per_page || 15); }
function onPerPage(p) { fetchReports(1, p); }

function statusBadge(status) {
  const map = { pending: 'bg-yellow-100 text-yellow-700', resolved: 'bg-green-100 text-green-700', dismissed: 'bg-gray-100 text-gray-600' };
  return map[status] || 'bg-gray-100 text-gray-600';
}

onMounted(() => fetchReports());
</script>
