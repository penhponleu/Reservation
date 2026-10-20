<script setup lang="ts">
definePageMeta({ middleware: "auth" });

const api = useApi();
const token = useCookie<string | null>("token");
const rows = ref<any[]>([]);
const stats = ref({ total: 0, emailed: 0, checked_in: 0 });
const busyId = ref<number | null>(null);
const notice = ref("");

const fmt = (d: string | null) => (d ? new Date(d).toLocaleString() : "—");

async function load() {
  try {
    const res = await api("/registrations");
    rows.value = res.data;
    stats.value = res.stats;
  } catch (e: any) {
    if (e.status === 401) {
      token.value = null;
      navigateTo("/admin/login");
    }
  }
}

// Resend email → sent_by = admin id
async function resend(id: number) {
  busyId.value = id;
  try {
    const res = await api(`/registrations/${id}/resend`, { method: "POST" });
    notice.value = res.message;
    await load();
  } finally {
    busyId.value = null;
  }
}

// Logout → DELETE personal_access_tokens
async function logout() {
  try {
    await api("/logout", { method: "POST" });
  } finally {
    token.value = null;
    navigateTo("/admin/login");
  }
}

onMounted(load);
</script>

<template>
  <div>
    <div class="card">
      <div class="row">
        <h1>Admin Dashboard</h1>
        <div>
          <NuxtLink to="/admin/scan"><button>Open Scanner</button></NuxtLink>
          <button class="light" @click="logout">Logout</button>
        </div>
      </div>
      <div class="stats">
        <div>
          <b>{{ stats.total }}</b
          >Registered
        </div>
        <div>
          <b>{{ stats.emailed }}</b
          >Email sent
        </div>
        <div>
          <b>{{ stats.checked_in }}</b
          >Checked in
        </div>
      </div>
    </div>

    <div class="card table-wrap">
      <p v-if="notice" class="success">{{ notice }}</p>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Email</th>
            <th>Username</th>
            <th>Phone</th>
            <th>Email sent</th>
            <th>Checked in</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rows" :key="r.id">
            <td>{{ r.id }}</td>
            <td>{{ r.email }}</td>
            <td>{{ r.username }}</td>
            <td>{{ r.phone }}</td>
            <td>{{ fmt(r.email_sent_at) }}</td>
            <td>{{ fmt(r.checked_in_at) }}</td>
            <td>
              <button
                class="light"
                :disabled="busyId === r.id"
                @click="resend(r.id)"
              >
                {{ busyId === r.id ? "Sending..." : "Resend email" }}
              </button>
            </td>
          </tr>
          <tr v-if="!rows.length">
            <td colspan="7">No registrations yet.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
