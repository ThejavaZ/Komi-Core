<template>
  <DataTable
    :columns="columns"
    :items="communities"
    :loading="loading"
    :pagination="pagination"
    :sort-key="sortKey"
    :sort-dir="sortDir"
    empty-text="No hay comunidades"
    @sort="onSort"
    @page="onPage"
    @per-page="onPerPage"
  >
    <template #header>
      <h2 class="font-semibold text-gray-800">Comunidades</h2>
    </template>
    <template #cell-posts_count="{ value }">
      <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ value }}</span>
    </template>
    <template #cell-members_count="{ value }">
      <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ value }}</span>
    </template>
    <template #cell-actions="{ item }">
      <button @click="deleteCommunity(item.id)" class="text-xs bg-red-50 text-red-700 px-2 py-1 rounded hover:bg-red-100">Eliminar</button>
    </template>
  </DataTable>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import DataTable from '../components/DataTable.vue';
import { api } from '../api';

const loading = ref(true);
const communities = ref([]);
const pagination = ref(null);
const sortKey = ref('created_at');
const sortDir = ref('desc');

const columns = [
  { key: 'id', label: 'ID' },
  { key: 'name', label: 'Nombre' },
  { key: 'slug', label: 'Slug' },
  { key: 'posts_count', label: 'Posts' },
  { key: 'members_count', label: 'Miembros' },
  { key: 'actions', label: 'Acciones', sortable: false, class: 'text-right' },
];

async function fetchCommunities(page = 1, perPage = 15) {
  loading.value = true;
  try {
    const params = new URLSearchParams({ page, per_page: perPage, sort: sortKey.value, direction: sortDir.value });
    const res = await api(`/admin/api/communities?${params}`);
    const data = await res.json();
    communities.value = data.data || [];
    pagination.value = { current_page: data.current_page, last_page: data.last_page, per_page: data.per_page, total: data.total };
  } catch (e) {
    console.error('Error:', e);
  } finally {
    loading.value = false;
  }
}

function onSort({ key, dir }) { sortKey.value = key; sortDir.value = dir; fetchCommunities(pagination.value?.current_page || 1, pagination.value?.per_page || 15); }
function onPage(p) { fetchCommunities(p, pagination.value?.per_page || 15); }
function onPerPage(p) { fetchCommunities(1, p); }

async function deleteCommunity(id) {
  if (!confirm('¿Eliminar esta comunidad?')) return;
  await api(`/admin/api/communities/${id}`, { method: 'DELETE' });
  fetchCommunities(pagination.value?.current_page || 1, pagination.value?.per_page || 15);
}

onMounted(() => fetchCommunities());
</script>
