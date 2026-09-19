<template>
  <div class="space-y-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
      <h3 class="font-semibold text-gray-800 mb-4">Filtros Avanzados</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Fecha desde</label>
          <input v-model="filters.date_from" type="date" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" @change="fetchReports(1)" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Fecha hasta</label>
          <input v-model="filters.date_to" type="date" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" @change="fetchReports(1)" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Estado</label>
          <select v-model="filters.status" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" @change="fetchReports(1)">
            <option value="">Todos</option>
            <option value="pending">Pendientes</option>
            <option value="resolved">Resueltos</option>
            <option value="dismissed">Descartados</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Tipo de contenido</label>
          <select v-model="filters.type" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" @change="fetchReports(1)">
            <option value="">Todos</option>
            <option value="App\Models\Post">Posts</option>
            <option value="App\Models\Comment">Comentarios</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Usuario reportado (ID)</label>
          <input v-model="filters.user_id" type="number" placeholder="ID del usuario" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" @change="fetchReports(1)" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Reportero (ID)</label>
          <input v-model="filters.reporter_id" type="number" placeholder="ID del reportero" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" @change="fetchReports(1)" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Min. reportes</label>
          <input v-model="filters.min_reports" type="number" min="2" placeholder="Ej: 3" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" @change="fetchReports(1)" />
        </div>
        <div class="flex items-end">
          <button @click="resetFilters" class="px-4 py-1.5 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Limpiar</button>
        </div>
      </div>
    </div>

    <DataTable
      :columns="columns"
      :items="reports"
      :loading="loading"
      :pagination="pagination"
      :sort-key="sortKey"
      :sort-dir="sortDir"
      empty-text="No hay reportes con estos filtros"
      @sort="onSort"
      @page="onPage"
      @per-page="onPerPage"
    >
      <template #header>
        <div class="flex items-center gap-3">
          <h2 class="font-semibold text-gray-800">Cola de Moderacion</h2>
          <span class="text-sm text-gray-500" v-if="pagination">{{ pagination.total }} reportes</span>
        </div>
      </template>
      <template #cell-reporter="{ item }">
        {{ item.reporter?.name || 'N/A' }}
      </template>
      <template #cell-reportable_type="{ item }">
        <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ typeName(item.reportable_type) }}</span>
      </template>
      <template #cell-content="{ item }">
        <p class="text-sm text-gray-700 truncate max-w-xs">{{ item.reportable?.content || 'Contenido eliminado' }}</p>
      </template>
      <template #cell-author="{ item }">
        <span v-if="item.reportable?.user" class="text-xs text-indigo-600 cursor-pointer hover:underline" @click="viewUser(item.reportable.user.id)">@{{ item.reportable.user.username }}</span>
        <span v-else class="text-xs text-gray-400">N/A</span>
      </template>
      <template #cell-reason="{ item }">
        <p class="text-sm text-gray-700">{{ item.reason }}</p>
        <p v-if="item.description" class="text-xs text-gray-400 mt-0.5">{{ item.description }}</p>
      </template>
      <template #cell-status="{ value }">
        <span class="text-xs px-2 py-1 rounded-full" :class="statusBadge(value)">{{ value }}</span>
      </template>
      <template #cell-created_at="{ value }">
        {{ formatDate(value) }}
      </template>
      <template #cell-actions="{ item }">
        <div v-if="item.status === 'pending'" class="flex gap-1 flex-wrap">
          <button @click="moderate(item, 'delete_post')" class="text-xs bg-red-50 text-red-700 px-2 py-1 rounded hover:bg-red-100">Eliminar</button>
          <button @click="moderate(item, 'warn_user')" class="text-xs bg-orange-50 text-orange-700 px-2 py-1 rounded hover:bg-orange-100">Advertir</button>
          <button @click="moderate(item, 'ban_user')" class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded hover:bg-red-200">Banear</button>
          <button @click="moderate(item, 'dismiss')" class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded hover:bg-gray-200">Descartar</button>
        </div>
        <span v-else class="text-xs text-gray-400">--</span>
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import DataTable from '../components/DataTable.vue';

const router = useRouter();
const loading = ref(true);
const reports = ref([]);
const pagination = ref(null);
const sortKey = ref('created_at');
const sortDir = ref('desc');

const filters = reactive({ date_from: '', date_to: '', status: 'pending', type: '', user_id: '', reporter_id: '', min_reports: '' });

const columns = [
  { key: 'id', label: 'ID' },
  { key: 'reporter', label: 'Reportado por', sortable: false },
  { key: 'reportable_type', label: 'Tipo' },
  { key: 'content', label: 'Contenido', sortable: false },
  { key: 'author', label: 'Autor', sortable: false },
  { key: 'reason', label: 'Razon', sortable: false },
  { key: 'status', label: 'Estado' },
  { key: 'created_at', label: 'Fecha' },
  { key: 'actions', label: 'Acciones', sortable: false },
];

async function fetchReports(page = 1, perPage = 15) {
  loading.value = true;
  try {
    const params = new URLSearchParams({ page, per_page: perPage, sort: sortKey.value, direction: sortDir.value });
    Object.entries(filters).forEach(([key, val]) => { if (val) params.set(key, val); });
    const res = await fetch(`/admin/api/moderation/search?${params}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    reports.value = data.data || [];
    pagination.value = { current_page: data.current_page, last_page: data.last_page, per_page: data.per_page, total: data.total };
  } catch (e) {
    console.error('Error:', e);
  } finally {
    loading.value = false;
  }
}

function resetFilters() {
  Object.assign(filters, { date_from: '', date_to: '', status: '', type: '', user_id: '', reporter_id: '', min_reports: '' });
  fetchReports(1);
}

async function moderate(report, action) {
  await fetch(`/admin/api/moderation/${report.id}/action`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    body: JSON.stringify({ action }),
  });
  fetchReports(pagination.value?.current_page || 1, pagination.value?.per_page || 15);
}

function viewUser(userId) { router.push({ name: 'admin.user-detail', params: { id: userId } }); }
function typeName(type) { return type?.split('\\').pop() || 'N/A'; }
function formatDate(date) { return date ? new Date(date).toLocaleDateString('es', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : ''; }
function statusBadge(status) { return { pending: 'bg-yellow-100 text-yellow-700', resolved: 'bg-green-100 text-green-700', dismissed: 'bg-gray-100 text-gray-600' }[status] || 'bg-gray-100 text-gray-600'; }

function onSort({ key, dir }) { sortKey.value = key; sortDir.value = dir; fetchReports(pagination.value?.current_page || 1, pagination.value?.per_page || 15); }
function onPage(p) { fetchReports(p, pagination.value?.per_page || 15); }
function onPerPage(p) { fetchReports(1, p); }

onMounted(() => fetchReports());
</script>
