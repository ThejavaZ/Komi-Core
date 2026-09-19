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
                !isMobile && 'lg:!translate-x-0!',
                !isMobile && (sidebarExpanded ? 'lg:w-64' : 'lg:w-20'),
            ]"
        >
            <!-- Logo -->
            <div
                class="flex items-center gap-3 px-5 h-16 border-b border-gray-700"
            >
                <div
                    class="w-9 h-9 rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-lg shrink-0"
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
            <nav class="flex-1 py-4 space-y-1 px-3 overflow-y-auto scrollbar-hide">
                <template v-for="group in navGroups" :key="group.label">
                    <p
                        v-if="isMobile || sidebarExpanded"
                        class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3 pt-4 pb-1"
                    >
                        {{ group.label }}
                    </p>
                    <router-link
                        v-for="item in group.items"
                        :key="item.route"
                        :to="{ name: item.route }"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                        :class="
                            isActive(item.route)
                                ? 'bg-indigo-600 text-white'
                                : 'text-gray-300 hover:bg-gray-800 hover:text-white'
                        "
                        @click="isMobile && (sidebarOpen = false)"
                    >
                        <component
                            :is="item.icon"
                            class="w-5 h-5 flex-shrink-0"
                        />
                        <span v-if="isMobile || sidebarExpanded">{{
                            item.label
                        }}</span>
                    </router-link>
                </template>
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
            <header
                class="sticky top-0 z-20 bg-white border-b border-gray-200 h-16 flex items-center justify-between px-4 lg:px-6"
            >
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
                <div class="flex-1 max-w-md mx-4 hidden md:block">
                    <div class="relative">
                        <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                        <input
                            v-model="globalSearch"
                            type="text"
                            placeholder="Buscar usuarios, posts, comunidades... (Ctrl+K)"
                            class="w-full border border-gray-200 rounded-lg pl-9 pr-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            @input="debouncedSearch"
                            @keydown.escape="globalSearch = ''; searchResults = []"
                        />
                        <div v-if="searchResults.length" class="absolute top-full left-0 right-0 mt-1 bg-white rounded-lg shadow-lg border border-gray-200 max-h-80 overflow-y-auto z-50">
                            <div v-for="group in groupedResults" :key="group.type" class="border-b border-gray-50 last:border-0">
                                <p class="px-3 py-1.5 text-xs font-semibold text-gray-400 uppercase bg-gray-50">{{ groupLabel(group.type) }}</p>
                                <div v-for="item in group.items" :key="`${item.type}-${item.id}`" class="px-3 py-2 hover:bg-gray-50 cursor-pointer flex items-center justify-between" @click="goToResult(item)">
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">{{ item.title }}</p>
                                        <p class="text-xs text-gray-400">{{ item.subtitle }}</p>
                                    </div>
                                    <span v-if="item.status" class="text-xs px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500">{{ item.status }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-medium text-gray-700">
                            {{ user?.name }}
                        </p>
                        <p class="text-xs text-gray-400">Administrador</p>
                    </div>
                    <div class="relative">
                        <button @click="showNotifications = !showNotifications" class="relative text-gray-400 hover:text-gray-600 transition-colors">
                            <BellIcon class="w-5 h-5" />
                            <span v-if="unreadCount > 0" class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">{{ unreadCount }}</span>
                        </button>
                        <div v-if="showNotifications" class="absolute right-0 top-full mt-1 w-72 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                            <div class="px-3 py-2 border-b border-gray-100 font-semibold text-sm text-gray-800">Notificaciones</div>
                            <div class="p-3 text-sm text-gray-500">
                                <p v-if="unreadCount === 0">Sin notificaciones pendientes</p>
                                <template v-else>
                                    <p v-if="unreadData.pending_reports > 0" class="mb-1">📋 {{ unreadData.pending_reports }} reportes pendientes</p>
                                    <p v-if="unreadData.pending_appeals > 0">📥 {{ unreadData.pending_appeals }} apelaciones pendientes</p>
                                </template>
                            </div>
                        </div>
                    </div>
                    <button @click="toggleDarkMode" class="text-gray-400 hover:text-gray-600 transition-colors" :title="isDark ? 'Modo claro' : 'Modo oscuro'">
                        <SunIcon v-if="isDark" class="w-5 h-5" />
                        <MoonIcon v-else class="w-5 h-5" />
                    </button>
                    <select v-model="currentLang" @change="changeLang" class="border border-gray-200 rounded px-1.5 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300">
                        <option value="es">ES</option>
                        <option value="en">EN</option>
                    </select>
                    <div
                        class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center"
                    >
                        <span class="text-indigo-700 font-semibold text-sm">
                            {{ user?.name?.charAt(0)?.toUpperCase() || "A" }}
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
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { api } from "../api";
import {
    HomeIcon,
    UsersIcon,
    DocumentTextIcon,
    TagIcon,
    BuildingOffice2Icon,
    FlagIcon,
    ShieldCheckIcon,
    InboxIcon,
    ChartBarIcon,
    ClockIcon,
    ServerIcon,
    ChevronLeftIcon,
    ArrowRightOnRectangleIcon,
    Bars3Icon,
    ShieldExclamationIcon,
    Cog6ToothIcon,
    MagnifyingGlassIcon,
    BellIcon,
    SunIcon,
    MoonIcon,
    BriefcaseIcon,
    ComputerDesktopIcon,
    RectangleStackIcon,
} from "@heroicons/vue/24/outline";

const route = useRoute();
const router = useRouter();
const { locale } = useI18n();
const sidebarOpen = ref(false);
const sidebarExpanded = ref(true);
const isMobile = ref(false);
const user = ref(window.adminUser);
const currentLang = ref(localStorage.getItem('admin_lang') || 'es');
const csrfToken = ref(
    document.querySelector('meta[name="csrf-token"]')?.content || "",
);

const globalSearch = ref('');
const searchResults = ref([]);
const showNotifications = ref(false);
const unreadCount = ref(0);
const unreadData = ref({});
const isDark = ref(localStorage.getItem('admin_dark_mode') === 'true');
let searchDebounce = null;

const groupedResults = computed(() => {
    const groups = {};
    searchResults.value.forEach(item => {
        if (!groups[item.type]) groups[item.type] = { type: item.type, items: [] };
        groups[item.type].items.push(item);
    });
    return Object.values(groups);
});

function groupLabel(type) {
    return { user: 'Usuarios', post: 'Publicaciones', community: 'Comunidades', tag: 'Tags' }[type] || type;
}

async function debouncedSearch() {
    clearTimeout(searchDebounce);
    if (globalSearch.value.length < 2) { searchResults.value = []; return; }
    searchDebounce = setTimeout(async () => {
        try {
            const res = await api(`/admin/api/search?q=${encodeURIComponent(globalSearch.value)}`);
            const data = await res.json();
            searchResults.value = data.results || [];
        } catch (e) { console.error(e); }
    }, 300);
}

function goToResult(item) {
    searchResults.value = [];
    globalSearch.value = '';
    if (item.type === 'user') router.push({ name: 'admin.user-detail', params: { id: item.id } });
    else if (item.type === 'post') router.push({ name: 'admin.posts' });
    else if (item.type === 'community') router.push({ name: 'admin.communities' });
    else if (item.type === 'tag') router.push({ name: 'admin.tags' });
}

function toggleDarkMode() {
    isDark.value = !isDark.value;
    localStorage.setItem('admin_dark_mode', isDark.value);
    document.documentElement.classList.toggle('dark', isDark.value);
}

function changeLang() {
    locale.value = currentLang.value;
    localStorage.setItem('admin_lang', currentLang.value);
}

async function fetchNotifications() {
    try {
        const res = await api('/admin/api/notifications/unread');
        const data = await res.json();
        unreadCount.value = data.total || 0;
        unreadData.value = data;
    } catch (e) { /* ignore */ }
}

function handleClickOutside(e) {
    if (!e.target.closest('.relative')) {
        searchResults.value = [];
        showNotifications.value = false;
    }
}

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
    window.addEventListener("resize", checkMobile);

    if (isDark.value) document.documentElement.classList.add('dark');
    locale.value = currentLang.value;

    document.addEventListener('keydown', handleKeydown);
    document.addEventListener('click', handleClickOutside);

    fetchNotifications();
    setInterval(fetchNotifications, 30000);
});

onUnmounted(() => {
    window.removeEventListener("resize", checkMobile);
    document.removeEventListener('keydown', handleKeydown);
    document.removeEventListener('click', handleClickOutside);
});

function handleKeydown(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        document.querySelector('input[placeholder*="Buscar"]')?.focus();
    }
}

const navGroups = [
    {
        label: "Principal",
        items: [
            { label: "Dashboard", route: "admin.dashboard", icon: HomeIcon },
        ],
    },
    {
        label: "Gestión",
        items: [
            { label: "Usuarios", route: "admin.users", icon: UsersIcon },
            {
                label: "Publicaciones",
                route: "admin.posts",
                icon: DocumentTextIcon,
            },
            { label: "Tags", route: "admin.tags", icon: TagIcon },
            {
                label: "Comunidades",
                route: "admin.communities",
                icon: BuildingOffice2Icon,
            },
        ],
    },
    {
        label: "Moderación",
        items: [
            {
                label: "Cola de Moderación",
                route: "admin.moderation",
                icon: ShieldCheckIcon,
            },
            { label: "Reportes", route: "admin.reports", icon: FlagIcon },
            { label: "Apelaciones", route: "admin.appeals", icon: InboxIcon },
        ],
    },
    {
        label: "Análisis",
        items: [
            {
                label: "Analytics",
                route: "admin.analytics",
                icon: ChartBarIcon,
            },
            { label: "Historial", route: "admin.logs", icon: ClockIcon },
            { label: "Sistema", route: "admin.system", icon: ServerIcon },
        ],
    },
    {
        label: "Herramientas",
        items: [
            {
                label: "Configuración General",
                route: "admin.general-settings",
                icon: Cog6ToothIcon,
            },
            {
                label: "Auto-Mod",
                route: "admin.auto-mod",
                icon: ShieldExclamationIcon,
            },
            {
                label: "Configuración",
                route: "admin.settings",
                icon: Cog6ToothIcon,
            },
            {
                label: "Cola de Jobs",
                route: "admin.jobs",
                icon: BriefcaseIcon,
            },
            {
                label: "Sesiones",
                route: "admin.sessions",
                icon: ComputerDesktopIcon,
            },
        ],
    },
];

const titles = {
    "admin.dashboard": "Dashboard",
    "admin.users": "Gestión de Usuarios",
    "admin.user-detail": "Detalle de Usuario",
    "admin.posts": "Gestión de Publicaciones",
    "admin.tags": "Gestión de Tags",
    "admin.communities": "Comunidades",
    "admin.reports": "Reportes",
    "admin.moderation": "Cola de Moderación",
    "admin.appeals": "Apelaciones",
    "admin.analytics": "Analytics",
    "admin.logs": "Historial de Actividad",
    "admin.system": "Salud del Sistema",
    "admin.auto-mod": "Auto-Moderación",
    "admin.settings": "Configuración",
    "admin.general-settings": "Configuración General",
    "admin.jobs": "Cola de Jobs",
    "admin.sessions": "Sesiones Activas",
};

const currentTitle = computed(() => titles[route.name] || "Admin");

function isActive(routeName) {
    return route.name === routeName;
}
</script>

<style scoped>
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
