<script setup lang="ts">
// Admin pages: dark sidebar on the left (Open Admin style) + page content
const api = useApi();
const token = useCookie<string | null>("token");
// router's current address (useRoute() in a layout can still show the previous page)
const router = useRouter();
const path = computed(() => router.currentRoute.value.path);
const search = ref(""); // search box filters the menu
const open = ref(false); // sidebar on phones

const menu = [
  { to: "/admin", label: "Dashboard", icon: "M3 13h8V3H3zm0 8h8v-6H3zm10 0h8V11h-8zm0-18v6h8V3z" },
  { to: "/register", label: "Register form", icon: "M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9 2 2 4-4", external: true },
  { to: "/admin/scan", label: "Scanner", icon: "M4 8V4h4M16 4h4v4M20 16v4h-4M8 20H4v-4M4 12h16" },
];

const shown = computed(() => {
  const q = search.value.trim().toLowerCase();
  return q ? menu.filter((m) => m.label.toLowerCase().includes(q)) : menu;
});

// close the phone menu after picking a page
watch(path, () => (open.value = false));

async function logout() {
  try {
    await api("/logout", { method: "POST" });
  } finally {
    token.value = null;
    navigateTo("/admin/login");
  }
}
</script>

<template>
  <div class="admin">
    <!-- phone top bar -->
    <header class="topbar">
      <button class="menu-btn" aria-label="Open menu" @click="open = true">☰</button>
      <span class="brand"><b>Event</b> Admin</span>
    </header>
    <div v-if="open" class="shade" @click="open = false" />

    <aside :class="['sidebar', { open }]">
      <div class="brand side-brand"><b>Event</b> Admin</div>

      <div class="side-search">
        <input v-model="search" type="search" placeholder="Search menu" aria-label="Search menu" />
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
          <circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" />
        </svg>
      </div>

      <nav>
        <!-- external = opens in a new tab (the guest register form) -->
        <NuxtLink
          v-for="m in shown"
          :key="m.to"
          :to="m.to"
          :target="m.external ? '_blank' : undefined"
          :class="['side-link', { active: path === m.to }]"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path :d="m.icon" />
          </svg>
          {{ m.label }}
        </NuxtLink>
        <p v-if="!shown.length" class="side-empty">No menu found</p>
      </nav>

      <button class="side-link side-logout" @click="logout">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9" />
        </svg>
        Logout
      </button>
    </aside>

    <main class="admin-main">
      <slot />
    </main>
  </div>
</template>

<style scoped>
/* ---------- Dark sidebar ---------- */
.sidebar {
  position: fixed;
  inset: 0 auto 0 0;
  z-index: 40;
  display: flex;
  flex-direction: column;
  width: 220px;
  background: #2c3b41;
  color: #b8c7ce;
}
.brand { color: #fff; font-size: 18px; }
.brand b { font-weight: 800; }
.side-brand { padding: 16px; text-align: center; background: #273439; }

.side-search { position: relative; margin: 12px; }
.side-search input { padding: 8px 32px 8px 12px; font-size: 13px; color: #fff; background: #374850; border: 0; border-radius: 4px; }
.side-search input::placeholder { color: #8aa4af; }
.side-search input:focus { box-shadow: none; outline: 1px solid #4b646f; }
.side-search svg { position: absolute; right: 10px; top: 50%; width: 15px; height: 15px; transform: translateY(-50%); color: #8aa4af; }

nav { display: flex; flex-direction: column; }
.side-link {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 0;
  padding: 11px 16px;
  font-size: 14px;
  font-weight: 400;
  color: #b8c7ce;
  text-align: left;
  text-decoration: none;
  background: transparent;
  border-left: 3px solid transparent;
  border-radius: 0;
}
.side-link svg { width: 17px; height: 17px; flex: none; }
.side-link:hover:not(:disabled) { color: #fff; background: #1e282c; }
.side-link.active { color: #fff; background: #1e282c; border-left-color: var(--primary); }
.side-empty { padding: 8px 16px; font-size: 13px; color: #8aa4af; }
.side-logout { width: 100%; margin-top: auto; border-top: 1px solid #374850; }

/* ---------- Content ---------- */
.admin-main { margin-left: 220px; padding: 24px; }
.admin-main > :deep(*) { max-width: 1120px; }

/* ---------- Phones ---------- */
.topbar { display: none; }
.shade { position: fixed; inset: 0; z-index: 30; background: rgba(0, 0, 0, 0.45); }
@media (max-width: 860px) {
  .topbar { display: flex; align-items: center; gap: 12px; padding: 10px 16px; background: #2c3b41; }
  .menu-btn { margin: 0; padding: 4px 10px; font-size: 20px; background: transparent; }
  .sidebar { transform: translateX(-100%); transition: transform 0.2s; }
  .sidebar.open { transform: none; }
  .admin-main { margin-left: 0; padding: 16px; }
}
</style>
