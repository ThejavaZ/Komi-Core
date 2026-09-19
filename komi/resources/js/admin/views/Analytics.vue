<template>
  <div>
    <div class="flex items-center gap-3 mb-6">
      <h2 class="font-semibold text-gray-800 text-lg">Analytics</h2>
      <select
        v-model="range"
        class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
        @change="fetchAnalytics"
      >
        <option value="7">Últimos 7 días</option>
        <option value="14">Últimos 14 días</option>
        <option value="30">Últimos 30 días</option>
        <option value="90">Últimos 90 días</option>
      </select>
      <select
        v-model="communityFilter"
        class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
        @change="fetchAnalytics"
      >
        <option value="">Todas las comunidades</option>
        <option v-for="c in communities" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <label class="flex items-center gap-2">
        <input type="checkbox" v-model="compareMode" @change="fetchAnalytics" class="rounded" />
        <span class="text-sm">Comparar período anterior</span>
      </label>
    </div>

    <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div v-for="i in 4" :key="i" class="h-64 bg-gray-100 rounded-xl animate-pulse" />
    </div>

    <div v-else class="space-y-6">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Posts per day -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <h3 class="font-semibold text-gray-800 mb-4">Publicaciones por día</h3>
          <div class="h-48"><Line :data="postsChart" :options="chartOptions" /></div>
        </div>

        <!-- Users per day -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <h3 class="font-semibold text-gray-800 mb-4">Nuevos usuarios por día</h3>
          <div class="h-48"><Line :data="usersChart" :options="chartOptions" /></div>
        </div>

        <!-- Reactions per day -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <h3 class="font-semibold text-gray-800 mb-4">Reacciones por día</h3>
          <div class="h-48"><Bar :data="reactionsChart" :options="chartOptions" /></div>
        </div>

        <!-- Activity by hour -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <h3 class="font-semibold text-gray-800 mb-4">Actividad por hora</h3>
          <div class="h-48"><Bar :data="hourChart" :options="chartOptions" /></div>
        </div>

        <!-- Engagement Rate -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <h3 class="font-semibold text-gray-800 mb-4">Engagement Rate</h3>
          <div class="h-48"><Bar :data="engagementChart" :options="chartOptions" /></div>
        </div>

        <!-- Posts by community -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <h3 class="font-semibold text-gray-800 mb-4">Posts por comunidad</h3>
          <div v-if="Object.keys(data.posts_by_community || {}).length" class="h-48"><Bar :data="communityChart" :options="barHorizontalOptions" /></div>
          <p v-else class="text-sm text-gray-400 text-center py-8">Sin datos</p>
        </div>

        <!-- Reports by reason -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <h3 class="font-semibold text-gray-800 mb-4">Reportes por razón</h3>
          <div v-if="Object.keys(data.reports_by_reason || {}).length" class="h-48 flex items-center justify-center">
            <Doughnut :data="reportsReasonChart" :options="doughnutOptions" />
          </div>
          <p v-else class="text-sm text-gray-400 text-center py-8">Sin datos</p>
        </div>
      </div>

      <!-- Comparison -->
      <div v-if="data.comparison" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-800 mb-4">Comparación con período anterior</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="text-center">
            <p class="text-sm text-gray-500">Publicaciones</p>
            <p class="text-lg font-bold text-gray-900">{{ data.comparison.posts?.current ?? '—' }}</p>
            <p class="text-xs" :class="(data.comparison.posts?.change ?? 0) >= 0 ? 'text-green-600' : 'text-red-600'">
              {{ (data.comparison.posts?.change ?? 0) >= 0 ? '+' : '' }}{{ data.comparison.posts?.change ?? 0 }}%
            </p>
          </div>
          <div class="text-center">
            <p class="text-sm text-gray-500">Usuarios</p>
            <p class="text-lg font-bold text-gray-900">{{ data.comparison.users?.current ?? '—' }}</p>
            <p class="text-xs" :class="(data.comparison.users?.change ?? 0) >= 0 ? 'text-green-600' : 'text-red-600'">
              {{ (data.comparison.users?.change ?? 0) >= 0 ? '+' : '' }}{{ data.comparison.users?.change ?? 0 }}%
            </p>
          </div>
          <div class="text-center">
            <p class="text-sm text-gray-500">Reacciones</p>
            <p class="text-lg font-bold text-gray-900">{{ data.comparison.reactions?.current ?? '—' }}</p>
            <p class="text-xs" :class="(data.comparison.reactions?.change ?? 0) >= 0 ? 'text-green-600' : 'text-red-600'">
              {{ (data.comparison.reactions?.change ?? 0) >= 0 ? '+' : '' }}{{ data.comparison.reactions?.change ?? 0 }}%
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { showErrorToast } from '@/../api.js';
import { ref, computed, onMounted } from 'vue';
import { Line, Bar, Doughnut } from 'vue-chartjs';
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

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, ArcElement, Tooltip, Legend, Filler);

const loading = ref(true);
const range = ref('14');
const communityFilter = ref('');
const compareMode = ref(false);
const communities = ref([]);
const data = ref({});

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
};

const barHorizontalOptions = {
  responsive: true,
  maintainAspectRatio: false,
  indexAxis: 'y',
  plugins: { legend: { display: false } },
  scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } },
};

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 8 } } },
};

function buildLabelsAndData(items) {
  const map = {};
  if (Array.isArray(items)) items.forEach(i => { map[i.date] = i.total; });
  else Object.entries(items).forEach(([k, v]) => { map[k] = v; });

  const labels = [];
  const values = [];
  const days = parseInt(range.value);
  for (let d = days - 1; d >= 0; d--) {
    const date = new Date();
    date.setDate(date.getDate() - d);
    const key = date.toISOString().slice(0, 10);
    labels.push(date.toLocaleDateString('es', { day: '2-digit', month: 'short' }));
    values.push(map[key] || 0);
  }
  return { labels, data: values };
}

const postsChart = computed(() => {
  const { labels, data: d } = buildLabelsAndData(data.value.posts_per_day || []);
  return { labels, datasets: [{ data: d, borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)', fill: true, tension: 0.4 }] };
});

const usersChart = computed(() => {
  const { labels, data: d } = buildLabelsAndData(data.value.users_per_day || []);
  return { labels, datasets: [{ data: d, borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,0.1)', fill: true, tension: 0.4 }] };
});

const reactionsChart = computed(() => {
  const { labels, data: d } = buildLabelsAndData(data.value.reactions_per_day || []);
  return { labels, datasets: [{ data: d, backgroundColor: 'rgba(249,115,22,0.7)', borderRadius: 4 }] };
});

const hourChart = computed(() => {
  const raw = data.value.activity_by_hour || {};
  const labels = [];
  const values = [];
  for (let h = 0; h < 24; h++) {
    labels.push(`${h}:00`);
    values.push(raw[h] || 0);
  }
  return { labels, datasets: [{ data: values, backgroundColor: 'rgba(99,102,241,0.6)', borderRadius: 4 }] };
});

const communityChart = computed(() => {
  const raw = data.value.posts_by_community || {};
  return {
    labels: Object.keys(raw),
    datasets: [{ data: Object.values(raw), backgroundColor: 'rgba(34,197,94,0.7)', borderRadius: 4 }],
  };
});

const reportsReasonChart = computed(() => {
  const raw = data.value.reports_by_reason || {};
  const colors = ['#ef4444', '#f97316', '#f59e0b', '#6366f1', '#8b5cf6', '#22c55e', '#ec4899'];
  return {
    labels: Object.keys(raw),
    datasets: [{ data: Object.values(raw), backgroundColor: colors }],
  };
});

const engagementChart = computed(() => {
  const { labels, data: d } = buildLabelsAndData(data.value.engagement_per_day || []);
  return { labels, datasets: [{ data: d, backgroundColor: 'rgba(139,92,246,0.7)', borderRadius: 4 }] };
});

async function fetchAnalytics() {
  loading.value = true;
  try {
    const params = new URLSearchParams({ days: range.value });
    if (communityFilter.value) params.set('community_id', communityFilter.value);
    if (compareMode.value) params.set('compare', '1');
    const res = await fetch(`/admin/api/analytics?${params}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    data.value = await res.json();
  } catch (e) {
    showErrorToast(e, 'Analytics');
  } finally {
    loading.value = false;
  }
}

onMounted(async () => {
  fetchAnalytics();
  try {
    const res = await fetch('/admin/api/communities', { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    const d = await res.json();
    communities.value = d.data || [];
  } catch (e) {
    showErrorToast(e, 'Analytics');
  }
});
</script>
