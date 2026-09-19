<template>
  <div class="space-y-4">
    <!-- Add Tag Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
      <h3 class="font-semibold text-gray-800 mb-4">Agregar Tag</h3>
      <div class="flex gap-3">
        <input
          v-model="newTagName"
          type="text"
          placeholder="Nombre del tag..."
          class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
          @keyup.enter="addTag"
        />
        <button
          @click="addTag"
          :disabled="!newTagName"
          class="bg-indigo-600 text-white text-sm px-4 py-1.5 rounded-lg hover:bg-indigo-700 disabled:opacity-40 transition-colors"
        >
          Crear Tag
        </button>
      </div>
    </div>

    <!-- Tags Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">Gestión de Tags</h2>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 text-left text-gray-500">
              <th class="px-5 py-3 font-medium">ID</th>
              <th class="px-5 py-3 font-medium">Nombre</th>
              <th class="px-5 py-3 font-medium">Slug</th>
              <th class="px-5 py-3 font-medium">Publicaciones</th>
              <th class="px-5 py-3 font-medium">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="5" class="px-5 py-8 text-center text-gray-400">Cargando...</td>
            </tr>
            <tr v-for="tag in tags" :key="tag.id" class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3 text-gray-500">{{ tag.id }}</td>
              <td class="px-5 py-3">
                <template v-if="editingId === tag.id">
                  <input v-model="editName" type="text" class="border border-gray-200 rounded px-2 py-1 text-sm w-full focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                </template>
                <template v-else>
                  <span class="text-indigo-600 font-medium">#{{ tag.name }}</span>
                </template>
              </td>
              <td class="px-5 py-3 text-gray-500">{{ tag.slug }}</td>
              <td class="px-5 py-3">
                <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ tag.posts_count }}</span>
              </td>
              <td class="px-5 py-3">
                <template v-if="editingId === tag.id">
                  <div class="flex gap-1">
                    <button @click="saveTag(tag.id)" class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded hover:bg-green-100">Guardar</button>
                    <button @click="editingId = null" class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded hover:bg-gray-200">Cancelar</button>
                  </div>
                </template>
                <template v-else>
                  <div class="flex gap-1">
                    <button @click="startEdit(tag)" class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded hover:bg-blue-100">Editar</button>
                    <button @click="deleteTag(tag.id)" class="text-xs bg-red-50 text-red-700 px-2 py-1 rounded hover:bg-red-100">Eliminar</button>
                  </div>
                </template>
              </td>
            </tr>
            <tr v-if="!loading && tags.length === 0">
              <td colspan="5" class="px-5 py-8 text-center text-gray-400">No se encontraron tags</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="totalPages > 1" class="px-5 py-3 border-t border-gray-100 flex items-center justify-between text-sm text-gray-500">
        <span>Página {{ currentPage }} de {{ totalPages }}</span>
        <div class="flex gap-2">
          <button @click="fetchTags(currentPage - 1)" :disabled="currentPage <= 1" class="px-3 py-1 rounded border border-gray-200 hover:bg-gray-50 disabled:opacity-40">Anterior</button>
          <button @click="fetchTags(currentPage + 1)" :disabled="currentPage >= totalPages" class="px-3 py-1 rounded border border-gray-200 hover:bg-gray-50 disabled:opacity-40">Siguiente</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const loading = ref(true);
const tags = ref([]);
const newTagName = ref('');
const editingId = ref(null);
const editName = ref('');
const currentPage = ref(1);
const totalPages = ref(1);

async function fetchTags(page = 1) {
  loading.value = true;
  try {
    const res = await fetch(`/admin/api/tags?page=${page}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    tags.value = data.data || [];
    currentPage.value = data.current_page || 1;
    totalPages.value = data.last_page || 1;
  } catch (e) {
    console.error('Error:', e);
  } finally {
    loading.value = false;
  }
}

async function addTag() {
  if (!newTagName.value) return;
  try {
    await fetch('/admin/api/tags', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ name: newTagName.value }),
    });
    newTagName.value = '';
    fetchTags();
  } catch (e) {
    console.error('Error:', e);
  }
}

function startEdit(tag) {
  editingId.value = tag.id;
  editName.value = tag.name;
}

async function saveTag(id) {
  try {
    await fetch(`/admin/api/tags/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ name: editName.value }),
    });
    editingId.value = null;
    fetchTags();
  } catch (e) {
    console.error('Error:', e);
  }
}

async function deleteTag(id) {
  if (!confirm('¿Eliminar este tag?')) return;
  try {
    await fetch(`/admin/api/tags/${id}`, { method: 'DELETE', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    fetchTags();
  } catch (e) {
    console.error('Error:', e);
  }
}

onMounted(() => fetchTags());
</script>
