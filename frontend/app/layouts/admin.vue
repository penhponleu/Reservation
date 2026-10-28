<script setup lang="ts">
// Admin area: sidebar menu + page content
const api = useApi();
const token = useCookie<string | null>("token");
const me = useState<any>("me", () => null); // logged-in user, shared with the Settings page
const open = ref(false); // sidebar on phones
const route = useRoute();

const menu = [
  { to: "/admin", label: "Dashboard", icon: "M3 13h8V3H3zm0 8h8v-6H3zm10 0h8V11h-8zm0-18v6h8V3z" },
  { to: "/admin/tickets", label: "Tickets", icon: "M4 6h16v4a2 2 0 0 0 0 4v4H4v-4a2 2 0 0 0 0-4zm9 1v2m0 3v2m0 3v2" },
  { to: "/admin/scan", label: "Scanner", icon: "M4 8V4h4M16 4h4v4M20 16v4h-4M8 20H4v-4M4 12h16" },
  { to: "/admin/users", label: "Users", icon: "M16 19v-1a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v1M9 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6m13 9v-1a4 4 0 0 0-3-3.9M16 4.1a3 3 0 0 1 0 5.8" },
  { to: "/admin/settings", label: "Settings", icon: "M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6m7.4-3a7.4 7.4 0 0 0-.1-1.2l2-1.6-2-3.4-2.4 1a7 7 0 0 0-2-1.2L14.5 3h-5l-.4 2.6a7 7 0 0 0-2 1.2l-2.4-1-2 3.4 2 1.6a7.4 7.4 0 0 0 0 2.4l-2 1.6 2 3.4 2.4-1a7 7 0 0 0 2 1.2l.4 2.6h5l.4-2.6a7 7 0 0 0 2-1.2l2.4 1 2-3.4-2-1.6c.1-.4.1-.8.1-1.2" },
];

// close the phone menu after picking a page
watch(() => route.path, () => (open.value = false));

async function loadMe() {
  try {
    me.value = await api("/me");
  } catch (e: any) {
    if (e.status === 401) {
      token.value = null;
      // scan page works without login → just drop the sidebar there
      if (route.path === "/admin/scan") setPageLayout("default");
      else navigateTo({ path: "/admin/login", query: { redirect: route.fullPath } });
    }
  }
}

async function logout() {
  try {
    await api("/logout", { method: "POST" });
  } finally {
    token.value = null;
    me.value = null;
    navigateTo("/admin/login");
  }
}

onMounted(() => {
  if (!me.value) loadMe();
});
</script>

<template>
  <div class="admin">
    <!-- Phone top bar -->
    <header class="topbar">
      <button class="menu-btn" aria-label="Open menu" @click="open = true">☰</button>
      <span class="brand-text">Event Admin</span>
    </header>

    <div v-if="open" class="shade" @click="open = false" />

    <aside :class="['sidebar', { open }]">
      <div class="brand">
        <span class="logo">E</span>
        <div>
          <div class="brand-text">Event Admin</div>
          <div class="brand-sub">Reservation system</div>
        </div>
      </div>

      <nav>
        <NuxtLink
          v-for="m in menu"
          :key="m.to"
          :to="m.to"
          :class="['nav-item', { active: route.path === m.to }]"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path :d="m.icon" />
          </svg>
          {{ m.label }}
        </NuxtLink>
      </nav>

      <div class="me">
        <span class="me-avatar">{{ (me?.fullname || "?").trim()[0]?.toUpperCase() }}</span>
        <div class="me-info">
          <div class="me-name">{{ me?.fullname || "…" }}</div>
          <div class="me-email">{{ me?.email }}</div>
        </div>
      </div>
      <button class="logout" @click="logout">Logout</button>
    </aside>

    <main class="main">
      <slot />
    </main>
  </div>
</template>

<style scoped>
.admin { min-height: 100vh; }

/* ---------- Sidebar ---------- */
.sidebar {
  position: fixed;
  inset: 0 auto 0 0;
  z-index: 40;
  display: flex;
  flex-direction: column;
  width: 248px;
  padding: 20px 14px;
  background: #0f172a;
  color: #cbd5e1;
}
.brand { display: flex; align-items: center; gap: 10px; padding: 4px 8px 24px; }
.logo { display: grid; place-items: center; width: 36px; height: 36px; border-radius: 10px; background: var(--primary); color: #fff; font-weight: 800; }
.brand-text { font-weight: 700; color: #fff; }
.brand-sub { font-size: 12px; color: #94a3b8; }

nav { display: flex; flex-direction: column; gap: 4px; }
.nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 8px; color: #cbd5e1; text-decoration: none; font-size: 14px; font-weight: 600; }
.nav-item svg { width: 20px; height: 20px; flex: none; }
.nav-item:hover { background: #1e293b; color: #fff; }
.nav-item.active { background: var(--primary); color: #fff; }

.me { display: flex; align-items: center; gap: 10px; margin-top: auto; padding: 12px 8px; border-top: 1px solid #1e293b; }
.me-avatar { display: grid; place-items: center; flex: none; width: 34px; height: 34px; border-radius: 50%; background: #1e293b; color: #fff; font-weight: 700; }
.me-info { min-width: 0; }
.me-name { color: #fff; font-size: 14px; font-weight: 600; }
.me-email { font-size: 12px; color: #94a3b8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.logout { margin: 4px 0 0; background: #1e293b; color: #e2e8f0; }
.logout:hover:not(:disabled) { background: #334155; }

/* ---------- Content ---------- */
.main { margin-left: 248px; padding: 28px 32px; max-width: 1240px; }

/* ---------- Phone ---------- */
.topbar { display: none; }
.shade { position: fixed; inset: 0; z-index: 30; background: rgba(15, 23, 42, 0.5); }

@media (max-width: 860px) {
  .topbar { display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: #0f172a; }
  .menu-btn { margin: 0; padding: 4px 10px; font-size: 20px; background: transparent; }
  .sidebar { transform: translateX(-100%); transition: transform 0.2s; }
  .sidebar.open { transform: none; }
  .main { margin-left: 0; padding: 20px 16px; }
}
</style>
