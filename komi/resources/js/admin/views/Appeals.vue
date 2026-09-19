<template>
  <DataTable
    :columns="columns"
    :items="appeals"
    :loading="loading"
    :pagination="pagination"
    :sort-key="sortKey"
    :sort-dir="sortDir"
    empty-text="No hay apelaciones"
    @sort="onSort"
    @page="onPage"
    @per-page="onPerPage"
  >
    <template #header>
      <div class="flex items-center gap-3">
        <h2 class="font-semibold text-gray-800">Apelaciones</h2>
        <select v-model="statusFilter" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" @change="fetchAppeals(1)">
          <option value="">Todas</option>
          <option value="pending">Pendientes</option>
          <option value="approved">Aprobadas</option>
          <option value="rejected">Rechazadas</option>
        </select>
      </div>
    </template>
    <template #cell-user="{ item }">
      <div>
        <p class="text-sm font-medium text-gray-700">{{ item.user?.name || 'N/A' }}</p>
        <p class="text-xs text-gray-400">@{{ item.user?.username }}</p>
      </div>
    </template>
    <template #cell-type="{ value }">
      <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ value }}</span>
    </template>
    <template #cell-reason="{ value }">
      <p class="text-sm text-gray-700 truncate max-w-xs">{{ value }}</p>
    </template>
    <template #cell-status="{ value }">
      <span class="text-xs px-2 py-1 rounded-full" :class="statusBadge(value)">{{ value }}</span>
    </template>
    <template #cell-reviewer="{ item }">
      {{ item.reviewer?.name || '--' }}
    </template>
    <template #cell-actions="{ item }">
      <div v-if="item.status === 'pending'" class="flex gap-2">
        <button @click="openApproveDialog(item.id)" class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded hover:bg-green-100 flex items-center gap-1"><CheckCircleIcon class="w-3.5 h-3.5"/> Aprobar</button>
        <button @click="openRejectDialog(item.id)" class="text-xs bg-red-50 text-red-700 px-2 py-1 rounded hover:bg-red-100 flex items-center gap-1"><XCircleIcon class="w-3.5 h-3.5"/> Rechazar</button>
      </div>
      <span v-else class="text-xs text-gray-400">--</span>
    </template>
  </DataTable>

  <ConfirmDialog v-model="showApproveDialog" title="Aprobar apelacion" subtitle="La apelacion sera aprobada y el usuario sera restaurado." :icon="CheckCircleIcon" confirm-text="Aprobar" confirm-class="text-white bg-green-600 hover:bg-green-700" @confirm="confirmApprove"/>
  <ConfirmDialog v-model="showRejectDialog" title="Rechazar apelacion" subtitle="La apelacion sera rechazada permanentemente." :icon="XCircleIcon" confirm-text="Rechazar" @confirm="confirmReject"/>
</template>

<script setup>
import { showErrorToast } from '@/../api.js';
import { ref, onMounted } from 'vue';
import DataTable from '../components/DataTable.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import { api } from '../api';
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline';

const loading = ref(true);
const appeals = ref([]);
const pagination = ref(null);
const sortKey = ref('created_at');
const sortDir = ref('desc');
const statusFilter = ref('');

// Dialog states
const showApproveDialog = ref(false);
const pendingApproveId = ref(null);
const showRejectDialog = ref(false);
const pendingRejectId = ref(null);

const columns = [
  { key: 'id', label: 'ID' },
  { key: 'user', label: 'Usuario', sortable: false },
  { key: 'type', label: 'Tipo' },
  { key: 'reason', label: 'Razon', sortable: false },
  { key: 'status', label: 'Estado' },
  { key: 'reviewer', label: 'Revisado por', sortable: false },
  { key: 'actions', label: 'Acciones', sortable: false },
];

async function fetchAppeals(page = 1, perPage = 15) {
  loading.value = true;
  try {
    const params = new URLSearchParams({ page, per_page: perPage, sort: sortKey.value, direction: sortDir.value });
    if (statusFilter.value) params.set('status', statusFilter.value);
    const res = await api(`/admin/api/appeals?${params}`);
    const data = await res.json();
    appeals.value = data.data || [];
    pagination.value = { current_page: data.current_page, last_page: data.last_page, per_page: data.per_page, total: data.total };
  } catch (e) {
    showErrorToast(e, 'Appeals');
  } finally {
    loading.value = false;
  }
}

function openApproveDialog(id) { pendingApproveId.value = id; showApproveDialog.value = true; }
function openRejectDialog(id) { pendingRejectId.value = id; showRejectDialog.value = true; }

async function resolveAppeal(id, status) {
  await api(`/admin/api/appeals/${id}`, {
    method: 'PUT',
    body: JSON.stringify({ status }),
  });
  fetchAppeals(pagination.value?.current_page || 1, pagination.value?.per_page || 15);
}

async function confirmApprove() { const id = pendingApproveId.value; showApproveDialog.value = false; pendingApproveId.value = null; await resolveAppeal(id, 'approved'); }
async function confirmReject() { const id = pendingRejectId.value; showRejectDialog.value = false; pendingRejectId.value = null; await resolveAppeal(id, 'rejected'); }

function onSort({ key, dir }) { sortKey.value = key; sortDir.value = dir; fetchAppeals(pagination.value?.current_page || 1, pagination.value?.per_page || 15); }
function onPage(p) { fetchAppeals(p, pagination.value?.per_page || 15); }
function onPerPage(p) { fetchAppeals(1, p); }

function statusBadge(status) {
  return { pending: 'bg-yellow-100 text-yellow-700', approved: 'bg-green-100 text-green-700', rejected: 'bg-red-100 text-red-700' }[status] || 'bg-gray-100 text-gray-600';
}

onMounted(() => fetchAppeals());
</script>
