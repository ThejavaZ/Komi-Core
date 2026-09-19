<template>
  <div class="space-y-6">
    <div v-if="successMsg" class="p-3 rounded-lg bg-green-50 text-green-700 text-sm">
      {{ successMsg }}
    </div>
    <div v-if="errorMsg" class="p-3 rounded-lg bg-red-50 text-red-700 text-sm">
      {{ errorMsg }}
    </div>

    <!-- Jobs Pendientes -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <h2 class="font-semibold text-gray-800">Jobs Pendientes</h2>
          <span class="text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700 font-medium">
            {{ pendingJobs.length }}
          </span>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 text-left text-gray-500">
              <th class="px-5 py-3 font-medium">ID</th>
              <th class="px-5 py-3 font-medium">Cola</th>
              <th class="px-5 py-3 font-medium">Clase</th>
              <th class="px-5 py-3 font-medium">Intentos</th>
              <th class="px-5 py-3 font-medium">Creado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="5" class="px-5 py-8 text-center text-gray-400">Cargando...</td>
            </tr>
            <tr v-for="job in pendingJobs" :key="job.id" class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3 text-xs text-gray-500 font-mono">{{ job.id }}</td>
              <td class="px-5 py-3 text-xs text-gray-500">{{ job.queue || 'default' }}</td>
              <td class="px-5 py-3 text-xs text-gray-700 font-mono">{{ job.job_class || job.payload?.displayName || '—' }}</td>
              <td class="px-5 py-3">
                <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ job.attempts ?? 0 }}</span>
              </td>
              <td class="px-5 py-3 text-xs text-gray-400">{{ job.created_at || '—' }}</td>
            </tr>
            <tr v-if="!loading && pendingJobs.length === 0">
              <td colspan="5" class="px-5 py-8 text-center text-gray-400">No hay jobs pendientes</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Jobs Fallidos -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <h2 class="font-semibold text-gray-800">Jobs Fallidos</h2>
          <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-medium">
            {{ failedJobs.length }}
          </span>
        </div>
        <button
          @click="showClearFailedDialog = true"
          :disabled="loadingClear"
          class="px-3 py-1.5 text-xs font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors disabled:opacity-50 flex items-center gap-1.5"
        >
          <TrashIcon class="w-3.5 h-3.5"/>
          {{ loadingClear ? 'Limpiando...' : 'Limpiar Fallidos' }}
        </button>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 text-left text-gray-500">
              <th class="px-5 py-3 font-medium">ID</th>
              <th class="px-5 py-3 font-medium">Clase</th>
              <th class="px-5 py-3 font-medium">Error</th>
              <th class="px-5 py-3 font-medium">Falló el</th>
              <th class="px-5 py-3 font-medium">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="5" class="px-5 py-8 text-center text-gray-400">Cargando...</td>
            </tr>
            <tr v-for="job in failedJobs" :key="job.id" class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3 text-xs text-gray-500 font-mono">{{ job.id }}</td>
              <td class="px-5 py-3 text-xs text-gray-700 font-mono">{{ job.job_class || job.payload?.displayName || '—' }}</td>
              <td class="px-5 py-3 max-w-xs">
                <p class="text-xs text-red-600 truncate" :title="job.exception || job.error || ''">
                  {{ (job.exception || job.error || '—').substring(0, 120) }}{{ (job.exception || job.error || '').length > 120 ? '...' : '' }}
                </p>
              </td>
              <td class="px-5 py-3 text-xs text-gray-400">{{ job.failed_at || '—' }}</td>
              <td class="px-5 py-3">
                <div class="flex items-center gap-2">
                  <button
                    @click="confirmRetryJob(job.id)"
                    class="px-2.5 py-1 text-xs font-medium text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition-colors flex items-center gap-1"
                  >
                    <ArrowPathIcon class="w-3.5 h-3.5"/>
                    Reintentar
                  </button>
                  <button
                    @click="confirmDeleteJob(job.id)"
                    class="px-2.5 py-1 text-xs font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors flex items-center gap-1"
                  >
                    <TrashIcon class="w-3.5 h-3.5"/>
                    Eliminar
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!loading && failedJobs.length === 0">
              <td colspan="5" class="px-5 py-8 text-center text-gray-400">No hay jobs fallidos</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <ConfirmDialog v-model="showClearFailedDialog" title="Limpiar jobs fallidos" subtitle="Se eliminiran todos los jobs fallidos de la cola. Esta accion no se puede deshacer." :icon="TrashIcon" confirm-text="Limpiar todo" @confirm="clearFailed"/>
    <ConfirmDialog v-model="showRetryJobDialog" title="Reintentar job" subtitle="El job sera reenviado a la cola para ser procesado nuevamente." :icon="ArrowPathIcon" confirm-text="Reintentar" confirm-class="text-white bg-indigo-600 hover:bg-indigo-700" @confirm="doRetryJob"/>
    <ConfirmDialog v-model="showDeleteJobDialog" title="Eliminar job" subtitle="El job sera eliminado permanentemente de la cola." :icon="TrashIcon" confirm-text="Eliminar" @confirm="deleteJob"/>
  </div>
</template>

<script setup>
import { showErrorToast } from '../api.js';
import { ref, onMounted } from 'vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import { api } from '../api';
import { TrashIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';

const loading = ref(true);
const loadingClear = ref(false);
const pendingJobs = ref([]);
const failedJobs = ref([]);
const successMsg = ref('');
const errorMsg = ref('');

// Dialog states
const showClearFailedDialog = ref(false);
const showDeleteJobDialog = ref(false);
const pendingDeleteJobId = ref(null);
const showRetryDialog = ref(false);
const pendingRetryJobId = ref(null);

function clearMessages() {
  successMsg.value = '';
  errorMsg.value = '';
}

async function fetchJobs() {
  loading.value = true;
  clearMessages();
  try {
    const res = await api('/admin/api/system/jobs');
    const data = await res.json();
    pendingJobs.value = data.pending || [];
    failedJobs.value = data.failed || [];
  } catch (e) {
    showErrorToast(e, 'JobsQueue');
    errorMsg.value = 'Error al cargar la cola de jobs.';
  } finally {
    loading.value = false;
  }
}

async function retryJob(id) {
  clearMessages();
  try {
    const res = await api(`/admin/api/system/jobs/${id}/retry`, {
      method: 'POST',
    });
    const data = await res.json();
    if (res.ok) {
      successMsg.value = 'Job reenviado a la cola.';
      fetchJobs();
    } else {
      errorMsg.value = data.message || 'Error al reintentar el job.';
    }
  } catch (e) {
    showErrorToast(e, 'JobsQueue');
    errorMsg.value = 'Error al reintentar el job.';
  }
}

function confirmDeleteJob(id) { pendingDeleteJobId.value = id; showDeleteJobDialog.value = true; }
function confirmRetryJob(id) { pendingRetryJobId.value = id; showRetryDialog.value = true; }

async function deleteJob() {
  const id = pendingDeleteJobId.value;
  showDeleteJobDialog.value = false;
  pendingDeleteJobId.value = null;
  clearMessages();
  try {
    const res = await api(`/admin/api/system/jobs/${id}`, { method: 'DELETE' });
    if (res.ok) {
      successMsg.value = 'Job eliminado.';
      fetchJobs();
    } else {
      const data = await res.json();
      errorMsg.value = data.message || 'Error al eliminar el job.';
    }
  } catch (e) {
    showErrorToast(e, 'JobsQueue');
    errorMsg.value = 'Error al eliminar el job.';
  }
}

async function doRetryJob() {
  const id = pendingRetryJobId.value;
  showRetryDialog.value = false;
  pendingRetryJobId.value = null;
  await retryJob(id);
}

async function clearFailed() {
  clearMessages();
  loadingClear.value = true;
  try {
    const res = await api('/admin/api/system/jobs/clear', { method: 'POST' });
    if (res.ok) {
      successMsg.value = 'Todos los jobs fallidos han sido eliminados.';
      fetchJobs();
    } else {
      const data = await res.json();
      errorMsg.value = data.message || 'Error al limpiar jobs fallidos.';
    }
  } catch (e) {
    showErrorToast(e, 'JobsQueue');
    errorMsg.value = 'Error al limpiar jobs fallidos.';
  } finally {
    loadingClear.value = false;
  }
}

onMounted(fetchJobs);
</script>
