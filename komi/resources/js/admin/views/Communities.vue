<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100">
      <h2 class="font-semibold text-gray-800">Comunidades</h2>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-100 text-left text-gray-500">
            <th class="px-5 py-3 font-medium">ID</th>
            <th class="px-5 py-3 font-medium">Nombre</th>
            <th class="px-5 py-3 font-medium">Slug</th>
            <th class="px-5 py-3 font-medium">Posts</th>
            <th class="px-5 py-3 font-medium">Miembros</th>
            <th class="px-5 py-3 font-medium">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="6" class="px-5 py-8 text-center text-gray-400">Cargando...</td>
          </tr>
          <tr v-for="c in communities" :key="c.id" class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
            <td class="px-5 py-3 text-gray-500">{{ c.id }}</td>
            <td class="px-5 py-3 font-medium text-gray-800">{{ c.name }}</td>
            <td class="px-5 py-3 text-gray-500">{{ c.slug }}</td>
            <td class="px-5 py-3">
              <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ c.posts_count }}</span>
            </td>
            <td class="px-5 py-3">
              <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ c.members_count }}</span>
            </td>
            <td class="px-5 py-3">
              <button @click="deleteCommunity(c.id)" class="text-xs bg-red-50 text-red-700 px-2 py-1 rounded hover:bg-red-100">Eliminar</button>
            </td>
          </tr>
          <tr v-if="!loading && communities.length === 0">
            <td colspan="6" class="px-5 py-8 text-center text-gray-400">No hay comunidades</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const loading = ref(true);
const communities = ref([]);

async function fetchCommunities() {
  loading.value = true;
  try {
    const res = await fetch('/admin/api/communities', { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    communities.value = data.data || [];
  } catch (e) {
    console.error('Error:', e);
  } finally {
    loading.value = false;
  }
}

async function deleteCommunity(id) {
  if (!confirm('¿Eliminar esta comunidad?')) return;
  try {
    await fetch(`/admin/api/communities/${id}`, { method: 'DELETE', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    fetchCommunities();
  } catch (e) {
    console.error('Error:', e);
  }
}

onMounted(fetchCommunities);
</script>
