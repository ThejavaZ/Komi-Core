<template>
  <div class="space-y-4">
    <!-- Add Keyword Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
      <h3 class="font-semibold text-gray-800 mb-4">Agregar Palabra Clave</h3>
      <div class="flex flex-col sm:flex-row gap-3">
        <input
          v-model="newKeyword.keyword"
          type="text"
          placeholder="Palabra o frase a bloquear..."
          class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />
        <select
          v-model="newKeyword.action"
          class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          <option value="flag">Flag (marcar para revisión)</option>
          <option value="block">Block (bloquear publicación)</option>
          <option value="delete">Delete (eliminar automáticamente)</option>
        </select>
        <button
          @click="addKeyword"
          :disabled="!newKeyword.keyword"
          class="bg-indigo-600 text-white text-sm px-4 py-1.5 rounded-lg hover:bg-indigo-700 disabled:opacity-40 transition-colors"
        >
          Agregar
        </button>
      </div>
    </div>

    <!-- Keywords List -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">Palabras Clave Auto-Mod</h2>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 text-left text-gray-500">
              <th class="px-5 py-3 font-medium">ID</th>
              <th class="px-5 py-3 font-medium">Palabra Clave</th>
              <th class="px-5 py-3 font-medium">Acción</th>
              <th class="px-5 py-3 font-medium">Fecha</th>
              <th class="px-5 py-3 font-medium">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="5" class="px-5 py-8 text-center text-gray-400">Cargando...</td>
            </tr>
            <tr v-for="kw in keywords" :key="kw.id" class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3 text-gray-500">{{ kw.id }}</td>
              <td class="px-5 py-3">
                <template v-if="editingId === kw.id">
                  <input v-model="editForm.keyword" type="text" class="border border-gray-200 rounded px-2 py-1 text-sm w-full focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                </template>
                <template v-else>
                  <span class="font-medium text-gray-800">{{ kw.keyword }}</span>
                </template>
              </td>
              <td class="px-5 py-3">
                <template v-if="editingId === kw.id">
                  <select v-model="editForm.action" class="border border-gray-200 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="flag">Flag</option>
                    <option value="block">Block</option>
                    <option value="delete">Delete</option>
                  </select>
                </template>
                <template v-else>
                  <span class="text-xs px-2 py-1 rounded-full" :class="actionBadge(kw.action)">{{ kw.action }}</span>
                </template>
              </td>
              <td class="px-5 py-3 text-xs text-gray-400">{{ kw.created_at }}</td>
              <td class="px-5 py-3">
                <template v-if="editingId === kw.id">
                  <div class="flex gap-1">
                    <button @click="saveEdit(kw.id)" class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded hover:bg-green-100">Guardar</button>
                    <button @click="cancelEdit" class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded hover:bg-gray-200">Cancelar</button>
                  </div>
                </template>
                <template v-else>
                  <div class="flex gap-1">
                    <button @click="startEdit(kw)" class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded hover:bg-blue-100">Editar</button>
                    <button @click="deleteKeyword(kw.id)" class="text-xs bg-red-50 text-red-700 px-2 py-1 rounded hover:bg-red-100">Eliminar</button>
                  </div>
                </template>
              </td>
            </tr>
            <tr v-if="!loading && keywords.length === 0">
              <td colspan="5" class="px-5 py-8 text-center text-gray-400">No hay palabras clave configuradas</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="totalPages > 1" class="px-5 py-3 border-t border-gray-100 flex items-center justify-between text-sm text-gray-500">
        <span>Página {{ currentPage }} de {{ totalPages }}</span>
        <div class="flex gap-2">
          <button @click="fetchKeywords(currentPage - 1)" :disabled="currentPage <= 1" class="px-3 py-1 rounded border border-gray-200 hover:bg-gray-50 disabled:opacity-40">Anterior</button>
          <button @click="fetchKeywords(currentPage + 1)" :disabled="currentPage >= totalPages" class="px-3 py-1 rounded border border-gray-200 hover:bg-gray-50 disabled:opacity-40">Siguiente</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';

const loading = ref(true);
const keywords = ref([]);
const currentPage = ref(1);
const totalPages = ref(1);
const editingId = ref(null);

const newKeyword = reactive({ keyword: '', action: 'flag' });
const editForm = reactive({ keyword: '', action: 'flag' });

async function fetchKeywords(page = 1) {
  loading.value = true;
  try {
    const res = await fetch(`/admin/api/auto-mod?page=${page}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    keywords.value = data.data || [];
    currentPage.value = data.current_page || 1;
    totalPages.value = data.last_page || 1;
  } catch (e) {
    console.error('Error:', e);
  } finally {
    loading.value = false;
  }
}

async function addKeyword() {
  try {
    await fetch('/admin/api/auto-mod', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify(newKeyword),
    });
    newKeyword.keyword = '';
    newKeyword.action = 'flag';
    fetchKeywords();
  } catch (e) {
    console.error('Error:', e);
  }
}

function startEdit(kw) {
  editingId.value = kw.id;
  editForm.keyword = kw.keyword;
  editForm.action = kw.action;
}

function cancelEdit() {
  editingId.value = null;
}

async function saveEdit(id) {
  try {
    await fetch(`/admin/api/auto-mod/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify(editForm),
    });
    editingId.value = null;
    fetchKeywords();
  } catch (e) {
    console.error('Error:', e);
  }
}

async function deleteKeyword(id) {
  if (!confirm('¿Eliminar esta keyword?')) return;
  try {
    await fetch(`/admin/api/auto-mod/${id}`, { method: 'DELETE', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    fetchKeywords();
  } catch (e) {
    console.error('Error:', e);
  }
}

function actionBadge(action) {
  return { flag: 'bg-yellow-100 text-yellow-700', block: 'bg-orange-100 text-orange-700', delete: 'bg-red-100 text-red-700' }[action] || 'bg-gray-100 text-gray-600';
}

onMounted(() => fetchKeywords());
</script>
