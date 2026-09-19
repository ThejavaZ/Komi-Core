<template>
  <div class="max-w-2xl space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
      <h2 class="text-lg font-semibold text-gray-800 mb-4">Configuración General</h2>

      <div v-if="successMsg" class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm">
        {{ successMsg }}
      </div>
      <div v-if="errorMsg" class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm">
        {{ errorMsg }}
      </div>

      <div v-if="loading" class="py-12 text-center text-gray-400">Cargando configuración...</div>

      <form v-else @submit.prevent="saveSettings" class="space-y-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la plataforma</label>
          <input
            v-model="settings.platform_name"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
          <textarea
            v-model="settings.platform_description"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Máx. posts por día</label>
            <input
              v-model.number="settings.max_posts_per_day"
              type="number"
              min="0"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Máx. caracteres por post</label>
            <input
              v-model.number="settings.max_chars_per_post"
              type="number"
              min="0"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>
        </div>

        <div class="flex items-center justify-between py-3 border-t border-gray-100">
          <div>
            <p class="text-sm font-medium text-gray-700">Registro habilitado</p>
            <p class="text-xs text-gray-400">Permitir que nuevos usuarios se registren</p>
          </div>
          <button
            type="button"
            @click="settings.registration_enabled = !settings.registration_enabled"
            :class="[
              'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
              settings.registration_enabled ? 'bg-indigo-600' : 'bg-gray-200',
            ]"
          >
            <span
              :class="[
                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                settings.registration_enabled ? 'translate-x-5' : 'translate-x-0',
              ]"
            />
          </button>
        </div>

        <div class="flex items-center justify-between py-3 border-t border-gray-100">
          <div>
            <p class="text-sm font-medium text-gray-700">Modo mantenimiento</p>
            <p class="text-xs text-gray-400">Bloquear acceso no administrativo al sitio</p>
          </div>
          <button
            type="button"
            @click="settings.maintenance_mode = !settings.maintenance_mode"
            :class="[
              'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
              settings.maintenance_mode ? 'bg-indigo-600' : 'bg-gray-200',
            ]"
          >
            <span
              :class="[
                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                settings.maintenance_mode ? 'translate-x-5' : 'translate-x-0',
              ]"
            />
          </button>
        </div>

        <div class="pt-4 border-t border-gray-100 flex justify-end">
          <button
            type="submit"
            :disabled="saving"
            class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50"
          >
            {{ saving ? 'Guardando...' : 'Guardar cambios' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { showErrorToast } from '../api.js';
import { ref, reactive, onMounted } from 'vue';
import { api } from '../api';

const loading = ref(true);
const saving = ref(false);
const successMsg = ref('');
const errorMsg = ref('');

const settings = reactive({
  platform_name: '',
  platform_description: '',
  max_posts_per_day: 50,
  max_chars_per_post: 500,
  registration_enabled: true,
  maintenance_mode: false,
});

function clearMessages() {
  successMsg.value = '';
  errorMsg.value = '';
}

async function fetchSettings() {
  loading.value = true;
  try {
    const res = await api('/admin/api/config');
    const data = await res.json();
    Object.assign(settings, data);
  } catch (e) {
    showErrorToast(e, 'GeneralSettings');
    errorMsg.value = 'Error al cargar la configuración.';
  } finally {
    loading.value = false;
  }
}

async function saveSettings() {
  clearMessages();
  saving.value = true;
  try {
    const res = await api('/admin/api/config', {
      method: 'PUT',
      body: JSON.stringify(settings),
    });
    const data = await res.json();
    if (res.ok) {
      successMsg.value = 'Configuración guardada correctamente.';
    } else {
      errorMsg.value = data.message || 'Error al guardar la configuración.';
    }
  } catch (e) {
    showErrorToast(e, 'GeneralSettings');
    errorMsg.value = 'Error al guardar la configuración.';
  } finally {
    saving.value = false;
  }
}

onMounted(fetchSettings);
</script>
