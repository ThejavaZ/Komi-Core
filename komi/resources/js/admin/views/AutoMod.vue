<template>
  <div class="space-y-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
      <h3 class="font-semibold text-gray-800 mb-4">Agregar Palabra Clave</h3>
      <div class="flex flex-col sm:flex-row gap-3">
        <input v-model="newKeyword.keyword" type="text" placeholder="Palabra o frase a bloquear..." class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
        <select v-model="newKeyword.action" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
          <option value="flag">Flag (marcar para revision)</option>
          <option value="block">Block (bloquear publicacion)</option>
          <option value="delete">Delete (eliminar automaticamente)</option>
        </select>
        <button @click="addKeyword" :disabled="!newKeyword.keyword" class="bg-indigo-600 text-white text-sm px-4 py-1.5 rounded-lg hover:bg-indigo-700 disabled:opacity-40 transition-colors">Agregar</button>
      </div>
    </div>

    <DataTable
      :columns="columns"
      :items="keywords"
      :loading="loading"
      :pagination="pagination"
      :sort-key="sortKey"
      :sort-dir="sortDir"
      empty-text="No hay palabras clave configuradas"
      @sort="onSort"
      @page="onPage"
      @per-page="onPerPage"
    >
      <template #header>
        <h2 class="font-semibold text-gray-800">Palabras Clave Auto-Mod</h2>
      </template>
      <template #cell-keyword="{ item }">
        <template v-if="editingId === item.id">
          <input v-model="editForm.keyword" type="text" class="border border-gray-200 rounded px-2 py-1 text-sm w-full focus:outline-none focus:ring-2 focus:ring-indigo-500" />
        </template>
        <template v-else>
          <span class="font-medium text-gray-800">{{ item.keyword }}</span>
        </template>
      </template>
      <template #cell-action="{ item }">
        <template v-if="editingId === item.id">
          <select v-model="editForm.action" class="border border-gray-200 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="flag">Flag</option>
            <option value="block">Block</option>
            <option value="delete">Delete</option>
          </select>
        </template>
        <template v-else>
          <span class="text-xs px-2 py-1 rounded-full" :class="actionBadge(item.action)">{{ item.action }}</span>
        </template>
      </template>
      <template #cell-actions="{ item }">
        <template v-if="editingId === item.id">
          <div class="flex gap-1">
            <button @click="saveEdit(item.id)" class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded hover:bg-green-100">Guardar</button>
            <button @click="cancelEdit" class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded hover:bg-gray-200">Cancelar</button>
          </div>
        </template>
        <template v-else>
          <div class="flex gap-1">
            <button @click="startEdit(item)" class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded hover:bg-blue-100">Editar</button>
            <button @click="deleteKeyword(item.id)" class="text-xs bg-red-50 text-red-700 px-2 py-1 rounded hover:bg-red-100">Eliminar</button>
          </div>
        </template>
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import DataTable from '../components/DataTable.vue';
import { api } from '../api';

const loading = ref(true);
const keywords = ref([]);
const pagination = ref(null);
const sortKey = ref('created_at');
const sortDir = ref('desc');
const editingId = ref(null);
const newKeyword = reactive({ keyword: '', action: 'flag' });
const editForm = reactive({ keyword: '', action: 'flag' });

const columns = [
  { key: 'id', label: 'ID' },
  { key: 'keyword', label: 'Palabra Clave' },
  { key: 'action', label: 'Accion' },
  { key: 'created_at', label: 'Fecha' },
  { key: 'actions', label: 'Acciones', sortable: false },
];

async function fetchKeywords(page = 1, perPage = 20) {
  loading.value = true;
  try {
    const params = new URLSearchParams({ page, per_page: perPage, sort: sortKey.value, direction: sortDir.value });
    const res = await api(`/admin/api/auto-mod?${params}`);
    const data = await res.json();
    keywords.value = data.data || [];
    pagination.value = { current_page: data.current_page, last_page: data.last_page, per_page: data.per_page, total: data.total };
  } catch (e) { console.error('Error:', e); } finally { loading.value = false; }
}

function onSort({ key, dir }) { sortKey.value = key; sortDir.value = dir; fetchKeywords(pagination.value?.current_page || 1, pagination.value?.per_page || 20); }
function onPage(p) { fetchKeywords(p, pagination.value?.per_page || 20); }
function onPerPage(p) { fetchKeywords(1, p); }

async function addKeyword() {
  await api('/admin/api/auto-mod', {
    method: 'POST',
    body: JSON.stringify(newKeyword),
  });
  newKeyword.keyword = '';
  newKeyword.action = 'flag';
  fetchKeywords(pagination.value?.current_page || 1, pagination.value?.per_page || 20);
}

function startEdit(kw) { editingId.value = kw.id; editForm.keyword = kw.keyword; editForm.action = kw.action; }
function cancelEdit() { editingId.value = null; }

async function saveEdit(id) {
  await api(`/admin/api/auto-mod/${id}`, {
    method: 'PUT',
    body: JSON.stringify(editForm),
  });
  editingId.value = null;
  fetchKeywords(pagination.value?.current_page || 1, pagination.value?.per_page || 20);
}

async function deleteKeyword(id) {
  if (!confirm('Eliminar esta keyword?')) return;
  await api(`/admin/api/auto-mod/${id}`, { method: 'DELETE' });
  fetchKeywords(pagination.value?.current_page || 1, pagination.value?.per_page || 20);
}

function actionBadge(action) {
  return { flag: 'bg-yellow-100 text-yellow-700', block: 'bg-orange-100 text-orange-700', delete: 'bg-red-100 text-red-700' }[action] || 'bg-gray-100 text-gray-600';
}

onMounted(() => fetchKeywords());
</script>
