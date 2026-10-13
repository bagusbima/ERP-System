<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Bell, ChevronsUpDown, LogOut, Menu, Settings, X } from 'lucide-vue-next';
import Button from '../components/ui/Button.vue';
import { navigation } from './navigation';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const sidebarOpen = ref(false);

const groups = computed(() => navigation
    .map((group) => ({
        ...group,
        items: group.items.filter((item) => ! item.permission || auth.can(item.permission)),
    }))
    .filter((group) => group.items.length > 0));

const initials = computed(() => (auth.user?.name ?? '?')
    .split(' ')
    .slice(0, 2)
    .map((part) => part.charAt(0))
    .join('')
    .toUpperCase());

const companyName = computed(() => auth.company?.name ?? 'Tidak ada perusahaan aktif');

const companyCode = computed(() => auth.company
    ? `ID: ${(auth.company.slug ?? auth.company.name).toUpperCase()}`
    : 'ID: -');

const roleName = computed(() => {
    if (auth.company?.role?.name) {
        return auth.company.role.name;
    }

    return auth.user?.is_superadmin ? 'Superadmin' : 'Tidak ada peran';
});

const productName = computed(() => {
    const match = navigation
        .flatMap((group) => group.items)
        .find((item) => item.path === route.path);

    return match?.name ?? 'Enterprise Resource Planning';
});

watch(() => route.fullPath, () => {
    sidebarOpen.value = false;
});

async function switchCompany(companyId) {
    if (companyId === auth.company?.id) {
        return;
    }

    await auth.selectCompany(companyId);

    if (route.meta.requiresCompany) {
        await router.replace('/');
    } else {
        await router.replace(route.fullPath);
    }
}

async function signOut() {
    await auth.logout();
    await router.replace({ name: 'login' });
}
</script>

<template>
    <div class="flex min-h-screen bg-background">
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-30 bg-foreground/30 backdrop-blur-[2px] md:hidden"
            aria-hidden="true"
            @click="sidebarOpen = false"
        />

        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-60 flex-col border-r border-border/70 bg-sidebar text-sidebar-foreground shadow-[0_1px_8px_rgba(0,0,0,0.04)] transition-transform duration-200 md:static md:z-auto md:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex flex-col gap-3 p-4">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-primary text-sm font-bold text-primary-foreground">
                        E
                    </div>
                    <div class="flex min-w-0 flex-1 flex-col">
                        <span class="truncate text-sm font-semibold text-foreground">{{ companyName }}</span>
                        <span class="truncate text-xs text-sidebar-foreground">ERP Core System</span>
                    </div>
                    <button
                        type="button"
                        class="rounded-md p-1 text-sidebar-foreground transition-colors hover:text-foreground md:hidden"
                        aria-label="Tutup menu"
                        @click="sidebarOpen = false"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="relative flex items-center gap-2 rounded-lg bg-muted px-3 py-2">
                    <span class="h-2 w-2 shrink-0 rounded-full bg-primary" />
                    <span class="min-w-0 flex-1 truncate text-xs font-medium text-foreground">{{ companyCode }}</span>
                    <ChevronsUpDown class="h-4 w-4 shrink-0 text-sidebar-foreground" />

                    <select
                        v-if="auth.companies.length > 0"
                        class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                        aria-label="Ganti perusahaan aktif"
                        :value="auth.company?.id"
                        @change="switchCompany(Number($event.target.value))"
                    >
                        <option
                            v-for="company in auth.companies"
                            :key="company.id"
                            :value="company.id"
                        >
                            {{ company.name }}
                        </option>
                    </select>
                </div>
            </div>

            <nav class="min-h-0 flex-1 overflow-y-auto px-3 pb-4">
                <div v-for="group in groups" :key="group.label" class="mb-5">
                    <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-sidebar-foreground/70">
                        {{ group.label }}
                    </p>

                    <ul class="space-y-0.5">
                        <li v-for="item in group.items" :key="item.path">
                            <router-link
                                :to="item.path"
                                exact-active-class="bg-sidebar-accent text-sidebar-accent-foreground"
                                class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-sidebar-foreground transition-colors hover:bg-muted hover:text-foreground"
                            >
                                <component :is="item.icon" class="h-4 w-4 shrink-0" />
                                <span class="truncate">{{ item.name }}</span>
                            </router-link>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="flex flex-col gap-2 border-t border-border/70 p-3">
                <div class="flex items-center justify-between rounded-lg bg-muted px-3 py-2">
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-success" />
                        <span class="text-xs font-medium text-foreground">Server Online</span>
                    </div>
                    <span class="text-xs tabular-nums text-sidebar-foreground">v1.0</span>
                </div>

                <div class="flex items-center gap-3 rounded-lg px-2 py-2 transition-colors hover:bg-muted">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-[11px] font-semibold text-primary-foreground">
                        {{ initials }}
                    </div>
                    <div class="flex min-w-0 flex-1 flex-col">
                        <span class="truncate text-sm font-semibold text-foreground">{{ auth.user?.name }}</span>
                        <span class="truncate text-xs text-sidebar-foreground">{{ roleName }}</span>
                    </div>
                    <button
                        type="button"
                        class="rounded-md p-1 text-sidebar-foreground transition-colors hover:text-destructive"
                        title="Keluar"
                        aria-label="Keluar"
                        @click="signOut"
                    >
                        <LogOut class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header
                class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-border/70 bg-background/80 px-4 backdrop-blur-xl sm:px-6"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground md:hidden"
                        aria-label="Buka menu"
                        @click="sidebarOpen = true"
                    >
                        <Menu class="h-5 w-5" />
                    </button>

                    <div class="flex min-w-0 items-center gap-2 text-sm text-muted-foreground">
                        <span class="truncate font-semibold text-foreground">{{ companyName }}</span>
                        <span class="hidden text-border sm:inline">/</span>
                        <span class="truncate hidden sm:inline">{{ productName }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="hidden rounded-full bg-secondary px-3 py-1 text-xs font-semibold text-secondary-foreground lg:inline">
                        {{ roleName }}
                    </span>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                        aria-label="Notifikasi"
                        title="Notifikasi"
                    >
                        <Bell class="h-4 w-4" />
                    </button>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                        aria-label="Pengaturan"
                        title="Pengaturan"
                        @click="router.push('/companies')"
                    >
                        <Settings class="h-4 w-4" />
                    </button>

                    <div class="flex items-center gap-2 border-l border-border/70 pl-2 sm:pl-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-[11px] font-semibold text-primary-foreground">
                            {{ initials }}
                        </div>
                        <div class="hidden flex-col md:flex">
                            <span class="text-sm font-semibold leading-none text-foreground">{{ auth.user?.name }}</span>
                            <span class="mt-1 text-xs text-muted-foreground">{{ companyCode }}</span>
                        </div>
                    </div>

                    <Button
                        variant="ghost"
                        size="icon"
                        title="Keluar"
                        aria-label="Keluar"
                        @click="signOut"
                    >
                        <LogOut class="h-4 w-4" />
                    </Button>
                </div>
            </header>

            <main class="flex-1">
                <router-view />
            </main>
        </div>
    </div>
</template>
