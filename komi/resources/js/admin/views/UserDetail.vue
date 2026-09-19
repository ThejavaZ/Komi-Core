<template>
  <div v-if="loading" class="space-y-4">
    <div class="h-32 bg-gray-100 rounded-xl animate-pulse" />
    <div class="h-48 bg-gray-100 rounded-xl animate-pulse" />
    <div class="h-64 bg-gray-100 rounded-xl animate-pulse" />
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
            <span v-if="user.is_shadowbanned" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">Shadowban</span>
            <span v-if="user.is_global_admin" class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full">Admin</span>
          </div>
          <p class="text-sm text-gray-500">@{{ user.username }} &middot; {{ user.email }}</p>
          <p v-if="user.bio" class="text-sm text-gray-600 mt-2">{{ user.bio }}</p>
          <div class="flex items-center gap-4 mt-3">
            <span class="text-xs px-2 py-1 rounded-full" :class="statusBadge(user.status)">{{ user.status }}</span>
            <span v-if="user.banned_until" class="text-xs text-red-500">Baneado hasta: {{ formatDate(user.banned_until) }}</span>
            <span class="text-xs text-gray-400">Registro: {{ formatDate(user.created_at) }}</span>
          </div>
        </div>
        <div class="flex flex-wrap gap-2">
          <button v-if="user.status === 'active'" @click="dialog = 'suspend'" class="inline-flex items-center gap-1.5 text-xs bg-orange-50 text-orange-700 px-3 py-1.5 rounded-lg hover:bg-orange-100">
            <ExclamationTriangleIcon class="w-4 h-4" />
            Suspender
          </button>
          <button v-if="user.status === 'active' || user.status === 'suspended'" @click="dialog = 'ban'" class="inline-flex items-center gap-1.5 text-xs bg-red-50 text-red-700 px-3 py-1.5 rounded-lg hover:bg-red-100">
            <NoSymbolIcon class="w-4 h-4" />
            Banear
          </button>
          <button v-if="user.status === 'suspended' || user.status === 'banned'" @click="dialog = 'activate'" class="inline-flex items-center gap-1.5 text-xs bg-green-50 text-green-700 px-3 py-1.5 rounded-lg hover:bg-green-100">
            <CheckCircleIcon class="w-4 h-4" />
            Activar
          </button>
          <button @click="dialog = user.is_verified ? 'unverify' : 'verify'" class="inline-flex items-center gap-1.5 text-xs bg-blue-50 text-blue-700 px-3 py-1.5 rounded-lg hover:bg-blue-100">
            <CheckBadgeIcon class="w-4 h-4" />
            {{ user.is_verified ? 'Quitar verificación' : 'Verificar' }}
          </button>
          <button @click="dialog = user.is_shadowbanned ? 'unshadowban' : 'shadowban'" class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg" :class="user.is_shadowbanned ? 'bg-purple-100 text-purple-700 hover:bg-purple-200' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'">
            <EyeSlashIcon class="w-4 h-4" />
            {{ user.is_shadowbanned ? 'Quitar shadowban' : 'Shadowban' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Ban Info Card (shown when user is banned or suspended) -->
    <div v-if="user.status === 'banned' || user.status === 'suspended'" class="rounded-xl border p-5 mb-6" :class="user.banned_until ? 'bg-orange-50 border-orange-200' : 'bg-red-50 border-red-200'">
      <div class="flex items-start gap-3">
        <div class="flex-shrink-0 mt-0.5">
          <ClockIcon v-if="user.banned_until" class="w-5 h-5 text-orange-600" />
          <NoSymbolIcon v-else class="w-5 h-5 text-red-600" />
        </div>
        <div class="flex-1">
          <h3 class="font-semibold text-sm" :class="user.banned_until ? 'text-orange-800' : 'text-red-800'">
            {{ user.banned_until ? 'Ban Temporal' : (user.status === 'suspended' ? 'Suspension' : 'Baneo Permanente') }}
          </h3>
          <div class="mt-2 space-y-1 text-sm" :class="user.banned_until ? 'text-orange-700' : 'text-red-700'">
            <p v-if="user.banned_until">
              <span class="font-medium">Expira:</span> {{ formatDate(user.banned_until) }}
            </p>
            <p v-if="banRemaining">
              <span class="font-medium">Tiempo restante:</span> {{ banRemaining }}
            </p>
            <p v-if="lastBanLog">
              <span class="font-medium">Baneado por:</span> {{ lastBanLog.admin_name }}
            </p>
            <p v-if="lastBanLog?.new_values?.reason">
              <span class="font-medium">Motivo:</span> {{ lastBanLog.new_values.reason }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Temp ban section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
      <h3 class="font-semibold text-gray-800 mb-3">Ban temporal</h3>
      <div class="flex items-center gap-3 flex-wrap">
        <button @click="openTempBan('24h')" class="inline-flex items-center gap-1.5 text-xs bg-orange-50 text-orange-700 px-3 py-1.5 rounded-lg hover:bg-orange-100">
          <ClockIcon class="w-4 h-4" />
          24 horas
        </button>
        <button @click="openTempBan('7d')" class="inline-flex items-center gap-1.5 text-xs bg-orange-50 text-orange-700 px-3 py-1.5 rounded-lg hover:bg-orange-100">
          <ClockIcon class="w-4 h-4" />
          7 dias
        </button>
        <button @click="openTempBan('30d')" class="inline-flex items-center gap-1.5 text-xs bg-orange-50 text-orange-700 px-3 py-1.5 rounded-lg hover:bg-orange-100">
          <ClockIcon class="w-4 h-4" />
          30 dias
        </button>
        <div class="flex items-center gap-2">
          <input v-model="customBanHours" type="number" min="1" placeholder="Horas" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm w-24 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
          <button @click="openTempBan('custom')" class="inline-flex items-center gap-1.5 text-xs bg-orange-50 text-orange-700 px-3 py-1.5 rounded-lg hover:bg-orange-100">
            <ClockIcon class="w-4 h-4" />
            Aplicar
          </button>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4 mb-6">
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
        <p class="text-2xl font-bold text-gray-900">{{ user.stats?.posts ?? 0 }}</p>
        <p class="text-xs text-gray-500">Publicaciones</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
        <p class="text-2xl font-bold text-gray-900">{{ user.stats?.comments ?? 0 }}</p>
        <p class="text-xs text-gray-500">Comentarios</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
        <p class="text-2xl font-bold text-gray-900">{{ user.stats?.reactions ?? 0 }}</p>
        <p class="text-xs text-gray-500">Reacciones</p>
      </div>
    </div>

    <!-- Communities -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
      <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">Comunidades</h3>
      </div>
      <div class="p-5">
        <ul v-if="user.communities && user.communities.length" class="space-y-3">
          <li v-for="community in user.communities" :key="community.id" class="flex items-center justify-between border-b border-gray-50 pb-3 last:border-0">
            <div>
              <p class="text-sm font-medium text-gray-800">{{ community.name }}</p>
              <p class="text-xs text-gray-400">@{{ community.slug }}</p>
            </div>
            <span class="text-xs text-gray-400">{{ community.posts_count ?? 0 }} posts</span>
          </li>
        </ul>
        <p v-else class="text-sm text-gray-400 text-center py-4">No pertenece a ninguna comunidad</p>
      </div>
    </div>

    <!-- Moderation History -->
    <div v-if="moderationHistory.length" class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
      <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">Historial de Moderacion</h3>
      </div>
      <div class="divide-y divide-gray-50">
        <div v-for="log in moderationHistory" :key="log.id" class="px-5 py-3 flex items-start gap-3">
          <div class="flex-shrink-0 mt-0.5">
            <div class="w-7 h-7 rounded-full flex items-center justify-center" :class="actionBg(log.action)">
              <component :is="actionIcon(log.action)" class="w-3.5 h-3.5" :class="actionIconColor(log.action)" />
            </div>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-xs font-semibold text-gray-700">{{ actionLabel(log.action) }}</span>
              <span class="text-xs text-gray-400">por {{ log.admin_name }}</span>
              <span class="text-xs text-gray-400">&middot; {{ log.created_at }}</span>
            </div>
            <p v-if="log.new_values?.reason" class="text-xs text-gray-500 mt-1">
              <span class="font-medium">Motivo:</span> {{ log.new_values.reason }}
            </p>
            <p v-if="log.new_values?.duration" class="text-xs text-gray-500">
              <span class="font-medium">Duracion:</span> {{ log.new_values.duration }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Reports against user -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
      <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">Reportes contra el usuario</h3>
      </div>
      <div class="overflow-x-auto">
        <table v-if="user.reports_against && user.reports_against.length" class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 text-left text-gray-500">
              <th class="px-5 py-3 font-medium">Fecha</th>
              <th class="px-5 py-3 font-medium">Reportado por</th>
              <th class="px-5 py-3 font-medium">Tipo</th>
              <th class="px-5 py-3 font-medium">Razon</th>
              <th class="px-5 py-3 font-medium">Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="report in user.reports_against" :key="report.id" class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3 text-gray-400 text-xs">{{ formatDate(report.created_at) }}</td>
              <td class="px-5 py-3 text-gray-700">{{ report.reporter?.name ?? 'Desconocido' }}</td>
              <td class="px-5 py-3">
                <span class="px-1.5 py-0.5 rounded bg-gray-100 text-xs">{{ report.reportable_type?.split('\\').pop() ?? 'N/A' }}</span>
              </td>
              <td class="px-5 py-3 text-gray-600 text-xs">{{ report.reason }}</td>
              <td class="px-5 py-3">
                <span class="text-xs px-2 py-1 rounded-full" :class="reportStatusBadge(report.status)">{{ report.status }}</span>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-else class="text-sm text-gray-400 text-center py-4">No hay reportes contra este usuario</p>
      </div>
    </div>

    <!-- Recent posts -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">Publicaciones recientes</h3>
      </div>
      <div class="p-5">
        <ul v-if="user.recent_posts && user.recent_posts.length" class="space-y-3">
          <li v-for="post in user.recent_posts" :key="post.id" class="border-b border-gray-50 pb-3 last:border-0">
            <p class="text-sm text-gray-700">{{ post.content }}</p>
            <div class="flex items-center gap-3 mt-1 text-xs text-gray-400">
              <span class="px-1.5 py-0.5 rounded bg-gray-100">{{ post.type }}</span>
              <span>{{ post.likes_count }} likes</span>
              <span>{{ post.comments_count }} comments</span>
              <span>{{ formatDateShort(post.created_at) }}</span>
            </div>
          </li>
        </ul>
        <p v-else class="text-sm text-gray-400 text-center py-4">No tiene publicaciones</p>
      </div>
    </div>

    <!-- ======== DIALOGS ======== -->

    <ConfirmDialog
      v-model="dialogSuspends"
      title="Suspender usuario"
      :subtitle="`Se suspendera a @${user.username}. No podra publicar ni comentar.`"
      :icon="ExclamationTriangleIcon"
      icon-bg="bg-orange-100"
      icon-color="text-orange-600"
      confirm-text="Suspender"
      confirm-class="text-white bg-orange-600 hover:bg-orange-700"
      :show-reason="true"
      reason-label="Motivo de la suspension"
      reason-placeholder="Ej: Contenido ofensivo, spam..."
      @confirm="confirmSuspend"
    />

    <ConfirmDialog
      v-model="dialogBan"
      title="Banear usuario"
      :subtitle="`Se baneara a @${user.username}. No podra acceder a la plataforma.`"
      :icon="NoSymbolIcon"
      icon-bg="bg-red-100"
      icon-color="text-red-600"
      confirm-text="Banear"
      confirm-class="text-white bg-red-600 hover:bg-red-700"
      :show-reason="true"
      reason-label="Motivo del baneo"
      reason-placeholder="Ej: Acoso, spam grave, violacion de normas..."
      @confirm="confirmBan"
    />

    <ConfirmDialog
      v-model="dialogActivate"
      title="Activar usuario"
      :subtitle="`Se reactivara la cuenta de @${user.username}.`"
      :icon="CheckCircleIcon"
      icon-bg="bg-green-100"
      icon-color="text-green-600"
      confirm-text="Activar"
      confirm-class="text-white bg-green-600 hover:bg-green-700"
      @confirm="confirmActivate"
    />

    <ConfirmDialog
      v-model="dialogVerify"
      :title="user?.is_verified ? 'Quitar verificacion' : 'Verificar usuario'"
      :subtitle="user?.is_verified ? `Se quitara la verificacion a @${user?.username}.` : `Se verificara la cuenta de @${user?.username}.`"
      :icon="user?.is_verified ? XCircleIcon : CheckBadgeIcon"
      :icon-bg="user?.is_verified ? 'bg-gray-100' : 'bg-blue-100'"
      :icon-color="user?.is_verified ? 'text-gray-600' : 'text-blue-600'"
      :confirm-text="user?.is_verified ? 'Quitar' : 'Verificar'"
      :confirm-class="user?.is_verified ? 'text-white bg-gray-600 hover:bg-gray-700' : 'text-white bg-blue-600 hover:bg-blue-700'"
      @confirm="confirmVerify"
    />

    <ConfirmDialog
      v-model="dialogShadowban"
      :title="user?.is_shadowbanned ? 'Quitar shadowban' : 'Aplicar shadowban'"
      :subtitle="user?.is_shadowbanned ? `@${user?.username} dejara de estar en shadowban.` : `@${user?.username} no podra ser visto por otros usuarios.`"
      :icon="user?.is_shadowbanned ? CheckCircleIcon : EyeSlashIcon"
      :icon-bg="user?.is_shadowbanned ? 'bg-purple-100' : 'bg-gray-100'"
      :icon-color="user?.is_shadowbanned ? 'text-purple-600' : 'text-gray-600'"
      :confirm-text="user?.is_shadowbanned ? 'Quitar' : 'Aplicar'"
      :confirm-class="user?.is_shadowbanned ? 'text-white bg-purple-600 hover:bg-purple-700' : 'text-white bg-gray-600 hover:bg-gray-700'"
      @confirm="confirmShadowban"
    />

    <ConfirmDialog
      v-model="dialogTempBan"
      title="Ban temporal"
      :subtitle="`@${user?.username} sera baneado temporalmente por ${tempBanLabel}.`"
      :icon="ClockIcon"
      icon-bg="bg-orange-100"
      icon-color="text-orange-600"
      confirm-text="Aplicar"
      confirm-class="text-white bg-orange-600 hover:bg-orange-700"
      :show-reason="true"
      reason-label="Motivo del baneo temporal"
      reason-placeholder="Ej: Incumplimiento temporal de normas..."
      @confirm="confirmTempBan"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import { api } from '../api';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import {
  ExclamationTriangleIcon,
  NoSymbolIcon,
  CheckCircleIcon,
  CheckBadgeIcon,
  XCircleIcon,
  EyeSlashIcon,
  ClockIcon,
} from '@heroicons/vue/24/outline';

const route = useRoute();
const loading = ref(true);
const user = ref(null);
const customBanHours = ref(24);
const moderationHistory = ref([]);

// Dialog states
const dialog = ref(null);
const dialogSuspends = computed({ get: () => dialog.value === 'suspend', set: (v) => { if (!v) dialog.value = null; } });
const dialogBan = computed({ get: () => dialog.value === 'ban', set: (v) => { if (!v) dialog.value = null; } });
const dialogActivate = computed({ get: () => dialog.value === 'activate', set: (v) => { if (!v) dialog.value = null; } });
const dialogVerify = computed({ get: () => dialog.value === 'verify' || dialog.value === 'unverify', set: (v) => { if (!v) dialog.value = null; } });
const dialogShadowban = computed({ get: () => dialog.value === 'shadowban' || dialog.value === 'unshadowban', set: (v) => { if (!v) dialog.value = null; } });
const dialogTempBan = computed({ get: () => dialog.value === 'tempban', set: (v) => { if (!v) dialog.value = null; } });

const tempBanLabel = computed(() => {
  if (pendingTempBan === '24h') return '24 horas';
  if (pendingTempBan === '7d') return '7 dias';
  if (pendingTempBan === '30d') return '30 dias';
  if (pendingTempBan === 'custom') return `${customBanHours.value} horas`;
  return '';
});

let pendingTempBan = null;

// Ban remaining time
const banRemaining = ref('');
let banTimer = null;

function updateBanRemaining() {
  if (!user.value?.banned_until) { banRemaining.value = ''; return; }
  const end = new Date(user.value.banned_until);
  const now = new Date();
  const diff = end - now;
  if (diff <= 0) { banRemaining.value = 'Expirado'; return; }
  const days = Math.floor(diff / 86400000);
  const hours = Math.floor((diff % 86400000) / 3600000);
  const minutes = Math.floor((diff % 3600000) / 60000);
  const parts = [];
  if (days > 0) parts.push(`${days}d`);
  if (hours > 0) parts.push(`${hours}h`);
  parts.push(`${minutes}m`);
  banRemaining.value = parts.join(' ');
}

// Last ban log entry
const lastBanLog = computed(() => {
  return moderationHistory.value.find(
    (l) => l.action === 'user.update' && l.new_values?.status === 'banned'
  ) || moderationHistory.value.find(
    (l) => l.action === 'user.tempban'
  );
});

function openTempBan(duration) {
  pendingTempBan = duration;
  dialog.value = 'tempban';
}

async function fetchUser() {
  try {
    const res = await api(`/admin/api/users/${route.params.id}`);
    const data = await res.json();
    user.value = { ...data.user, stats: data.stats, communities: data.communities, reports_against: data.reports_against, recent_posts: data.recent_posts };
    moderationHistory.value = data.moderation_history || [];
    updateBanRemaining();
  } catch (e) {
    console.error('Error:', e);
  } finally {
    loading.value = false;
  }
}

async function updateUser(payload) {
  try {
    const res = await api(`/admin/api/users/${route.params.id}`, {
      method: 'PUT',
      body: JSON.stringify(payload),
    });
    const data = await res.json();
    user.value = { ...user.value, ...data.user };
    updateBanRemaining();
    await fetchUser();
  } catch (e) {
    console.error('Error:', e);
  }
}

async function toggleShadowban() {
  try {
    const res = await api(`/admin/api/users/${route.params.id}/shadowban`, {
      method: 'POST',
    });
    const data = await res.json();
    user.value = { ...user.value, ...data.user };
    await fetchUser();
  } catch (e) {
    console.error('Error:', e);
  }
}

async function tempBan(duration, reason) {
  try {
    const body = { duration };
    if (duration === 'custom') body.custom_hours = customBanHours.value;
    if (reason) body.reason = reason;
    const res = await api(`/admin/api/users/${route.params.id}/temp-ban`, {
      method: 'POST',
      body: JSON.stringify(body),
    });
    const data = await res.json();
    user.value = { ...user.value, ...data.user };
    updateBanRemaining();
    await fetchUser();
  } catch (e) {
    console.error('Error:', e);
  }
}

function confirmSuspend(reason) { updateUser({ status: 'suspended', reason }); }
function confirmBan(reason) { updateUser({ status: 'banned', reason }); }
function confirmActivate() { updateUser({ status: 'active', banned_until: null }); }
function confirmVerify() { updateUser({ is_verified: !user.value.is_verified }); }
function confirmShadowban() { toggleShadowban(); }
function confirmTempBan(reason) { tempBan(pendingTempBan, reason); }

function actionLabel(action) {
  const map = {
    'user.update': 'Cambio de estado',
    'user.warn': 'Advertencia',
    'user.tempban': 'Ban temporal',
    'user.shadowban.toggle': 'Shadowban',
    'user.bulk.ban': 'Baneo masivo',
  };
  return map[action] || action;
}

function actionBg(action) {
  if (action?.includes('ban') || action === 'user.update') return 'bg-red-100';
  if (action?.includes('warn')) return 'bg-orange-100';
  if (action?.includes('shadowban')) return 'bg-purple-100';
  return 'bg-gray-100';
}

function actionIcon(action) {
  if (action?.includes('ban') || action === 'user.update') return NoSymbolIcon;
  if (action?.includes('warn')) return ExclamationTriangleIcon;
  if (action?.includes('shadowban')) return EyeSlashIcon;
  return ClockIcon;
}

function actionIconColor(action) {
  if (action?.includes('ban') || action === 'user.update') return 'text-red-600';
  if (action?.includes('warn')) return 'text-orange-600';
  if (action?.includes('shadowban')) return 'text-purple-600';
  return 'text-gray-600';
}

function statusBadge(status) {
  const map = { active: 'bg-green-100 text-green-700', pending: 'bg-yellow-100 text-yellow-700', suspended: 'bg-orange-100 text-orange-700', banned: 'bg-red-100 text-red-700' };
  return map[status] || 'bg-gray-100 text-gray-600';
}

function reportStatusBadge(status) {
  const map = { pending: 'bg-yellow-100 text-yellow-700', reviewed: 'bg-blue-100 text-blue-700', resolved: 'bg-green-100 text-green-700', dismissed: 'bg-gray-100 text-gray-600' };
  return map[status] || 'bg-gray-100 text-gray-600';
}

function formatDate(date) {
  if (!date) return '';
  return new Date(date).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function formatDateShort(date) {
  if (!date) return '';
  return new Date(date).toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric' });
}

onMounted(() => {
  fetchUser();
  banTimer = setInterval(updateBanRemaining, 60000);
});

onUnmounted(() => {
  if (banTimer) clearInterval(banTimer);
});
</script>
