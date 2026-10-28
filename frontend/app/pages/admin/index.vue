<script setup lang="ts">
definePageMeta({ middleware: "auth", layout: "admin" });

const api = useApi();
const rows = ref<any[]>([]);
const stats = ref({ total: 0, emailed: 0, checked_in: 0 });
const loading = ref(false);

const fmt = (d: string | null) =>
  d ? new Date(d).toLocaleString("en-GB", { day: "2-digit", month: "short", hour: "2-digit", minute: "2-digit" }) : "";
const initials = (name: string) =>
  (name || "?").trim().split(/\s+/).slice(0, 2).map((w) => w[0]?.toUpperCase()).join("");
const pct = (n: number) => (stats.value.total ? Math.round((n / stats.value.total) * 100) : 0);
const waiting = computed(() => stats.value.total - stats.value.checked_in);
const latest = computed(() => rows.value.slice(0, 5)); // list is newest first

async function load() {
  loading.value = true;
  try {
    const res = await api("/registrations");
    rows.value = res.data;
    stats.value = res.stats;
  } finally {
    loading.value = false;
  }
}

onMounted(load);
</script>

<template>
  <div>
    <div class="page-head">
      <div>
        <h1>Dashboard</h1>
        <p>Registrations, emails and check-ins at a glance.</p>
      </div>
      <div class="actions-row">
        <button class="light" :disabled="loading" @click="load">{{ loading ? "Refreshing..." : "Refresh" }}</button>
        <NuxtLink to="/admin/scan"><button>Open Scanner</button></NuxtLink>
      </div>
    </div>

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

    <section class="card">
      <div class="row">
        <span class="progress-title">Check-in progress</span>
        <span class="muted small">{{ stats.checked_in }} / {{ stats.total }} · {{ pct(stats.checked_in) }}%</span>
      </div>
      <div class="bar"><div class="bar-fill" :style="{ width: pct(stats.checked_in) + '%' }" /></div>
    </section>

    <section class="card table-wrap">
      <div class="row latest-head">
        <h3>Latest registrations</h3>
        <NuxtLink to="/admin/tickets" class="link">View all tickets →</NuxtLink>
      </div>
      <table>
        <thead>
          <tr>
            <th>Guest</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Check-in</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in latest" :key="r.id">
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
            <td>
              <span v-if="r.email_sent_at" class="badge teal">Sent · {{ fmt(r.email_sent_at) }}</span>
              <span v-else class="badge red">Not sent</span>
            </td>
            <td>
              <span v-if="r.checked_in_at" class="badge green">Checked in · {{ fmt(r.checked_in_at) }}</span>
              <span v-else class="badge gray">Not arrived</span>
            </td>
          </tr>
          <tr v-if="!latest.length">
            <td colspan="4" class="empty">No registrations yet.</td>
          </tr>
        </tbody>
      </table>
    </section>
  </div>
</template>

<style scoped>
.flow { display: grid; grid-template-columns: 1fr auto 1fr auto 1fr; gap: 12px; margin-bottom: 16px; }
.step { display: flex; flex-direction: column; gap: 2px; padding: 18px 20px; border-radius: 12px; color: #fff; box-shadow: var(--shadow); }
.step b { font-size: 34px; line-height: 1.1; }
.step-label { font-size: 13px; font-weight: 600; opacity: 0.9; }
.step-sub { font-size: 13px; opacity: 0.85; }
.step.indigo { background: linear-gradient(135deg, #4f46e5, #6d5ce8); }
.step.teal { background: linear-gradient(135deg, #0f766e, #14917f); }
.step.amber { background: linear-gradient(135deg, #b45309, #d06a1a); }
.arrow { align-self: center; color: var(--muted); font-size: 22px; }

.progress-title { font-weight: 600; }
.bar { height: 10px; margin-top: 10px; background: var(--bg); border-radius: 99px; overflow: hidden; }
.bar-fill { height: 100%; background: linear-gradient(90deg, #15803d, #22c55e); border-radius: 99px; transition: width 0.4s; }

.latest-head { margin-bottom: 8px; }
.latest-head h3 { margin: 0; }
.link { color: var(--primary); font-size: 14px; font-weight: 600; text-decoration: none; }

@media (max-width: 720px) {
  .flow { grid-template-columns: 1fr; }
  .arrow { display: none; }
}
</style>
