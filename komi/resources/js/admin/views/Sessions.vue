<template>
  <div class="space-y-6">
    <div v-if="successMsg" class="p-3 rounded-lg bg-green-50 text-green-700 text-sm">
      {{ successMsg }}
    </div>
    <div v-if="errorMsg" class="p-3 rounded-lg bg-red-50 text-red-700 text-sm">
      {{ errorMsg }}
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <h2 class="font-semibold text-gray-800">Sesiones Activas</h2>
          <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-medium">
            {{ sessions.length }}
          </span>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 text-left text-gray-500">
              <th class="px-5 py-3 font-medium">IP</th>
              <th class="px-5 py-3 font-medium">Navegador</th>
              <th class="px-5 py-3 font-medium">OS</th>
              <th class="px-5 py-3 font-medium">Última actividad</th>
              <th class="px-5 py-3 font-medium">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="5" class="px-5 py-8 text-center text-gray-400">Cargando...</td>
            </tr>
            <tr
              v-for="session in sessions"
              :key="session.id"
              class="border-b border-gray-50 hover:bg-gray-50 transition-colors"
            >
              <td class="px-5 py-3 text-xs text-gray-700 font-mono">{{ session.ip_address || '—' }}</td>
              <td class="px-5 py-3 text-xs text-gray-600">{{ session.browser || '—' }}</td>
              <td class="px-5 py-3 text-xs text-gray-600">{{ session.os || '—' }}</td>
              <td class="px-5 py-3 text-xs text-gray-400">{{ session.last_activity || '—' }}</td>
              <td class="px-5 py-3">
                <div class="flex items-center gap-2">
                  <span
                    v-if="session.is_current"
                    class="text-xs px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 font-medium"
                  >
                    Sesión actual
                  </span>
                  <button
                    v-else
                    @click="confirmRevokeSession(session.id)"
                    class="px-2.5 py-1 text-xs font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors flex items-center gap-1"
                  >
                    <XCircleIcon class="w-3.5 h-3.5"/>
                    Cerrar sesión
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!loading && sessions.length === 0">
              <td colspan="5" class="px-5 py-8 text-center text-gray-400">No hay sesiones activas</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <ConfirmDialog v-model="showRevokeDialog" title="Cerrar sesion" subtitle="Esta sesion sera cerrada y el usuario tendra que iniciar sesion nuevamente." :icon="XCircleIcon" confirm-text="Cerrar sesion" @confirm="revokeSession"/>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import { api } from '../api';
import { XCircleIcon } from '@heroicons/vue/24/outline';

const loading = ref(true);
const sessions = ref([]);
const successMsg = ref('');
const errorMsg = ref('');

// Dialog states
const showRevokeDialog = ref(false);
const pendingRevokeId = ref(null);

function clearMessages() {
  successMsg.value = '';
  errorMsg.value = '';
}

async function fetchSessions() {
  loading.value = true;
  clearMessages();
  try {
    const res = await api('/admin/api/sessions');
    const data = await res.json();
    sessions.value = data.sessions || data.data || data || [];
  } catch {
    errorMsg.value = 'Error al cargar las sesiones.';
  } finally {
    loading.value = false;
  }
}

function confirmRevokeSession(id) { pendingRevokeId.value = id; showRevokeDialog.value = true; }

async function revokeSession() {
  const id = pendingRevokeId.value;
  showRevokeDialog.value = false;
  pendingRevokeId.value = null;
  clearMessages();
  try {
    const res = await api(`/admin/api/sessions/${id}`, { method: 'DELETE' });
    if (res.ok) {
      successMsg.value = 'Sesión cerrada correctamente.';
      fetchSessions();
    } else {
      const data = await res.json();
      errorMsg.value = data.message || 'Error al cerrar la sesión.';
    }
  } catch {
    errorMsg.value = 'Error al cerrar la sesión.';
  }
}

onMounted(fetchSessions);
</script>
