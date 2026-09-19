<template>
  <div class="space-y-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
      <h3 class="font-semibold text-gray-800 mb-4">Agregar Tag</h3>
      <div class="flex gap-3">
        <input v-model="newTagName" type="text" placeholder="Nombre del tag..." class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" @keyup.enter="addTag" />
        <button @click="addTag" :disabled="!newTagName" class="bg-indigo-600 text-white text-sm px-4 py-1.5 rounded-lg hover:bg-indigo-700 disabled:opacity-40 transition-colors">Crear Tag</button>
      </div>
    </div>

    <DataTable
      :columns="columns"
      :items="tags"
      :loading="loading"
      :pagination="pagination"
      :sort-key="sortKey"
      :sort-dir="sortDir"
      empty-text="No se encontraron tags"
      @sort="onSort"
      @page="onPage"
      @per-page="onPerPage"
    >
      <template #header>
        <h2 class="font-semibold text-gray-800">Gestion de Tags</h2>
      </template>
      <template #cell-name="{ item }">
        <template v-if="editingId === item.id">
          <input v-model="editName" type="text" class="border border-gray-200 rounded px-2 py-1 text-sm w-full focus:outline-none focus:ring-2 focus:ring-indigo-500" />
        </template>
        <template v-else>
          <span class="text-indigo-600 font-medium">#{{ item.name }}</span>
        </template>
      </template>
      <template #cell-posts_count="{ value }">
        <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ value }}</span>
      </template>
      <template #cell-actions="{ item }">
        <template v-if="editingId === item.id">
          <div class="flex gap-1">
            <button @click="saveTag(item.id)" class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded hover:bg-green-100">Guardar</button>
            <button @click="editingId = null" class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded hover:bg-gray-200">Cancelar</button>
          </div>
        </template>
        <template v-else>
          <div class="flex gap-1">
            <button @click="startEdit(item)" class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded hover:bg-blue-100 flex items-center gap-1"><PencilSquareIcon class="w-3.5 h-3.5"/> Editar</button>
            <button @click="confirmDeleteTag(item.id)" class="text-xs bg-red-50 text-red-700 px-2 py-1 rounded hover:bg-red-100 flex items-center gap-1"><TrashIcon class="w-3.5 h-3.5"/> Eliminar</button>
          </div>
        </template>
      </template>
    </DataTable>

    <ConfirmDialog v-model="showDeleteDialog" title="Eliminar tag" subtitle="El tag sera eliminado permanentemente." :icon="TrashIcon" confirm-text="Eliminar" @confirm="deleteTag"/>
  </div>
</template>

<script setup>
import { showErrorToast } from '../api';
import { ref, onMounted } from 'vue';
import DataTable from '../components/DataTable.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import { api } from '../api';
import { TrashIcon, PencilSquareIcon } from '@heroicons/vue/24/outline';

const loading = ref(true);
const tags = ref([]);
const pagination = ref(null);
const sortKey = ref('created_at');
const sortDir = ref('desc');
const newTagName = ref('');
const editingId = ref(null);
const editName = ref('');

// Dialog states
const showDeleteDialog = ref(false);
const pendingDeleteId = ref(null);

const columns = [
  { key: 'id', label: 'ID' },
  { key: 'name', label: 'Nombre' },
  { key: 'slug', label: 'Slug' },
  { key: 'posts_count', label: 'Publicaciones' },
  { key: 'actions', label: 'Acciones', sortable: false },
];

async function fetchTags(page = 1, perPage = 15) {
  loading.value = true;
  try {
    const params = new URLSearchParams({ page, per_page: perPage, sort: sortKey.value, direction: sortDir.value });
    const res = await api(`/admin/api/tags?${params}`);
    const data = await res.json();
    tags.value = data.data || [];
    pagination.value = { current_page: data.current_page, last_page: data.last_page, per_page: data.per_page, total: data.total };
  } catch (e) {
    showErrorToast(e, 'Tags');
  } finally {
    loading.value = false;
  }
}

function onSort({ key, dir }) { sortKey.value = key; sortDir.value = dir; fetchTags(pagination.value?.current_page || 1, pagination.value?.per_page || 15); }
function onPage(p) { fetchTags(p, pagination.value?.per_page || 15); }
function onPerPage(p) { fetchTags(1, p); }

async function addTag() {
  if (!newTagName.value) return;
  await api('/admin/api/tags', {
    method: 'POST',
    body: JSON.stringify({ name: newTagName.value }),
  });
  newTagName.value = '';
  fetchTags(pagination.value?.current_page || 1, pagination.value?.per_page || 15);
}

function startEdit(tag) { editingId.value = tag.id; editName.value = tag.name; }

async function saveTag(id) {
  await api(`/admin/api/tags/${id}`, {
    method: 'PUT',
    body: JSON.stringify({ name: editName.value }),
  });
  editingId.value = null;
  fetchTags(pagination.value?.current_page || 1, pagination.value?.per_page || 15);
}

function confirmDeleteTag(id) { pendingDeleteId.value = id; showDeleteDialog.value = true; }

async function deleteTag() {
  const id = pendingDeleteId.value;
  showDeleteDialog.value = false;
  pendingDeleteId.value = null;
  await api(`/admin/api/tags/${id}`, { method: 'DELETE' });
  fetchTags(pagination.value?.current_page || 1, pagination.value?.per_page || 15);
}

onMounted(() => fetchTags());
</script>
