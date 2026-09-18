<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
      <h2 class="font-semibold text-gray-800">Gestión de Publicaciones</h2>
      <select
        v-model="typeFilter"
        class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
        @change="fetchPosts"
      >
        <option value="">Todos los tipos</option>
        <option value="text">Texto</option>
        <option value="image">Imagen</option>
        <option value="poll">Encuesta</option>
        <option value="repost">Repost</option>
      </select>
    </div>
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
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="6" class="px-5 py-8 text-center text-gray-400">Cargando...</td>
          </tr>
          <tr
            v-for="post in posts"
            :key="post.id"
            class="border-b border-gray-50 hover:bg-gray-50 transition-colors"
          >
            <td class="px-5 py-3 text-gray-500">{{ post.id }}</td>
            <td class="px-5 py-3 text-gray-800 max-w-xs truncate">{{ post.content }}</td>
            <td class="px-5 py-3 text-gray-500">{{ post.user?.name || 'N/A' }}</td>
            <td class="px-5 py-3">
              <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">
                {{ post.type }}
              </span>
            </td>
            <td class="px-5 py-3 text-gray-500">{{ post.likes_count }}</td>
            <td class="px-5 py-3 text-gray-500">{{ post.comments_count }}</td>
          </tr>
          <tr v-if="!loading && posts.length === 0">
            <td colspan="6" class="px-5 py-8 text-center text-gray-400">No se encontraron publicaciones</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const loading = ref(true);
const posts = ref([]);
const typeFilter = ref('');

async function fetchPosts() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (typeFilter.value) params.set('type', typeFilter.value);
    const response = await fetch(`/admin/api/posts?${params}`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    const data = await response.json();
    posts.value = data.data || [];
  } catch (error) {
    console.error('Error fetching posts:', error);
  } finally {
    loading.value = false;
  }
}

onMounted(fetchPosts);
</script>
