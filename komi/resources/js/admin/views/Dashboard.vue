<template>
  <div>
    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
      <div
        v-for="kpi in kpis"
        :key="kpi.label"
        class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4"
      >
        <div
          class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0"
          :class="kpi.bgColor"
        >
          <component :is="kpi.icon" class="w-6 h-6" :class="kpi.iconColor" />
        </div>
        <div>
          <p class="text-2xl font-bold text-gray-900">{{ kpi.value }}</p>
          <p class="text-sm text-gray-500">{{ kpi.label }}</p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Trending Tags -->
      <div class="lg:col-span-1 bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
          <h2 class="font-semibold text-gray-800">Tags en Tendencia</h2>
        </div>
        <div class="p-5">
          <div v-if="loading" class="space-y-3">
            <div v-for="i in 5" :key="i" class="h-8 bg-gray-100 rounded animate-pulse" />
          </div>
          <ul v-else class="space-y-3">
            <li
              v-for="(tag, index) in trendingTags"
              :key="tag.id"
              class="flex items-center justify-between"
            >
              <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-gray-400 w-5">{{ index + 1 }}</span>
                <span class="text-sm font-medium text-gray-700">#{{ tag.name }}</span>
              </div>
              <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">
                {{ tag.posts_count }} posts
              </span>
            </li>
            <li v-if="trendingTags.length === 0 && !loading" class="text-sm text-gray-400 text-center py-4">
              No hay tags disponibles
            </li>
          </ul>
        </div>
      </div>

      <!-- Recent Posts -->
      <div class="lg:col-span-1 bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
          <h2 class="font-semibold text-gray-800">Publicaciones Recientes</h2>
        </div>
        <div class="p-5">
          <div v-if="loading" class="space-y-4">
            <div v-for="i in 5" :key="i" class="h-14 bg-gray-100 rounded animate-pulse" />
          </div>
          <ul v-else class="space-y-3">
            <li
              v-for="post in recentPosts"
              :key="post.id"
              class="border-b border-gray-50 pb-3 last:border-0 last:pb-0"
            >
              <p class="text-sm text-gray-700 line-clamp-2">{{ post.content }}</p>
              <div class="flex items-center gap-2 mt-1">
                <span class="text-xs text-gray-400">{{ post.author }}</span>
                <span class="text-xs text-gray-300">&middot;</span>
                <span class="text-xs text-gray-400">{{ post.type }}</span>
                <span class="text-xs text-gray-300">&middot;</span>
                <span class="text-xs text-gray-400">{{ post.created_at }}</span>
              </div>
            </li>
            <li v-if="recentPosts.length === 0 && !loading" class="text-sm text-gray-400 text-center py-4">
              No hay publicaciones
            </li>
          </ul>
        </div>
      </div>

      <!-- Recent Users -->
      <div class="lg:col-span-1 bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
          <h2 class="font-semibold text-gray-800">Usuarios Recientes</h2>
        </div>
        <div class="p-5">
          <div v-if="loading" class="space-y-4">
            <div v-for="i in 5" :key="i" class="h-10 bg-gray-100 rounded animate-pulse" />
          </div>
          <ul v-else class="space-y-3">
            <li
              v-for="user in recentUsers"
              :key="user.id"
              class="flex items-center justify-between border-b border-gray-50 pb-3 last:border-0 last:pb-0"
            >
              <div>
                <p class="text-sm font-medium text-gray-700">{{ user.name }}</p>
                <p class="text-xs text-gray-400">@{{ user.username }}</p>
              </div>
              <span
                class="text-xs px-2 py-1 rounded-full"
                :class="statusBadge(user.status)"
              >
                {{ user.status }}
              </span>
            </li>
            <li v-if="recentUsers.length === 0 && !loading" class="text-sm text-gray-400 text-center py-4">
              No hay usuarios
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Posts by type -->
    <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
      <h2 class="font-semibold text-gray-800 mb-4">Publicaciones por Tipo</h2>
      <div v-if="loading" class="h-24 bg-gray-100 rounded animate-pulse" />
      <div v-else class="flex gap-6">
        <div
          v-for="(count, type) in postsByType"
          :key="type"
          class="flex-1 text-center p-4 rounded-lg bg-gray-50"
        >
          <p class="text-2xl font-bold text-gray-900">{{ count }}</p>
          <p class="text-sm text-gray-500 capitalize">{{ type }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import {
  UsersIcon,
  DocumentTextIcon,
  TagIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const loading = ref(true);
const stats = ref({});
const trendingTags = ref([]);
const recentPosts = ref([]);
const recentUsers = ref([]);
const postsByType = ref({});

const kpis = ref([]);

onMounted(async () => {
  try {
    const response = await fetch('/admin/api/dashboard', {
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    const data = await response.json();

    stats.value = data.stats;
    trendingTags.value = data.trending_tags;
    recentPosts.value = data.recent_posts;
    recentUsers.value = data.recent_users;
    postsByType.value = data.posts_by_type;

    kpis.value = [
      {
        label: 'Total Usuarios',
        value: data.stats.total_users?.toLocaleString() || '0',
        icon: UsersIcon,
        bgColor: 'bg-blue-50',
        iconColor: 'text-blue-600',
      },
      {
        label: 'Total Publicaciones',
        value: data.stats.total_posts?.toLocaleString() || '0',
        icon: DocumentTextIcon,
        bgColor: 'bg-green-50',
        iconColor: 'text-green-600',
      },
      {
        label: 'Tags Registrados',
        value: data.stats.total_tags?.toLocaleString() || '0',
        icon: TagIcon,
        bgColor: 'bg-purple-50',
        iconColor: 'text-purple-600',
      },
      {
        label: 'Total Reportes',
        value: data.stats.total_reports?.toLocaleString() || '0',
        icon: ExclamationTriangleIcon,
        bgColor: 'bg-amber-50',
        iconColor: 'text-amber-600',
      },
    ];
  } catch (error) {
    console.error('Error loading dashboard:', error);
  } finally {
    loading.value = false;
  }
});

function statusBadge(status) {
  const map = {
    active: 'bg-green-100 text-green-700',
    pending: 'bg-yellow-100 text-yellow-700',
    suspended: 'bg-orange-100 text-orange-700',
    banned: 'bg-red-100 text-red-700',
  };
  return map[status] || 'bg-gray-100 text-gray-600';
}
</script>
