<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
      <h2 class="font-semibold text-gray-800">Gestión de Usuarios</h2>
      <input
        v-model="search"
        type="text"
        placeholder="Buscar usuarios..."
        class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
        @input="fetchUsers"
      />
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-100 text-left text-gray-500">
            <th class="px-5 py-3 font-medium">Nombre</th>
            <th class="px-5 py-3 font-medium">Username</th>
            <th class="px-5 py-3 font-medium">Email</th>
            <th class="px-5 py-3 font-medium">Estado</th>
            <th class="px-5 py-3 font-medium">Admin</th>
            <th class="px-5 py-3 font-medium">Registro</th>
            <th class="px-5 py-3 font-medium">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="7" class="px-5 py-8 text-center text-gray-400">Cargando...</td>
          </tr>
          <tr
            v-for="user in users"
            :key="user.id"
            class="border-b border-gray-50 hover:bg-gray-50 transition-colors"
          >
            <td class="px-5 py-3 font-medium text-gray-800">{{ user.name }}</td>
            <td class="px-5 py-3 text-gray-500">@{{ user.username }}</td>
            <td class="px-5 py-3 text-gray-500">{{ user.email }}</td>
            <td class="px-5 py-3">
              <span class="text-xs px-2 py-1 rounded-full" :class="statusBadge(user.status)">{{ user.status }}</span>
            </td>
            <td class="px-5 py-3">
              <span v-if="user.is_global_admin" class="text-indigo-600 font-medium text-xs">Sí</span>
              <span v-else class="text-gray-400 text-xs">No</span>
            </td>
            <td class="px-5 py-3 text-gray-400 text-xs">{{ user.created_at }}</td>
            <td class="px-5 py-3">
              <router-link :to="{ name: 'admin.user-detail', params: { id: user.id } }" class="text-xs bg-indigo-50 text-indigo-700 px-2 py-1 rounded hover:bg-indigo-100">Ver perfil</router-link>
            </td>
          </tr>
          <tr v-if="!loading && users.length === 0">
            <td colspan="7" class="px-5 py-8 text-center text-gray-400">No se encontraron usuarios</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const loading = ref(true);
const users = ref([]);
const search = ref('');

async function fetchUsers() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    const response = await fetch(`/admin/api/users?${params}`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    const data = await response.json();
    users.value = data.data || [];
  } catch (error) {
    console.error('Error fetching users:', error);
  } finally {
    loading.value = false;
  }
}

function statusBadge(status) {
  const map = {
    active: 'bg-green-100 text-green-700',
    pending: 'bg-yellow-100 text-yellow-700',
    suspended: 'bg-orange-100 text-orange-700',
    banned: 'bg-red-100 text-red-700',
  };
  return map[status] || 'bg-gray-100 text-gray-600';
}

onMounted(fetchUsers);
</script>
