import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import client, { readToken, readCompanyId, writeCompanyId, writeToken } from '../api/client';

/**
 * Authentication, active company and permission state for the whole SPA.
 */
export const useAuthStore = defineStore('auth', () => {
    const token = ref(readToken());
    const user = ref(null);
    const company = ref(null);
    const permissions = ref([]);
    const booted = ref(false);
    const loading = ref(false);

    const isAuthenticated = computed(() => Boolean(token.value));
    const companies = computed(() => user.value?.companies ?? []);
    const hasCompany = computed(() => Boolean(company.value));

    /**
     * Whether the active company grants the given permission key.
     *
     * @param {string} permission
     * @returns {boolean}
     */
    function can(permission) {
        if (user.value?.is_superadmin) {
            return true;
        }

        return permissions.value.includes(permission);
    }

    /**
     * Exchange credentials for an API token and bootstrap the session.
     *
     * @param {{email: string, password: string}} credentials
     */
    async function login(credentials) {
        loading.value = true;

        try {
            const { data } = await client.post('/auth/login', {
                ...credentials,
                device_name: browserName(),
            });

            token.value = data.data.token;
            writeToken(data.data.token);
            user.value = data.data.user;

            await pickInitialCompany();
            await fetchSession();

            return true;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Restore the session from the stored token, if there is one.
     */
    async function restore() {
        if (booted.value || ! isAuthenticated.value) {
            booted.value = true;
            return;
        }

        loading.value = true;

        try {
            await fetchSession();
        } finally {
            booted.value = true;
            loading.value = false;
        }
    }

    /**
     * Reload the user, the active company and its permissions.
     */
    async function fetchSession() {
        const { data } = await client.get('/auth/me');

        user.value = data.data.user;
        company.value = data.data.company;
        permissions.value = data.data.permissions ?? [];
    }

    /**
     * Make a company active and reload its permissions.
     *
     * @param {number} companyId
     */
    async function selectCompany(companyId) {
        writeCompanyId(companyId);
        await fetchSession();
        return company.value;
    }

    /**
     * Revoke the current token and clear every piece of session state.
     */
    async function logout() {
        try {
            await client.post('/auth/logout');
        } catch {
            // The token may already be invalid; clearing state is enough.
        } finally {
            $reset();
        }
    }

    /**
     * Pick the default company right after signing in.
     */
    async function pickInitialCompany() {
        const existing = readCompanyId();
        const available = user.value?.companies ?? [];

        if (existing && available.some((item) => item.id === existing)) {
            return;
        }

        await writeCompanyId(available[0]?.id ?? null);
    }

    function $reset() {
        token.value = null;
        user.value = null;
        company.value = null;
        permissions.value = [];
        booted.value = false;
        writeToken(null);
        writeCompanyId(null);
    }

    return {
        token,
        user,
        company,
        permissions,
        booted,
        loading,
        isAuthenticated,
        companies,
        hasCompany,
        can,
        login,
        restore,
        fetchSession,
        selectCompany,
        logout,
        $reset,
    };
});

/**
 * A readable name for the API token so sessions can be revoked per device.
 *
 * @returns {string}
 */
function browserName() {
    const agent = navigator.userAgent;

    if (agent.includes('Edg/')) return 'Edge';
    if (agent.includes('Chrome/')) return 'Chrome';
    if (agent.includes('Firefox/')) return 'Firefox';
    if (agent.includes('Safari/')) return 'Safari';

    return 'SPA';
}
