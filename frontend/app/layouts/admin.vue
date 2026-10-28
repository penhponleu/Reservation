<script setup lang="ts">
// Admin pages: content on the left, simple menu on the right
const api = useApi();
const token = useCookie<string | null>("token");
const route = useRoute();
const me = ref<any>(null);

const menu = [
  { to: "/admin", label: "Dashboard" },
  { to: "/admin/scan", label: "Scanner" },
];

async function logout() {
  try {
    await api("/logout", { method: "POST" });
  } finally {
    token.value = null;
    navigateTo("/admin/login");
  }
}

onMounted(async () => {
  try {
    me.value = await api("/me");
  } catch {
    // not logged in (or token expired): the page itself handles it
  }
});
</script>

<template>
  <div class="admin">
    <main class="admin-main">
      <slot />
    </main>

    <aside class="sidebar">
      <div class="side-title">Admin</div>
      <nav>
        <NuxtLink v-for="m in menu" :key="m.to" :to="m.to" :class="['side-link', { active: route.path === m.to }]">
          {{ m.label }}
        </NuxtLink>
      </nav>
      <div class="side-me">
        <div class="side-name">{{ me?.fullname }}</div>
        <div class="side-email">{{ me?.email }}</div>
        <button class="light side-logout" @click="logout">Logout</button>
      </div>
    </aside>
  </div>
</template>

<style scoped>
.admin { display: flex; gap: 20px; align-items: flex-start; max-width: 1180px; margin: 0 auto; padding: 24px 16px; }
.admin-main { flex: 1; min-width: 0; }

/* simple right sidebar, stays in view while scrolling */
.sidebar {
  position: sticky;
  top: 24px;
  width: 200px;
  flex: none;
  padding: 16px;
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
}
.side-title { margin-bottom: 10px; font-size: 12px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--muted); }
nav { display: flex; flex-direction: column; gap: 4px; }
.side-link { padding: 9px 12px; border-radius: 8px; color: var(--text); text-decoration: none; font-size: 14px; font-weight: 600; }
.side-link:hover { background: var(--bg); }
.side-link.active { background: var(--primary); color: #fff; }
.side-me { margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--border); }
.side-name { font-size: 14px; font-weight: 600; }
.side-email { font-size: 12px; color: var(--muted); overflow-wrap: anywhere; }
.side-logout { width: 100%; margin-top: 10px; }

/* phones: menu goes under the page */
@media (max-width: 760px) {
  .admin { flex-direction: column; align-items: stretch; }
  .sidebar { position: static; width: 100%; }
}
</style>
