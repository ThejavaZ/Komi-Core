<template>
  <DataTable
    :columns="columns"
    :items="appeals"
    :loading="loading"
    :pagination="pagination"
    :sort-key="sortKey"
    :sort-dir="sortDir"
    empty-text="No hay apelaciones"
    @sort="onSort"
    @page="onPage"
    @per-page="onPerPage"
  >
    <template #header>
      <div class="flex items-center gap-3">
        <h2 class="font-semibold text-gray-800">Apelaciones</h2>
        <select v-model="statusFilter" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" @change="fetchAppeals(1)">
          <option value="">Todas</option>
          <option value="pending">Pendientes</option>
          <option value="approved">Aprobadas</option>
          <option value="rejected">Rechazadas</option>
        </select>
      </div>
    </template>
    <template #cell-user="{ item }">
      <div>
        <p class="text-sm font-medium text-gray-700">{{ item.user?.name || 'N/A' }}</p>
        <p class="text-xs text-gray-400">@{{ item.user?.username }}</p>
      </div>
    </template>
    <template #cell-type="{ value }">
      <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ value }}</span>
    </template>
    <template #cell-reason="{ value }">
      <p class="text-sm text-gray-700 truncate max-w-xs">{{ value }}</p>
    </template>
    <template #cell-status="{ value }">
      <span class="text-xs px-2 py-1 rounded-full" :class="statusBadge(value)">{{ value }}</span>
    </template>
    <template #cell-reviewer="{ item }">
      {{ item.reviewer?.name || '--' }}
    </template>
    <template #cell-actions="{ item }">
      <div v-if="item.status === 'pending'" class="flex gap-2">
        <button @click="resolveAppeal(item.id, 'approved')" class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded hover:bg-green-100">Aprobar</button>
        <button @click="resolveAppeal(item.id, 'rejected')" class="text-xs bg-red-50 text-red-700 px-2 py-1 rounded hover:bg-red-100">Rechazar</button>
      </div>
      <span v-else class="text-xs text-gray-400">--</span>
    </template>
  </DataTable>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DataTable from '../components/DataTable.vue';

const loading = ref(true);
const appeals = ref([]);
const pagination = ref(null);
const sortKey = ref('created_at');
const sortDir = ref('desc');
const statusFilter = ref('');

const columns = [
  { key: 'id', label: 'ID' },
  { key: 'user', label: 'Usuario', sortable: false },
  { key: 'type', label: 'Tipo' },
  { key: 'reason', label: 'Razon', sortable: false },
  { key: 'status', label: 'Estado' },
  { key: 'reviewer', label: 'Revisado por', sortable: false },
  { key: 'actions', label: 'Acciones', sortable: false },
];

async function fetchAppeals(page = 1, perPage = 15) {
  loading.value = true;
  try {
    const params = new URLSearchParams({ page, per_page: perPage, sort: sortKey.value, direction: sortDir.value });
    if (statusFilter.value) params.set('status', statusFilter.value);
    const res = await fetch(`/admin/api/appeals?${params}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    appeals.value = data.data || [];
    pagination.value = { current_page: data.current_page, last_page: data.last_page, per_page: data.per_page, total: data.total };
  } catch (e) {
    console.error('Error:', e);
  } finally {
    loading.value = false;
  }
}

async function resolveAppeal(id, status) {
  await fetch(`/admin/api/appeals/${id}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    body: JSON.stringify({ status }),
  });
  fetchAppeals(pagination.value?.current_page || 1, pagination.value?.per_page || 15);
}

function onSort({ key, dir }) { sortKey.value = key; sortDir.value = dir; fetchAppeals(pagination.value?.current_page || 1, pagination.value?.per_page || 15); }
function onPage(p) { fetchAppeals(p, pagination.value?.per_page || 15); }
function onPerPage(p) { fetchAppeals(1, p); }

function statusBadge(status) {
  return { pending: 'bg-yellow-100 text-yellow-700', approved: 'bg-green-100 text-green-700', rejected: 'bg-red-100 text-red-700' }[status] || 'bg-gray-100 text-gray-600';
}

onMounted(() => fetchAppeals());
</script>
