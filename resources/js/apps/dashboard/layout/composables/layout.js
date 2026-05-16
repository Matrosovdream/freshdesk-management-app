import { computed, reactive } from 'vue';

const THEME_CACHE_KEY = 'dashboard.theme.dark';

export function readCachedDarkMode() {
    try {
        const raw = window.localStorage?.getItem(THEME_CACHE_KEY);
        if (raw === null || raw === undefined) return null;
        return raw === '1' || raw === 'true';
    } catch {
        return null;
    }
}

function writeCachedDarkMode(enabled) {
    try {
        window.localStorage?.setItem(THEME_CACHE_KEY, enabled ? '1' : '0');
    } catch {
        // ignore (private mode, quota, etc.)
    }
}

const layoutConfig = reactive({
    preset: 'Aura',
    primary: 'emerald',
    surface: null,
    darkTheme: false,
    menuMode: 'static'
});

const layoutState = reactive({
    staticMenuInactive: false,
    overlayMenuActive: false,
    profileSidebarVisible: false,
    configSidebarVisible: false,
    sidebarExpanded: false,
    menuHoverActive: false,
    activeMenuItem: null,
    activePath: null
});

export function useLayout() {
    const toggleDarkMode = () => {
        if (!document.startViewTransition) {
            executeDarkModeToggle();

            return;
        }

        document.startViewTransition(() => executeDarkModeToggle(event));
    };

    const executeDarkModeToggle = () => {
        setDarkMode(!layoutConfig.darkTheme);
    };

    const setDarkMode = (enabled) => {
        const value = !!enabled;
        layoutConfig.darkTheme = value;
        document.documentElement.classList.toggle('app-dark', value);
        writeCachedDarkMode(value);
    };

    const toggleMenu = () => {
        if (isDesktop()) {
            if (layoutConfig.menuMode === 'static') {
                layoutState.staticMenuInactive = !layoutState.staticMenuInactive;
            }

            if (layoutConfig.menuMode === 'overlay') {
                layoutState.overlayMenuActive = !layoutState.overlayMenuActive;
            }
        } else {
            layoutState.mobileMenuActive = !layoutState.mobileMenuActive;
        }
    };

    const toggleConfigSidebar = () => {
        layoutState.configSidebarVisible = !layoutState.configSidebarVisible;
    };

    const hideMobileMenu = () => {
        layoutState.mobileMenuActive = false;
    };

    const changeMenuMode = (event) => {
        layoutConfig.menuMode = event.value;
        layoutState.staticMenuInactive = false;
        layoutState.mobileMenuActive = false;
        layoutState.sidebarExpanded = false;
        layoutState.menuHoverActive = false;
        layoutState.anchored = false;
    };

    const isDarkTheme = computed(() => layoutConfig.darkTheme);
    const isDesktop = () => window.innerWidth > 991;

    const hasOpenOverlay = computed(() => layoutState.overlayMenuActive);

    return {
        layoutConfig,
        layoutState,
        isDarkTheme,
        toggleDarkMode,
        setDarkMode,
        toggleConfigSidebar,
        toggleMenu,
        hideMobileMenu,
        changeMenuMode,
        isDesktop,
        hasOpenOverlay
    };
}
