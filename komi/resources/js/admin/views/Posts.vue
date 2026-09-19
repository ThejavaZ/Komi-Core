<template>
  <div class="space-y-4">
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="flex gap-2 flex-wrap">
          <select v-model="typeFilter" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" @change="fetchPosts(1)">
            <option value="">Todos los tipos</option>
            <option value="text">Texto</option>
            <option value="image">Imagen</option>
            <option value="poll">Encuesta</option>
            <option value="repost">Repost</option>
          </select>
          <select v-model="viewFilter" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" @change="fetchPosts(1)">
            <option value="active">Publicados</option>
            <option value="trashed">Eliminados</option>
          </select>
        </div>
      </div>
    </div>

    <DataTable
      :columns="columns"
      :items="posts"
      :loading="loading"
      :pagination="pagination"
      :sort-key="sortKey"
      :sort-dir="sortDir"
      searchable
      search-placeholder="Buscar contenido..."
      empty-text="No se encontraron publicaciones"
      @sort="onSort"
      @page="onPage"
      @per-page="onPerPage"
      @search="onSearch"
    >
      <template #cell-content="{ item }">
        <template v-if="editingId === item.id">
          <textarea v-model="editContent" rows="3" class="w-full border border-gray-200 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
        </template>
        <template v-else>
          <div class="cursor-pointer" @click="previewPost(item)">
            <p class="text-sm text-gray-700 truncate hover:text-indigo-600 transition-colors">{{ item.content }}</p>
            <p v-if="item.image_url" class="text-xs text-gray-400 mt-0.5">[IMG]</p>
          </div>
        </template>
      </template>
      <template #cell-user="{ item }">
        {{ item.user?.name || 'N/A' }}
      </template>
      <template #cell-type="{ value }">
        <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ value }}</span>
      </template>
      <template #cell-actions="{ item }">
        <template v-if="editingId === item.id">
          <div class="flex gap-1">
            <button @click="savePost(item.id)" class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded hover:bg-green-100">Guardar</button>
            <button @click="editingId = null" class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded hover:bg-gray-200">Cancelar</button>
          </div>
        </template>
        <template v-else>
          <div class="flex gap-1">
            <button @click="startEdit(item)" class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded hover:bg-blue-100">Editar</button>
            <button v-if="viewFilter === 'active'" @click="deletePost(item.id)" class="text-xs bg-red-50 text-red-700 px-2 py-1 rounded hover:bg-red-100">Eliminar</button>
            <button v-if="viewFilter === 'trashed'" @click="restorePost(item.id)" class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded hover:bg-green-100">Restaurar</button>
          </div>
        </template>
      </template>
    </DataTable>

    <!-- Preview Modal -->
    <div v-if="previewingPost" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="previewingPost = null">
      <div class="bg-white rounded-xl shadow-xl max-w-lg w-full max-h-[80vh] overflow-y-auto">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
          <h3 class="font-semibold text-gray-800">Preview del Post #{{ previewingPost.id }}</h3>
          <button @click="previewingPost = null" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <div class="p-5">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
              <span class="text-indigo-700 font-semibold text-sm">{{ previewingPost.user?.name?.charAt(0)?.toUpperCase() || 'U' }}</span>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-800">{{ previewingPost.user?.name || 'N/A' }}</p>
              <p class="text-xs text-gray-400">@{{ previewingPost.user?.username }}</p>
            </div>
          </div>
          <div class="text-sm text-gray-700 whitespace-pre-wrap mb-4">{{ previewingPost.content }}</div>
          <img v-if="previewingPost.image_url" :src="previewingPost.image_url" class="rounded-lg w-full mb-4" alt="Post image" />
          <div class="flex items-center gap-4 text-xs text-gray-400">
            <span>{{ previewingPost.likes_count }} likes</span>
            <span>{{ previewingPost.comments_count }} comentarios</span>
            <span>{{ previewingPost.type }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DataTable from '../components/DataTable.vue';
import { api } from '../api';

const loading = ref(true);
const posts = ref([]);
const pagination = ref(null);
const sortKey = ref('created_at');
const sortDir = ref('desc');
const typeFilter = ref('');
const viewFilter = ref('active');
const editingId = ref(null);
const editContent = ref('');
const previewingPost = ref(null);

const columns = [
  { key: 'id', label: 'ID' },
  { key: 'content', label: 'Contenido', sortable: false },
  { key: 'user', label: 'Autor', sortable: false },
  { key: 'type', label: 'Tipo' },
  { key: 'likes_count', label: 'Likes' },
  { key: 'comments_count', label: 'Comentarios' },
  { key: 'actions', label: 'Acciones', sortable: false },
];

let searchVal = '';
function onSearch(val) {
  searchVal = val;
  fetchPosts(1, pagination.value?.per_page || 15);
}

async function fetchPosts(page = 1, perPage = 15) {
  loading.value = true;
  try {
    const params = new URLSearchParams({ page, per_page: perPage, sort: sortKey.value, direction: sortDir.value });
    if (typeFilter.value) params.set('type', typeFilter.value);
    if (searchVal) params.set('search', searchVal);
    const url = viewFilter.value === 'trashed' ? `/admin/api/posts/trashed?${params}` : `/admin/api/posts?${params}`;
    const res = await api(url);
    const data = await res.json();
    posts.value = data.data || [];
    pagination.value = { current_page: data.current_page, last_page: data.last_page, per_page: data.per_page, total: data.total };
  } catch (e) {
    console.error('Error:', e);
  } finally {
    loading.value = false;
  }
}

function onSort({ key, dir }) { sortKey.value = key; sortDir.value = dir; fetchPosts(pagination.value?.current_page || 1, pagination.value?.per_page || 15); }
function onPage(p) { fetchPosts(p, pagination.value?.per_page || 15); }
function onPerPage(p) { fetchPosts(1, p); }

function startEdit(post) { editingId.value = post.id; editContent.value = post.content; }

async function savePost(id) {
  await api(`/admin/api/posts/${id}`, {
    method: 'PUT',
    body: JSON.stringify({ content: editContent.value }),
  });
  editingId.value = null;
  fetchPosts(pagination.value?.current_page || 1, pagination.value?.per_page || 15);
}

async function deletePost(id) {
  if (!confirm('¿Eliminar esta publicación?')) return;
  await api(`/admin/api/posts/${id}`, { method: 'DELETE' });
  fetchPosts(pagination.value?.current_page || 1, pagination.value?.per_page || 15);
}

async function restorePost(id) {
  await api(`/admin/api/posts/${id}/restore`, { method: 'POST' });
  fetchPosts(pagination.value?.current_page || 1, pagination.value?.per_page || 15);
}

function previewPost(post) { previewingPost.value = post; }

onMounted(() => fetchPosts());
</script>
