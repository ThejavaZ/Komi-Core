<template>
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
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="4" class="px-5 py-8 text-center text-gray-400">Cargando...</td>
          </tr>
          <tr
            v-for="tag in tags"
            :key="tag.id"
            class="border-b border-gray-50 hover:bg-gray-50 transition-colors"
          >
            <td class="px-5 py-3 text-gray-500">{{ tag.id }}</td>
            <td class="px-5 py-3">
              <span class="text-indigo-600 font-medium">#{{ tag.name }}</span>
            </td>
            <td class="px-5 py-3 text-gray-500">{{ tag.slug }}</td>
            <td class="px-5 py-3">
              <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">
                {{ tag.posts_count }}
              </span>
            </td>
          </tr>
          <tr v-if="!loading && tags.length === 0">
            <td colspan="4" class="px-5 py-8 text-center text-gray-400">No se encontraron tags</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const loading = ref(true);
const tags = ref([]);

async function fetchTags() {
  loading.value = true;
  try {
    const response = await fetch('/admin/api/tags', {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    const data = await response.json();
    tags.value = data.data || [];
  } catch (error) {
    console.error('Error fetching tags:', error);
  } finally {
    loading.value = false;
  }
}

onMounted(fetchTags);
</script>
