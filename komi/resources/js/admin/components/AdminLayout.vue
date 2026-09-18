<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Mobile backdrop -->
    <transition name="fade">
      <div
        v-if="sidebarOpen && isMobile"
        class="fixed inset-0 bg-black/50 z-30 lg:hidden"
        @click="sidebarOpen = false"
      />
    </transition>

    <!-- Sidebar -->
    <aside
      :class="[
        'fixed top-0 left-0 h-full bg-gray-900 text-white flex flex-col transition-transform duration-300 z-40',
        sidebarOpen ? 'w-64 translate-x-0' : 'w-64 -translate-x-full',
        !isMobile && 'lg:!translate-x-0',
        !isMobile && (sidebarExpanded ? 'lg:w-64' : 'lg:w-20'),
      ]"
    >
      <!-- Logo -->
      <div class="flex items-center gap-3 px-5 h-16 border-b border-gray-700">
        <div
          class="w-9 h-9 rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-lg flex-shrink-0"
        >
          K
        </div>
        <span
          v-if="isMobile || sidebarExpanded"
          class="font-semibold text-lg tracking-tight whitespace-nowrap"
        >
          Komi Admin
        </span>
      </div>

      <!-- Nav links -->
      <nav class="flex-1 py-4 space-y-1 px-3 overflow-y-auto">
        <router-link
          v-for="item in navItems"
          :key="item.route"
          :to="{ name: item.route }"
          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
          :class="isActive(item.route) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'"
          @click="isMobile && (sidebarOpen = false)"
        >
          <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
          <span v-if="isMobile || sidebarExpanded">{{ item.label }}</span>
        </router-link>
      </nav>

      <!-- Collapse toggle (solo desktop) -->
      <button
        v-if="!isMobile"
        class="flex items-center justify-center h-12 border-t border-gray-700 text-gray-400 hover:text-white transition-colors"
        @click="sidebarExpanded = !sidebarExpanded"
      >
        <ChevronLeftIcon
          class="w-5 h-5 transition-transform duration-300"
          :class="sidebarExpanded ? '' : 'rotate-180'"
        />
      </button>
    </aside>

    <!-- Main wrapper -->
    <div
      :class="[
        'transition-all duration-300 min-h-screen flex flex-col',
        !isMobile && (sidebarExpanded ? 'lg:ml-64' : 'lg:ml-20'),
      ]"
    >
      <!-- Header -->
      <header class="sticky top-0 z-20 bg-white border-b border-gray-200 h-16 flex items-center justify-between px-4 lg:px-6">
        <div class="flex items-center gap-3">
          <!-- Mobile hamburger -->
          <button
            class="lg:hidden text-gray-600 hover:text-gray-900"
            @click="sidebarOpen = !sidebarOpen"
          >
            <Bars3Icon class="w-6 h-6" />
          </button>
          <h1 class="text-lg font-semibold text-gray-800">
            {{ currentTitle }}
          </h1>
        </div>
        <div class="flex items-center gap-4">
          <div class="text-right hidden sm:block">
            <p class="text-sm font-medium text-gray-700">{{ user?.name }}</p>
            <p class="text-xs text-gray-400">Administrador</p>
          </div>
          <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center">
            <span class="text-indigo-700 font-semibold text-sm">
              {{ user?.name?.charAt(0)?.toUpperCase() || 'A' }}
            </span>
          </div>
          <form method="POST" action="/admin/logout">
            <input type="hidden" name="_token" :value="csrfToken" />
            <button
              type="submit"
              class="text-gray-400 hover:text-red-500 transition-colors"
              title="Cerrar sesión"
            >
              <ArrowRightOnRectangleIcon class="w-5 h-5" />
            </button>
          </form>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 p-4 lg:p-6">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import {
  HomeIcon,
  UsersIcon,
  DocumentTextIcon,
  TagIcon,
  BuildingOffice2Icon,
  ChevronLeftIcon,
  ArrowRightOnRectangleIcon,
  Bars3Icon,
} from '@heroicons/vue/24/outline';

const route = useRoute();
const sidebarOpen = ref(false);
const sidebarExpanded = ref(true);
const isMobile = ref(false);
const user = ref(window.adminUser);
const csrfToken = ref(document.querySelector('meta[name="csrf-token"]')?.content || '');

function checkMobile() {
  isMobile.value = window.innerWidth < 1024;
  if (!isMobile.value) {
    sidebarOpen.value = true;
  } else {
    sidebarOpen.value = false;
  }
}

onMounted(() => {
  checkMobile();
  window.addEventListener('resize', checkMobile);
});

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile);
});

const navItems = [
  { label: 'Dashboard', route: 'admin.dashboard', icon: HomeIcon },
  { label: 'Usuarios', route: 'admin.users', icon: UsersIcon },
  { label: 'Publicaciones', route: 'admin.posts', icon: DocumentTextIcon },
  { label: 'Tags', route: 'admin.tags', icon: TagIcon },
  { label: 'Comunidades', route: 'admin.communities', icon: BuildingOffice2Icon },
];

const titles = {
  'admin.dashboard': 'Dashboard',
  'admin.users': 'Gestión de Usuarios',
  'admin.posts': 'Gestión de Publicaciones',
  'admin.tags': 'Gestión de Tags',
  'admin.communities': 'Comunidades',
};

const currentTitle = computed(() => titles[route.name] || 'Admin');

function isActive(routeName) {
  return route.name === routeName;
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
