<template>
  <div>
    <!-- KPI Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
      <div
        v-for="kpi in kpis"
        :key="kpi.label"
        class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center gap-3"
      >
        <div
          class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
          :class="kpi.bgColor"
        >
          <component :is="kpi.icon" class="w-5 h-5" :class="kpi.iconColor" />
        </div>
        <div>
          <p class="text-xl font-bold text-gray-900">{{ kpi.value }}</p>
          <p class="text-xs text-gray-500">{{ kpi.label }}</p>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <!-- Posts per day -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-800 mb-4">Publicaciones (14 días)</h3>
        <div v-if="loading" class="h-48 bg-gray-100 rounded animate-pulse" />
        <div v-else class="h-48"><Line :data="postsChartData" :options="chartOptions" /></div>
      </div>

      <!-- Users per day -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-800 mb-4">Nuevos Usuarios (14 días)</h3>
        <div v-if="loading" class="h-48 bg-gray-100 rounded animate-pulse" />
        <div v-else class="h-48"><Line :data="usersChartData" :options="chartOptions" /></div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <!-- Posts by type (doughnut) -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-800 mb-4">Posts por Tipo</h3>
        <div v-if="loading" class="h-48 bg-gray-100 rounded animate-pulse" />
        <div v-else class="h-48 flex items-center justify-center">
          <Doughnut :data="postsByTypeChart" :options="doughnutOptions" />
        </div>
      </div>

      <!-- Users by status (doughnut) -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-800 mb-4">Usuarios por Estado</h3>
        <div v-if="loading" class="h-48 bg-gray-100 rounded animate-pulse" />
        <div v-else class="h-48 flex items-center justify-center">
          <Doughnut :data="usersByStatusChart" :options="doughnutOptions" />
        </div>
      </div>

      <!-- Engagement per day -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-800 mb-4">Reacciones (14 días)</h3>
        <div v-if="loading" class="h-48 bg-gray-100 rounded animate-pulse" />
        <div v-else class="h-48"><Bar :data="engagementChartData" :options="chartOptions" /></div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Trending Tags -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
          <h3 class="font-semibold text-gray-800">Tags en Tendencia</h3>
        </div>
        <div class="p-5">
          <ul v-if="trendingTags.length" class="space-y-3">
            <li v-for="(tag, i) in trendingTags" :key="tag.id" class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-gray-400 w-5">{{ i + 1 }}</span>
                <span class="text-sm font-medium text-gray-700">#{{ tag.name }}</span>
              </div>
              <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">{{ tag.posts_count }}</span>
            </li>
          </ul>
          <p v-else class="text-sm text-gray-400 text-center py-4">No hay tags</p>
        </div>
      </div>

      <!-- Top Posts -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
          <h3 class="font-semibold text-gray-800">Top Publicaciones</h3>
        </div>
        <div class="p-5">
          <ul v-if="topPosts.length" class="space-y-3">
            <li v-for="post in topPosts" :key="post.id" class="border-b border-gray-50 pb-3 last:border-0">
              <p class="text-sm text-gray-700">{{ post.content }}</p>
              <div class="flex items-center gap-3 mt-1 text-xs text-gray-400">
                <span>{{ post.author }}</span>
                <span>{{ post.likes_count }} likes</span>
                <span>{{ post.comments_count }} comments</span>
              </div>
            </li>
          </ul>
          <p v-else class="text-sm text-gray-400 text-center py-4">No hay posts</p>
        </div>
      </div>

      <!-- Active Communities -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
          <h3 class="font-semibold text-gray-800">Comunidades Activas</h3>
        </div>
        <div class="p-5">
          <ul v-if="activeCommunities.length" class="space-y-3">
            <li v-for="c in activeCommunities" :key="c.id" class="flex items-center justify-between">
              <span class="text-sm font-medium text-gray-700">{{ c.name }}</span>
              <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">{{ c.posts_count }} posts</span>
            </li>
          </ul>
          <p v-else class="text-sm text-gray-400 text-center py-4">No hay comunidades</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { showErrorToast } from '../api';
import { ref, computed, onMounted } from 'vue';
import { Line, Doughnut, Bar } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js';
import {
  UsersIcon,
  DocumentTextIcon,
  TagIcon,
  FlagIcon,
  ChatBubbleLeftIcon,
} from '@heroicons/vue/24/outline';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, ArcElement, Tooltip, Legend, Filler);

const loading = ref(true);
const stats = ref({});
const trendingTags = ref([]);
const topPosts = ref([]);
const activeCommunities = ref([]);
const postsPerDay = ref([]);
const usersPerDay = ref([]);
const reactionsPerDay = ref([]);
const postsByType = ref({});
const usersByStatus = ref({});

const kpis = computed(() => [
  { label: 'Usuarios', value: stats.value.total_users?.toLocaleString() || '0', icon: UsersIcon, bgColor: 'bg-blue-50', iconColor: 'text-blue-600' },
  { label: 'Publicaciones', value: stats.value.total_posts?.toLocaleString() || '0', icon: DocumentTextIcon, bgColor: 'bg-green-50', iconColor: 'text-green-600' },
  { label: 'Comentarios', value: stats.value.total_comments?.toLocaleString() || '0', icon: ChatBubbleLeftIcon, bgColor: 'bg-amber-50', iconColor: 'text-amber-600' },
  { label: 'Tags', value: stats.value.total_tags?.toLocaleString() || '0', icon: TagIcon, bgColor: 'bg-purple-50', iconColor: 'text-purple-600' },
  { label: 'Reportes', value: stats.value.pending_reports?.toLocaleString() || '0', icon: FlagIcon, bgColor: 'bg-red-50', iconColor: 'text-red-600' },
]);

function buildLabelsAndData(items) {
  const map = {};
  items.forEach(i => { map[i.date] = i.total; });
  const labels = [];
  const data = [];
  for (let d = 13; d >= 0; d--) {
    const date = new Date();
    date.setDate(date.getDate() - d);
    const key = date.toISOString().slice(0, 10);
    labels.push(date.toLocaleDateString('es', { day: '2-digit', month: 'short' }));
    data.push(map[key] || 0);
  }
  return { labels, data };
}

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
};

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } } },
};

const postsChartData = computed(() => {
  const { labels, data } = buildLabelsAndData(postsPerDay.value);
  return {
    labels,
    datasets: [{
      data,
      borderColor: '#6366f1',
      backgroundColor: 'rgba(99,102,241,0.1)',
      fill: true,
      tension: 0.4,
    }],
  };
});

const usersChartData = computed(() => {
  const { labels, data } = buildLabelsAndData(usersPerDay.value);
  return {
    labels,
    datasets: [{
      data,
      borderColor: '#22c55e',
      backgroundColor: 'rgba(34,197,94,0.1)',
      fill: true,
      tension: 0.4,
    }],
  };
});

const engagementChartData = computed(() => {
  const { labels, data } = buildLabelsAndData(reactionsPerDay.value);
  return {
    labels,
    datasets: [{
      data,
      backgroundColor: 'rgba(249,115,22,0.7)',
      borderRadius: 4,
    }],
  };
});

const postsByTypeChart = computed(() => {
  const types = postsByType.value;
  const colors = ['#6366f1', '#22c55e', '#f59e0b', '#8b5cf6'];
  return {
    labels: Object.keys(types),
    datasets: [{ data: Object.values(types), backgroundColor: colors }],
  };
});

const usersByStatusChart = computed(() => {
  const statuses = usersByStatus.value;
  const colorMap = { active: '#22c55e', pending: '#f59e0b', suspended: '#f97316', banned: '#ef4444' };
  return {
    labels: Object.keys(statuses),
    datasets: [{ data: Object.values(statuses), backgroundColor: Object.keys(statuses).map(s => colorMap[s] || '#94a3b8') }],
  };
});

onMounted(async () => {
  try {
    const res = await fetch('/admin/api/dashboard', { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    stats.value = data.stats;
    trendingTags.value = data.trending_tags;
    topPosts.value = data.top_posts;
    activeCommunities.value = data.active_communities;
    postsPerDay.value = data.posts_per_day;
    usersPerDay.value = data.users_per_day;
    reactionsPerDay.value = data.reactions_per_day;
    postsByType.value = data.posts_by_type;
    usersByStatus.value = data.users_by_status;
  } catch (e) {
    showErrorToast(e, 'Dashboard');
  } finally {
    loading.value = false;
  }
});
</script>
