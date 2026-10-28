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

// Edit a guest → PUT /registrations/{id} (new email → backend sends the QR there)
const editId = ref<number | null>(null);
const edit = reactive({ fullname: "", email: "", phone: "" });
const editErrors = ref<Record<string, string[]>>({});

function startEdit(r: any) {
  editId.value = r.id;
  editErrors.value = {};
  Object.assign(edit, { fullname: r.fullname, email: r.email, phone: r.phone });
}

async function save(id: number) {
  busyId.value = id;
  editErrors.value = {};
  try {
    const res = await api(`/registrations/${id}`, { method: "PUT", body: edit });
    notice.value = res.message;
    editId.value = null;
    await load();
  } catch (e: any) {
    editErrors.value = e.data?.errors ?? { general: [e.data?.message ?? "Save failed"] };
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
        <div><b>{{ stats.total }}</b>Registered</div>
        <div><b>{{ stats.emailed }}</b>Email sent</div>
        <div><b>{{ stats.checked_in }}</b>Checked in</div>
      </div>
    </div>

    <div class="card table-wrap">
      <p v-if="notice" class="success">{{ notice }}</p>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Email</th>
            <th>Full name</th>
            <th>Phone</th>
            <th>Email sent</th>
            <th>Checked in</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(r, i) in rows" :key="r.id">
            <!-- row number: first registration = 1 (list is newest first); DB id stays unchanged -->
            <td>{{ rows.length - i }}</td>
            <template v-if="editId === r.id">
              <td>
                <input v-model="edit.email" type="email" />
                <p v-if="editErrors.email" class="error">{{ editErrors.email[0] }}</p>
              </td>
              <td>
                <input v-model="edit.fullname" type="text" />
                <p v-if="editErrors.fullname" class="error">{{ editErrors.fullname[0] }}</p>
              </td>
              <td>
                <input v-model="edit.phone" type="tel" />
                <p v-if="editErrors.phone" class="error">{{ editErrors.phone[0] }}</p>
              </td>
            </template>
            <template v-else>
              <td>{{ r.email }}</td>
              <td>{{ r.fullname }}</td>
              <td>{{ r.phone }}</td>
            </template>
            <td>{{ fmt(r.email_sent_at) }}</td>
            <td>{{ fmt(r.checked_in_at) }}</td>
            <td class="actions">
              <template v-if="editId === r.id">
                <button :disabled="busyId === r.id" @click="save(r.id)">
                  {{ busyId === r.id ? "Saving..." : "Save" }}
                </button>
                <button class="light" @click="editId = null">Cancel</button>
                <p v-if="editErrors.general" class="error">{{ editErrors.general[0] }}</p>
              </template>
              <template v-else>
                <button class="light" @click="startEdit(r)">Edit</button>
                <button class="light" :disabled="busyId === r.id" @click="resend(r.id)">
                  {{ busyId === r.id ? "Sending..." : "Resend email" }}
                </button>
              </template>
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

<style scoped>
/* Edit / Save buttons side by side */
.actions { white-space: nowrap; }
.actions button + button { margin-left: 6px; }
td input { min-width: 140px; margin: 0; }</style>
