<template>
  <div class="space-y-4">
    <!-- Filters + Actions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="flex gap-2 flex-wrap">
          <select v-model="typeFilter" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" @change="fetchPosts">
            <option value="">Todos los tipos</option>
            <option value="text">Texto</option>
            <option value="image">Imagen</option>
            <option value="poll">Encuesta</option>
            <option value="repost">Repost</option>
          </select>
          <select v-model="viewFilter" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" @change="fetchPosts">
            <option value="active">Publicados</option>
            <option value="trashed">Eliminados</option>
          </select>
        </div>
        <input v-model="search" type="text" placeholder="Buscar contenido..." class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-48" @input="debouncedFetch" />
      </div>
    </div>

    <!-- Posts Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 text-left text-gray-500">
              <th class="px-5 py-3 font-medium">ID</th>
              <th class="px-5 py-3 font-medium">Contenido</th>
              <th class="px-5 py-3 font-medium">Autor</th>
              <th class="px-5 py-3 font-medium">Tipo</th>
              <th class="px-5 py-3 font-medium">Likes</th>
              <th class="px-5 py-3 font-medium">Comentarios</th>
              <th class="px-5 py-3 font-medium">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="px-5 py-8 text-center text-gray-400">Cargando...</td>
            </tr>
            <tr v-for="post in posts" :key="post.id" class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3 text-gray-500">{{ post.id }}</td>
              <td class="px-5 py-3 max-w-xs">
                <template v-if="editingId === post.id">
                  <textarea v-model="editContent" rows="3" class="w-full border border-gray-200 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </template>
                <template v-else>
                  <div class="cursor-pointer" @click="previewPost(post)">
                    <p class="text-sm text-gray-700 truncate hover:text-indigo-600 transition-colors">{{ post.content }}</p>
                    <p v-if="post.image_url" class="text-xs text-gray-400 mt-0.5">[IMG]</p>
                  </div>
                </template>
              </td>
              <td class="px-5 py-3 text-gray-500">{{ post.user?.name || 'N/A' }}</td>
              <td class="px-5 py-3">
                <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ post.type }}</span>
              </td>
              <td class="px-5 py-3 text-gray-500">{{ post.likes_count }}</td>
              <td class="px-5 py-3 text-gray-500">{{ post.comments_count }}</td>
              <td class="px-5 py-3">
                <template v-if="editingId === post.id">
                  <div class="flex gap-1">
                    <button @click="savePost(post.id)" class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded hover:bg-green-100">Guardar</button>
                    <button @click="editingId = null" class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded hover:bg-gray-200">Cancelar</button>
                  </div>
                </template>
                <template v-else>
                  <div class="flex gap-1">
                    <button @click="startEdit(post)" class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded hover:bg-blue-100">Editar</button>
                    <button v-if="viewFilter === 'active'" @click="deletePost(post.id)" class="text-xs bg-red-50 text-red-700 px-2 py-1 rounded hover:bg-red-100">Eliminar</button>
                    <button v-if="viewFilter === 'trashed'" @click="restorePost(post.id)" class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded hover:bg-green-100">Restaurar</button>
                  </div>
                </template>
              </td>
            </tr>
            <tr v-if="!loading && posts.length === 0">
              <td colspan="7" class="px-5 py-8 text-center text-gray-400">No se encontraron publicaciones</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="totalPages > 1" class="px-5 py-3 border-t border-gray-100 flex items-center justify-between text-sm text-gray-500">
        <span>Página {{ currentPage }} de {{ totalPages }}</span>
        <div class="flex gap-2">
          <button @click="fetchPosts(currentPage - 1)" :disabled="currentPage <= 1" class="px-3 py-1 rounded border border-gray-200 hover:bg-gray-50 disabled:opacity-40">Anterior</button>
          <button @click="fetchPosts(currentPage + 1)" :disabled="currentPage >= totalPages" class="px-3 py-1 rounded border border-gray-200 hover:bg-gray-50 disabled:opacity-40">Siguiente</button>
        </div>
      </div>
    </div>

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

const loading = ref(true);
const posts = ref([]);
const typeFilter = ref('');
const viewFilter = ref('active');
const search = ref('');
const editingId = ref(null);
const editContent = ref('');
const previewingPost = ref(null);
const currentPage = ref(1);
const totalPages = ref(1);
let debounceTimer = null;

async function fetchPosts(page = 1) {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (typeFilter.value) params.set('type', typeFilter.value);
    if (search.value) params.set('search', search.value);
    params.set('page', page);

    let url;
    if (viewFilter.value === 'trashed') {
      url = `/admin/api/posts/trashed?${params}`;
    } else {
      url = `/admin/api/posts?${params}`;
    }

    const res = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    posts.value = data.data || [];
    currentPage.value = data.current_page || 1;
    totalPages.value = data.last_page || 1;
  } catch (e) {
    console.error('Error:', e);
  } finally {
    loading.value = false;
  }
}

function debouncedFetch() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => fetchPosts(), 300);
}

function startEdit(post) {
  editingId.value = post.id;
  editContent.value = post.content;
}

async function savePost(id) {
  try {
    await fetch(`/admin/api/posts/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ content: editContent.value }),
    });
    editingId.value = null;
    fetchPosts(currentPage.value);
  } catch (e) {
    console.error('Error:', e);
  }
}

async function deletePost(id) {
  if (!confirm('¿Eliminar esta publicación?')) return;
  try {
    await fetch(`/admin/api/posts/${id}`, { method: 'DELETE', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    fetchPosts(currentPage.value);
  } catch (e) {
    console.error('Error:', e);
  }
}

async function restorePost(id) {
  try {
    await fetch(`/admin/api/posts/${id}/restore`, {
      method: 'POST',
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    fetchPosts(currentPage.value);
  } catch (e) {
    console.error('Error:', e);
  }
}

function previewPost(post) {
  previewingPost.value = post;
}

onMounted(() => fetchPosts());
</script>
