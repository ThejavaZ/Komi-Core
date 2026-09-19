<template>
  <DataTable
    :columns="columns"
    :items="users"
    :loading="loading"
    :pagination="pagination"
    :sort-key="sortKey"
    :sort-dir="sortDir"
    searchable
    search-placeholder="Buscar usuarios..."
    exportable
    export-url="/admin/api/export/users"
    empty-text="No se encontraron usuarios"
    @sort="onSort"
    @page="onPage"
    @per-page="onPerPage"
    @search="onSearch"
  >
    <template #header>
      <h2 class="font-semibold text-gray-800">Gestión de Usuarios</h2>
    </template>
    <template #cell-status="{ value }">
      <span class="text-xs px-2 py-1 rounded-full" :class="statusBadge(value)">{{ value }}</span>
    </template>
    <template #cell-is_global_admin="{ value }">
      <span v-if="value" class="text-indigo-600 font-medium text-xs">Sí</span>
      <span v-else class="text-gray-400 text-xs">No</span>
    </template>
    <template #cell-actions="{ item }">
      <router-link :to="{ name: 'admin.user-detail', params: { id: item.id } }" class="text-xs bg-indigo-50 text-indigo-700 px-2 py-1 rounded hover:bg-indigo-100">Ver perfil</router-link>
    </template>
  </DataTable>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DataTable from '../components/DataTable.vue';

const loading = ref(true);
const users = ref([]);
const pagination = ref(null);
const sortKey = ref('created_at');
const sortDir = ref('desc');
const search = ref('');

const columns = [
  { key: 'name', label: 'Nombre' },
  { key: 'username', label: 'Username' },
  { key: 'email', label: 'Email' },
  { key: 'status', label: 'Estado' },
  { key: 'is_global_admin', label: 'Admin' },
  { key: 'created_at', label: 'Registro' },
  { key: 'actions', label: 'Acciones', sortable: false },
];

let searchTimeout = null;
function onSearch(val) {
  search.value = val;
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => fetchUsers(1, pagination.value?.per_page || 15), 300);
}

async function fetchUsers(page = 1, perPage = 15) {
  loading.value = true;
  try {
    const params = new URLSearchParams({ page, per_page: perPage, sort: sortKey.value, direction: sortDir.value });
    if (search.value) params.set('search', search.value);
    const res = await fetch(`/admin/api/users?${params}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    users.value = data.data || [];
    pagination.value = { current_page: data.current_page, last_page: data.last_page, per_page: data.per_page, total: data.total };
  } catch (e) {
    console.error('Error fetching users:', e);
  } finally {
    loading.value = false;
  }
}

function onSort({ key, dir }) { sortKey.value = key; sortDir.value = dir; fetchUsers(pagination.value?.current_page || 1, pagination.value?.per_page || 15); }
function onPage(p) { fetchUsers(p, pagination.value?.per_page || 15); }
function onPerPage(p) { fetchUsers(1, p); }

function statusBadge(status) {
  const map = { active: 'bg-green-100 text-green-700', pending: 'bg-yellow-100 text-yellow-700', suspended: 'bg-orange-100 text-orange-700', banned: 'bg-red-100 text-red-700' };
  return map[status] || 'bg-gray-100 text-gray-600';
}

onMounted(() => fetchUsers());
</script>
