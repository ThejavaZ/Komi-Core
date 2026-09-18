<template>
  <div v-if="loading" class="space-y-4">
    <div class="h-32 bg-gray-100 rounded-xl animate-pulse" />
    <div class="h-48 bg-gray-100 rounded-xl animate-pulse" />
  </div>
  <div v-else-if="user">
    <!-- User header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
      <div class="flex items-start gap-5">
        <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
          <span class="text-indigo-700 font-bold text-xl">{{ user.name?.charAt(0)?.toUpperCase() || 'U' }}</span>
        </div>
        <div class="flex-1">
          <div class="flex items-center gap-3 mb-1">
            <h2 class="text-xl font-bold text-gray-900">{{ user.name }}</h2>
            <span v-if="user.is_verified" class="text-blue-500 text-sm" title="Verificado">&#10003;</span>
            <span v-if="user.is_global_admin" class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full">Admin</span>
          </div>
          <p class="text-sm text-gray-500">@{{ user.username }} &middot; {{ user.email }}</p>
          <p v-if="user.bio" class="text-sm text-gray-600 mt-2">{{ user.bio }}</p>
          <div class="flex items-center gap-4 mt-3">
            <span class="text-xs px-2 py-1 rounded-full" :class="statusBadge(user.status)">{{ user.status }}</span>
            <span class="text-xs text-gray-400">Registro: {{ user.created_at }}</span>
          </div>
        </div>
        <div class="flex gap-2">
          <button
            v-if="user.status === 'active'"
            @click="updateUser({ status: 'suspended' })"
            class="text-xs bg-orange-50 text-orange-700 px-3 py-1.5 rounded-lg hover:bg-orange-100"
          >Suspender</button>
          <button
            v-if="user.status === 'active' || user.status === 'suspended'"
            @click="updateUser({ status: 'banned' })"
            class="text-xs bg-red-50 text-red-700 px-3 py-1.5 rounded-lg hover:bg-red-100"
          >Banear</button>
          <button
            v-if="user.status === 'suspended' || user.status === 'banned'"
            @click="updateUser({ status: 'active' })"
            class="text-xs bg-green-50 text-green-700 px-3 py-1.5 rounded-lg hover:bg-green-100"
          >Activar</button>
          <button
            @click="updateUser({ is_verified: !user.is_verified })"
            class="text-xs bg-blue-50 text-blue-700 px-3 py-1.5 rounded-lg hover:bg-blue-100"
          >{{ user.is_verified ? 'Quitar verificación' : 'Verificar' }}</button>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4 mb-6">
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
        <p class="text-2xl font-bold text-gray-900">{{ userStats.posts }}</p>
        <p class="text-xs text-gray-500">Publicaciones</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
        <p class="text-2xl font-bold text-gray-900">{{ userStats.comments }}</p>
        <p class="text-xs text-gray-500">Comentarios</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
        <p class="text-2xl font-bold text-gray-900">{{ userStats.reactions }}</p>
        <p class="text-xs text-gray-500">Reacciones</p>
      </div>
    </div>

    <!-- Recent posts -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">Publicaciones recientes</h3>
      </div>
      <div class="p-5">
        <ul v-if="recentPosts.length" class="space-y-3">
          <li v-for="post in recentPosts" :key="post.id" class="border-b border-gray-50 pb-3 last:border-0">
            <p class="text-sm text-gray-700">{{ post.content }}</p>
            <div class="flex items-center gap-3 mt-1 text-xs text-gray-400">
              <span class="px-1.5 py-0.5 rounded bg-gray-100">{{ post.type }}</span>
              <span>{{ post.likes_count }} likes</span>
              <span>{{ post.comments_count }} comments</span>
              <span>{{ post.created_at }}</span>
            </div>
          </li>
        </ul>
        <p v-else class="text-sm text-gray-400 text-center py-4">No tiene publicaciones</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const user = ref(null);
const userStats = ref({ posts: 0, comments: 0, reactions: 0 });
const recentPosts = ref([]);

async function fetchUser() {
  try {
    const res = await fetch(`/admin/api/users/${route.params.id}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    user.value = data.user;
    userStats.value = data.stats;
    recentPosts.value = data.recent_posts;
  } catch (e) {
    console.error('Error:', e);
  } finally {
    loading.value = false;
  }
}

async function updateUser(payload) {
  try {
    const res = await fetch(`/admin/api/users/${route.params.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify(payload),
    });
    const data = await res.json();
    user.value = data.user;
  } catch (e) {
    console.error('Error:', e);
  }
}

function statusBadge(status) {
  return { active: 'bg-green-100 text-green-700', pending: 'bg-yellow-100 text-yellow-700', suspended: 'bg-orange-100 text-orange-700', banned: 'bg-red-100 text-red-700' }[status] || 'bg-gray-100 text-gray-600';
}

onMounted(fetchUser);
</script>
