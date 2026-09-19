<template>
  <div class="space-y-4">
    <!-- Advanced Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
      <h3 class="font-semibold text-gray-800 mb-4">Filtros Avanzados</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Fecha desde</label>
          <input v-model="filters.date_from" type="date" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" @change="fetchReports" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Fecha hasta</label>
          <input v-model="filters.date_to" type="date" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" @change="fetchReports" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Estado</label>
          <select v-model="filters.status" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" @change="fetchReports">
            <option value="">Todos</option>
            <option value="pending">Pendientes</option>
            <option value="resolved">Resueltos</option>
            <option value="dismissed">Descartados</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Tipo de contenido</label>
          <select v-model="filters.type" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" @change="fetchReports">
            <option value="">Todos</option>
            <option value="App\Models\Post">Posts</option>
            <option value="App\Models\Comment">Comentarios</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Usuario reportado (ID)</label>
          <input v-model="filters.user_id" type="number" placeholder="ID del usuario" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" @change="fetchReports" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Reportero (ID)</label>
          <input v-model="filters.reporter_id" type="number" placeholder="ID del reportero" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" @change="fetchReports" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Min. reportes del mismo contenido</label>
          <input v-model="filters.min_reports" type="number" min="2" placeholder="Ej: 3" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" @change="fetchReports" />
        </div>
        <div class="flex items-end">
          <button @click="resetFilters" class="px-4 py-1.5 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Limpiar</button>
        </div>
      </div>
    </div>

    <!-- Reports Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-800">Cola de Moderación</h2>
        <span class="text-sm text-gray-500">{{ totalResults }} reportes</span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 text-left text-gray-500">
              <th class="px-5 py-3 font-medium">ID</th>
              <th class="px-5 py-3 font-medium">Reportado por</th>
              <th class="px-5 py-3 font-medium">Tipo</th>
              <th class="px-5 py-3 font-medium">Contenido</th>
              <th class="px-5 py-3 font-medium">Autor</th>
              <th class="px-5 py-3 font-medium">Razón</th>
              <th class="px-5 py-3 font-medium">Estado</th>
              <th class="px-5 py-3 font-medium">Fecha</th>
              <th class="px-5 py-3 font-medium">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="9" class="px-5 py-8 text-center text-gray-400">Cargando...</td>
            </tr>
            <tr v-for="report in reports" :key="report.id" class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3 text-gray-500">#{{ report.id }}</td>
              <td class="px-5 py-3 text-gray-700">{{ report.reporter?.name || 'N/A' }}</td>
              <td class="px-5 py-3">
                <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ typeName(report.reportable_type) }}</span>
              </td>
              <td class="px-5 py-3 max-w-xs">
                <p class="text-sm text-gray-700 truncate">{{ report.reportable?.content || 'Contenido eliminado' }}</p>
              </td>
              <td class="px-5 py-3">
                <span v-if="report.reportable?.user" class="text-xs text-indigo-600 cursor-pointer hover:underline" @click="viewUser(report.reportable.user.id)">@{{ report.reportable.user.username }}</span>
                <span v-else class="text-xs text-gray-400">N/A</span>
              </td>
              <td class="px-5 py-3">
                <p class="text-sm text-gray-700">{{ report.reason }}</p>
                <p v-if="report.description" class="text-xs text-gray-400 mt-0.5">{{ report.description }}</p>
              </td>
              <td class="px-5 py-3">
                <span class="text-xs px-2 py-1 rounded-full" :class="statusBadge(report.status)">{{ report.status }}</span>
              </td>
              <td class="px-5 py-3 text-xs text-gray-400">{{ formatDate(report.created_at) }}</td>
              <td class="px-5 py-3">
                <div v-if="report.status === 'pending'" class="flex gap-1 flex-wrap">
                  <button @click="moderate(report, 'delete_post')" class="text-xs bg-red-50 text-red-700 px-2 py-1 rounded hover:bg-red-100">Eliminar</button>
                  <button @click="moderate(report, 'warn_user')" class="text-xs bg-orange-50 text-orange-700 px-2 py-1 rounded hover:bg-orange-100">Advertir</button>
                  <button @click="moderate(report, 'ban_user')" class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded hover:bg-red-200">Banear</button>
                  <button @click="moderate(report, 'dismiss')" class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded hover:bg-gray-200">Descartar</button>
                </div>
                <span v-else class="text-xs text-gray-400">—</span>
              </td>
            </tr>
            <tr v-if="!loading && reports.length === 0">
              <td colspan="9" class="px-5 py-8 text-center text-gray-400">No hay reportes con estos filtros</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="totalPages > 1" class="px-5 py-3 border-t border-gray-100 flex items-center justify-between text-sm text-gray-500">
        <span>Página {{ currentPage }} de {{ totalPages }}</span>
        <div class="flex gap-2">
          <button @click="goPage(currentPage - 1)" :disabled="currentPage <= 1" class="px-3 py-1 rounded border border-gray-200 hover:bg-gray-50 disabled:opacity-40">Anterior</button>
          <button @click="goPage(currentPage + 1)" :disabled="currentPage >= totalPages" class="px-3 py-1 rounded border border-gray-200 hover:bg-gray-50 disabled:opacity-40">Siguiente</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const loading = ref(true);
const reports = ref([]);
const currentPage = ref(1);
const totalPages = ref(1);
const totalResults = ref(0);

const filters = reactive({
  date_from: '',
  date_to: '',
  status: 'pending',
  type: '',
  user_id: '',
  reporter_id: '',
  min_reports: '',
});

async function fetchReports(page = 1) {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, val]) => {
      if (val) params.set(key, val);
    });
    params.set('page', page);
    const res = await fetch(`/admin/api/moderation/search?${params}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    reports.value = data.data || [];
    currentPage.value = data.current_page || 1;
    totalPages.value = data.last_page || 1;
    totalResults.value = data.total || 0;
  } catch (e) {
    console.error('Error:', e);
  } finally {
    loading.value = false;
  }
}

function resetFilters() {
  Object.assign(filters, { date_from: '', date_to: '', status: '', type: '', user_id: '', reporter_id: '', min_reports: '' });
  fetchReports();
}

async function moderate(report, action) {
  try {
    await fetch(`/admin/api/moderation/${report.id}/action`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ action }),
    });
    fetchReports(currentPage.value);
  } catch (e) {
    console.error('Error:', e);
  }
}

function viewUser(userId) {
  router.push({ name: 'admin.user-detail', params: { id: userId } });
}

function typeName(type) {
  if (!type) return 'N/A';
  return type.split('\\').pop();
}

function formatDate(date) {
  if (!date) return '';
  return new Date(date).toLocaleDateString('es', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function statusBadge(status) {
  return { pending: 'bg-yellow-100 text-yellow-700', resolved: 'bg-green-100 text-green-700', dismissed: 'bg-gray-100 text-gray-600' }[status] || 'bg-gray-100 text-gray-600';
}

function goPage(page) {
  if (page >= 1 && page <= totalPages.value) fetchReports(page);
}

onMounted(() => fetchReports());
</script>
