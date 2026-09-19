<template>
  <DataTable
    :columns="columns"
    :items="logs"
    :loading="loading"
    :pagination="pagination"
    :sort-key="sortKey"
    :sort-dir="sortDir"
    empty-text="No hay registros"
    @sort="onSort"
    @page="onPage"
    @per-page="onPerPage"
  >
    <template #header>
      <div class="flex items-center gap-3">
        <h2 class="font-semibold text-gray-800">Historial de Actividad</h2>
        <select v-model="actionFilter" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" @change="fetchLogs(1)">
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
    </template>
    <template #cell-admin_name="{ item }">
      <p class="text-sm font-medium text-gray-700">{{ item.admin?.name || 'N/A' }}</p>
    </template>
    <template #cell-action="{ value }">
      <span class="text-xs px-2 py-1 rounded-full" :class="actionBadge(value)">{{ value }}</span>
    </template>
    <template #cell-target_type="{ item }">
      {{ item.target_type?.split('\\').pop() || '—' }} #{{ item.target_id || '—' }}
    </template>
    <template #cell-detail="{ item }">
      <div class="max-w-xs">
        <p v-if="item.old_values" class="text-xs text-gray-400">Antes: {{ JSON.stringify(item.old_values) }}</p>
        <p v-if="item.new_values" class="text-xs text-gray-600">Ahora: {{ JSON.stringify(item.new_values) }}</p>
        <p v-if="!item.old_values && !item.new_values" class="text-xs text-gray-400">—</p>
      </div>
    </template>
  </DataTable>
</template>

<script setup>
import { showErrorToast } from '../api';
import { ref, onMounted } from 'vue';
import DataTable from '../components/DataTable.vue';

const loading = ref(true);
const logs = ref([]);
const pagination = ref(null);
const sortKey = ref('created_at');
const sortDir = ref('desc');
const actionFilter = ref('');

const columns = [
  { key: 'admin_name', label: 'Admin', sortable: false },
  { key: 'action', label: 'Acción' },
  { key: 'target_type', label: 'Objetivo' },
  { key: 'detail', label: 'Detalle', sortable: false },
  { key: 'ip_address', label: 'IP' },
  { key: 'created_at', label: 'Fecha' },
];

async function fetchLogs(page = 1, perPage = 15) {
  loading.value = true;
  try {
    const params = new URLSearchParams({ page, per_page: perPage, sort: sortKey.value, direction: sortDir.value });
    if (actionFilter.value) params.set('action', actionFilter.value);
    const res = await fetch(`/admin/api/logs?${params}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    logs.value = data.data || [];
    pagination.value = { current_page: data.current_page, last_page: data.last_page, per_page: data.per_page, total: data.total };
  } catch (e) {
    showErrorToast(e, 'AdminLog');
  } finally {
    loading.value = false;
  }
}

function onSort({ key, dir }) { sortKey.value = key; sortDir.value = dir; fetchLogs(pagination.value?.current_page || 1, pagination.value?.per_page || 15); }
function onPage(p) { fetchLogs(p, pagination.value?.per_page || 15); }
function onPerPage(p) { fetchLogs(1, p); }

function actionBadge(action) {
  if (action?.includes('delete')) return 'bg-red-100 text-red-700';
  if (action?.includes('warn')) return 'bg-orange-100 text-orange-700';
  if (action?.includes('ban')) return 'bg-red-100 text-red-800';
  if (action?.includes('resolve')) return 'bg-green-100 text-green-700';
  if (action?.includes('update')) return 'bg-blue-100 text-blue-700';
  return 'bg-gray-100 text-gray-600';
}

onMounted(() => fetchLogs());
</script>
