<script setup lang="ts">
definePageMeta({ middleware: "auth" });

const api = useApi();
const token = useCookie<string | null>("token");
const rows = ref<any[]>([]);
const stats = ref({ total: 0, emailed: 0, checked_in: 0 });
const busyId = ref<number | null>(null);
const notice = ref("");
const loading = ref(false);

const fmt = (d: string | null) =>
  d
    ? new Date(d).toLocaleString("en-GB", { day: "2-digit", month: "short", hour: "2-digit", minute: "2-digit" })
    : "";

// "heng penhponleu" → "HP" for the round avatar
const initials = (name: string) =>
  (name || "?").trim().split(/\s+/).slice(0, 2).map((w) => w[0]?.toUpperCase()).join("");

const pct = (n: number) => (stats.value.total ? Math.round((n / stats.value.total) * 100) : 0);
const waiting = computed(() => stats.value.total - stats.value.checked_in);

async function load() {
  loading.value = true;
  try {
    const res = await api("/registrations");
    rows.value = res.data;
    stats.value = res.stats;
  } catch (e: any) {
    if (e.status === 401) {
      token.value = null;
      navigateTo("/admin/login");
    }
  } finally {
    loading.value = false;
  }
}

// Message at the top, hides itself after a few seconds
let noticeTimer: ReturnType<typeof setTimeout> | undefined;
function showNotice(text: string) {
  notice.value = text;
  clearTimeout(noticeTimer);
  noticeTimer = setTimeout(() => (notice.value = ""), 4000);
}

// Search + filter tabs (done in the browser, list is small)
const search = ref("");
const filter = ref<"all" | "checked" | "waiting" | "no_email">("all");
const filters = computed(() => [
  { key: "all", label: "All", count: stats.value.total },
  { key: "checked", label: "Checked in", count: stats.value.checked_in },
  { key: "waiting", label: "Not arrived", count: waiting.value },
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
      return !q || [r.ticket_no, r.fullname, r.email, r.phone].some((v) => String(v ?? "").toLowerCase().includes(q));
    });
});

// Resend email → sent_by = admin id
async function resend(id: number) {
  busyId.value = id;
  try {
    const res = await api(`/registrations/${id}/resend`, { method: "POST" });
    showNotice(res.message);
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
    showNotice(res.message);
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
onBeforeUnmount(() => clearTimeout(noticeTimer));
</script>

<template>
  <div class="dash">
    <!-- Header -->
    <header class="dash-head">
      <div>
        <p class="eyebrow">Event Reservation</p>
        <h1>Dashboard</h1>
      </div>
      <div class="head-actions">
        <button class="light" :disabled="loading" @click="load">{{ loading ? "Refreshing..." : "Refresh" }}</button>
        <NuxtLink to="/admin/scan"><button>Open Scanner</button></NuxtLink>
        <button class="light" @click="logout">Logout</button>
      </div>
    </header>

    <!-- Flow: registered → email sent → checked in -->
    <section class="flow">
      <div class="step indigo">
        <span class="step-label">1 · Registered</span>
        <b>{{ stats.total }}</b>
        <span class="step-sub">Name, email, phone</span>
      </div>
      <span class="arrow" aria-hidden="true">→</span>
      <div class="step teal">
        <span class="step-label">2 · Email sent</span>
        <b>{{ stats.emailed }}</b>
        <span class="step-sub">{{ pct(stats.emailed) }}% got their QR code</span>
      </div>
      <span class="arrow" aria-hidden="true">→</span>
      <div class="step amber">
        <span class="step-label">3 · Checked in</span>
        <b>{{ stats.checked_in }}</b>
        <span class="step-sub">{{ waiting }} not arrived yet</span>
      </div>
    </section>

    <!-- Check-in progress -->
    <section class="card progress-card">
      <div class="row">
        <span class="progress-title">Check-in progress</span>
        <span class="progress-num">{{ stats.checked_in }} / {{ stats.total }} · {{ pct(stats.checked_in) }}%</span>
      </div>
      <div class="bar"><div class="bar-fill" :style="{ width: pct(stats.checked_in) + '%' }" /></div>
    </section>

    <!-- Guests -->
    <section class="card guests">
      <div class="toolbar">
        <div class="tabs">
          <button
            v-for="f in filters"
            :key="f.key"
            :class="['tab', { active: filter === f.key }]"
            @click="filter = f.key"
          >
            {{ f.label }} <span class="tab-count">{{ f.count }}</span>
          </button>
        </div>
        <input v-model="search" class="search" type="search" placeholder="Search ticket, name, email…" />
      </div>

      <p v-if="notice" class="notice">{{ notice }}</p>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Ticket</th>
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
              <td><span class="ticket">{{ r.ticket_no || "—" }}</span></td>

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
                  <div class="guest">
                    <span class="avatar">{{ initials(r.fullname) }}</span>
                    <div>
                      <div class="guest-name">{{ r.fullname }}</div>
                      <div class="muted small">{{ r.email }}</div>
                    </div>
                  </div>
                </td>
                <td>{{ r.phone }}</td>
              </template>

              <td>
                <span v-if="r.email_sent_at" class="badge teal" :title="fmt(r.email_sent_at)">Sent · {{ fmt(r.email_sent_at) }}</span>
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
                  <button class="sm light" :disabled="busyId === r.id" @click="resend(r.id)">
                    {{ busyId === r.id ? "Sending..." : "Resend email" }}
                  </button>
                </template>
              </td>
            </tr>
            <tr v-if="!shown.length">
              <td colspan="7" class="empty">
                {{ rows.length ? "No guests match this search or filter." : "No registrations yet." }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<style scoped>
/* ---------- Header ---------- */
.dash-head { display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px; margin-bottom: 20px; }
.dash-head h1 { font-size: 28px; margin: 0; }
.eyebrow { margin: 0 0 4px; font-size: 13px; font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; color: var(--primary); }
.head-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.head-actions button { margin: 0; }

/* ---------- Flow cards (like the 3-step diagram) ---------- */
.flow { display: grid; grid-template-columns: 1fr auto 1fr auto 1fr; align-items: stretch; gap: 12px; margin-bottom: 16px; }
.step { display: flex; flex-direction: column; gap: 2px; padding: 18px 20px; border-radius: 12px; color: #fff; box-shadow: var(--shadow); }
.step b { font-size: 34px; line-height: 1.1; }
.step-label { font-size: 13px; font-weight: 600; opacity: 0.9; }
.step-sub { font-size: 13px; opacity: 0.85; }
.step.indigo { background: linear-gradient(135deg, #4f46e5, #6d5ce8); }
.step.teal { background: linear-gradient(135deg, #0f766e, #14917f); }
.step.amber { background: linear-gradient(135deg, #b45309, #d06a1a); }
.arrow { align-self: center; color: var(--muted); font-size: 22px; }

/* ---------- Progress ---------- */
.progress-card { padding: 18px 24px; }
.progress-title { font-weight: 600; }
.progress-num { color: var(--muted); font-size: 14px; }
.bar { height: 10px; margin-top: 10px; background: var(--bg); border-radius: 99px; overflow: hidden; }
.bar-fill { height: 100%; background: linear-gradient(90deg, #15803d, #22c55e); border-radius: 99px; transition: width 0.4s; }

/* ---------- Toolbar ---------- */
.toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 12px; }
.tabs { display: flex; gap: 4px; flex-wrap: wrap; padding: 4px; background: var(--bg); border-radius: 10px; }
.tab { margin: 0; padding: 7px 12px; font-size: 13px; background: transparent; color: var(--muted); border-radius: 8px; }
.tab:hover:not(:disabled) { background: var(--border); color: var(--text); }
.tab.active, .tab.active:hover { background: var(--card); color: var(--text); box-shadow: 0 1px 3px rgba(15, 23, 42, 0.12); }
.tab-count { margin-left: 4px; padding: 1px 7px; font-size: 12px; background: var(--border); border-radius: 99px; }
.search { width: 260px; max-width: 100%; }

.notice { margin: 0 0 12px; padding: 10px 14px; background: var(--green-bg); color: var(--green); border-radius: 8px; font-size: 14px; }

/* ---------- Table ---------- */
.guest { display: flex; align-items: center; gap: 10px; }
.avatar { flex: none; display: grid; place-items: center; width: 36px; height: 36px; border-radius: 50%; background: #eef2ff; color: var(--primary); font-size: 13px; font-weight: 700; }
.guest-name { font-weight: 600; }
.ticket { font-family: ui-monospace, "Cascadia Mono", Consolas, monospace; font-size: 13px; font-weight: 600; color: var(--primary); white-space: nowrap; }
.muted { color: var(--muted); }
.small { font-size: 13px; }
.mt { margin-top: 6px; }
td { vertical-align: middle; }
td input { min-width: 160px; margin: 0; padding: 7px 10px; font-size: 14px; }
.empty { padding: 32px 8px; text-align: center; color: var(--muted); }

.badge { display: inline-block; padding: 3px 10px; font-size: 12px; font-weight: 600; border-radius: 99px; white-space: nowrap; }
.badge.teal { background: #ccfbf1; color: #0f766e; }
.badge.green { background: var(--green-bg); color: var(--green); }
.badge.red { background: var(--red-bg); color: var(--red); }
.badge.gray { background: var(--bg); color: var(--muted); border: 1px solid var(--border); }

.actions { white-space: nowrap; text-align: right; }
.actions button + button { margin-left: 6px; }
button.sm { margin: 0; padding: 6px 12px; font-size: 13px; }

/* ---------- Phone screens ---------- */
@media (max-width: 720px) {
  .flow { grid-template-columns: 1fr; }
  .arrow { display: none; }
  .search { width: 100%; }
}
</style>
