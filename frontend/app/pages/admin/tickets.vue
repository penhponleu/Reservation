<script setup lang="ts">
definePageMeta({ middleware: "auth", layout: "admin" });

const api = useApi();
const rows = ref<any[]>([]);
const stats = ref({ total: 0, emailed: 0, checked_in: 0 });
const busyId = ref<number | null>(null);
const notice = ref({ text: "", bad: false });

const fmt = (d: string | null) =>
  d ? new Date(d).toLocaleString("en-GB", { day: "2-digit", month: "short", hour: "2-digit", minute: "2-digit" }) : "";
const initials = (name: string) =>
  (name || "?").trim().split(/\s+/).slice(0, 2).map((w) => w[0]?.toUpperCase()).join("");

async function load() {
  const res = await api("/registrations");
  rows.value = res.data;
  stats.value = res.stats;
}

// Message at the top, hides itself after a few seconds
let noticeTimer: ReturnType<typeof setTimeout> | undefined;
function showNotice(text: string, bad = false) {
  notice.value = { text, bad };
  clearTimeout(noticeTimer);
  noticeTimer = setTimeout(() => (notice.value.text = ""), 4000);
}

// Search + filter tabs (done in the browser, list is small)
const search = ref("");
const filter = ref<"all" | "checked" | "waiting" | "no_email">("all");
const filters = computed(() => [
  { key: "all", label: "All", count: stats.value.total },
  { key: "checked", label: "Checked in", count: stats.value.checked_in },
  { key: "waiting", label: "Not arrived", count: stats.value.total - stats.value.checked_in },
  { key: "no_email", label: "Email not sent", count: stats.value.total - stats.value.emailed },
] as const);

const shown = computed(() => {
  const q = search.value.trim().toLowerCase();
  return rows.value
    .map((r, i) => ({ ...r, no: rows.value.length - i })) // row number: first registration = 1
    .filter((r) => {
      if (filter.value === "checked" && !r.checked_in_at) return false;
      if (filter.value === "waiting" && r.checked_in_at) return false;
      if (filter.value === "no_email" && r.email_sent_at) return false;
      return !q || [r.fullname, r.email, r.phone].some((v) => String(v ?? "").toLowerCase().includes(q));
    });
});

// Edit → PUT /registrations/{id} (new email → backend sends the QR there)
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
    showNotice(res.message);
    editId.value = null;
    await load();
  } catch (e: any) {
    editErrors.value = e.data?.errors ?? { general: [e.data?.message ?? "Save failed"] };
  } finally {
    busyId.value = null;
  }
}

// Resend email → sent_by = admin id
async function resend(id: number) {
  busyId.value = id;
  try {
    const res = await api(`/registrations/${id}/resend`, { method: "POST" });
    showNotice(res.message, !res.email_sent);
    await load();
  } finally {
    busyId.value = null;
  }
}

// Delete → asks first, cannot be undone
async function remove(r: any) {
  if (!confirm(`Delete ${r.fullname} (${r.email})? This cannot be undone.`)) return;
  busyId.value = r.id;
  try {
    const res = await api(`/registrations/${r.id}`, { method: "DELETE" });
    showNotice(res.message);
    await load();
  } catch (e: any) {
    showNotice(e.data?.message ?? "Delete failed", true);
  } finally {
    busyId.value = null;
  }
}

onMounted(load);
onBeforeUnmount(() => clearTimeout(noticeTimer));
</script>

<template>
  <div>
    <div class="page-head">
      <div>
        <h1>Tickets</h1>
        <p>Everyone who registered. Edit details, resend the QR email or delete a ticket.</p>
      </div>
      <div class="actions-row">
        <!-- exports what the search / filter shows -->
        <button class="light" :disabled="!shown.length" @click="exportGuests(shown, 'tickets')">Export Excel</button>
      </div>
    </div>

    <section class="card">
      <div class="toolbar">
        <div class="tabs">
          <button v-for="f in filters" :key="f.key" :class="['tab', { active: filter === f.key }]" @click="filter = f.key">
            {{ f.label }} <span class="tab-count">{{ f.count }}</span>
          </button>
        </div>
        <input v-model="search" class="search" type="search" placeholder="Search name, email or phone" />
      </div>

      <p v-if="notice.text" :class="['notice', { bad: notice.bad }]">{{ notice.text }}</p>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Guest</th>
              <th>Phone</th>
              <th>Email</th>
              <th>Check-in</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in shown" :key="r.id">
              <td class="muted">{{ r.no }}</td>

              <template v-if="editId === r.id">
                <td>
                  <input v-model="edit.fullname" type="text" placeholder="Full name" />
                  <p v-if="editErrors.fullname" class="error">{{ editErrors.fullname[0] }}</p>
                  <input v-model="edit.email" type="email" placeholder="Email" class="mt" />
                  <p v-if="editErrors.email" class="error">{{ editErrors.email[0] }}</p>
                </td>
                <td>
                  <input v-model="edit.phone" type="tel" placeholder="Phone" />
                  <p v-if="editErrors.phone" class="error">{{ editErrors.phone[0] }}</p>
                </td>
              </template>
              <template v-else>
                <td>
                  <div class="person">
                    <span class="avatar">{{ initials(r.fullname) }}</span>
                    <div>
                      <div class="person-name">{{ r.fullname }}</div>
                      <div class="muted small">{{ r.email }}</div>
                    </div>
                  </div>
                </td>
                <td>{{ r.phone }}</td>
              </template>

              <td>
                <span v-if="r.email_sent_at" class="badge teal">Sent · {{ fmt(r.email_sent_at) }}</span>
                <span v-else class="badge red">Not sent</span>
              </td>
              <td>
                <span v-if="r.checked_in_at" class="badge green">Checked in · {{ fmt(r.checked_in_at) }}</span>
                <span v-else class="badge gray">Not arrived</span>
              </td>

              <td class="actions">
                <template v-if="editId === r.id">
                  <button class="sm" :disabled="busyId === r.id" @click="save(r.id)">
                    {{ busyId === r.id ? "Saving..." : "Save" }}
                  </button>
                  <button class="sm light" @click="editId = null">Cancel</button>
                  <p v-if="editErrors.general" class="error">{{ editErrors.general[0] }}</p>
                </template>
                <template v-else>
                  <button class="sm light" @click="startEdit(r)">Edit</button>
                  <button class="sm light" :disabled="busyId === r.id" @click="resend(r.id)">Resend email</button>
                  <button class="sm danger" :disabled="busyId === r.id" @click="remove(r)">Delete</button>
                </template>
              </td>
            </tr>
            <tr v-if="!shown.length">
              <td colspan="6" class="empty">
                {{ rows.length ? "No tickets match this search or filter." : "No registrations yet." }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<style scoped>
.toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 12px; }
.tabs { display: flex; gap: 4px; flex-wrap: wrap; padding: 4px; background: var(--bg); border-radius: 10px; }
.tab { margin: 0; padding: 7px 12px; font-size: 13px; background: transparent; color: var(--muted); border-radius: 8px; }
.tab:hover:not(:disabled) { background: var(--border); color: var(--text); }
.tab.active, .tab.active:hover { background: var(--card); color: var(--text); box-shadow: 0 1px 3px rgba(15, 23, 42, 0.12); }
.tab-count { margin-left: 4px; padding: 1px 7px; font-size: 12px; background: var(--border); border-radius: 99px; }
.search { width: 260px; max-width: 100%; }
.mt { margin-top: 6px; }
td { vertical-align: middle; }
@media (max-width: 720px) { .search { width: 100%; } }
</style>
