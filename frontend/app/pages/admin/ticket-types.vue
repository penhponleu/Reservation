<script setup lang="ts">
definePageMeta({ middleware: "auth", layout: "admin" });

const api = useApi();
const types = ref<any[]>([]);
const busyId = ref<number | null>(null);
const notice = ref({ text: "", bad: false });

async function load() {
  types.value = await api("/ticket-types");
}

let noticeTimer: ReturnType<typeof setTimeout> | undefined;
function showNotice(text: string, bad = false) {
  notice.value = { text, bad };
  clearTimeout(noticeTimer);
  noticeTimer = setTimeout(() => (notice.value.text = ""), 4000);
}

// quota "" in the form = no limit (null)
const toBody = (f: { name: string; quota: string | number | null; is_active: boolean }) => ({
  name: f.name,
  quota: f.quota === "" || f.quota === null ? null : Number(f.quota),
  is_active: f.is_active,
});

// Add type
const showAdd = ref(false);
const add = reactive({ name: "", quota: "" as string | number, is_active: true });
const addErrors = ref<Record<string, string[]>>({});

async function create() {
  addErrors.value = {};
  try {
    const res = await api("/ticket-types", { method: "POST", body: toBody(add) });
    showNotice(res.message);
    Object.assign(add, { name: "", quota: "", is_active: true });
    showAdd.value = false;
    await load();
  } catch (e: any) {
    addErrors.value = e.data?.errors ?? { general: [e.data?.message ?? "Add failed"] };
  }
}

// Edit in the table
const editId = ref<number | null>(null);
const edit = reactive({ name: "", quota: "" as string | number, is_active: true });
const editErrors = ref<Record<string, string[]>>({});

function startEdit(t: any) {
  editId.value = t.id;
  editErrors.value = {};
  Object.assign(edit, { name: t.name, quota: t.quota ?? "", is_active: t.is_active });
}

async function save(id: number) {
  busyId.value = id;
  editErrors.value = {};
  try {
    const res = await api(`/ticket-types/${id}`, { method: "PUT", body: toBody(edit) });
    showNotice(res.message);
    editId.value = null;
    await load();
  } catch (e: any) {
    editErrors.value = e.data?.errors ?? { general: [e.data?.message ?? "Save failed"] };
  } finally {
    busyId.value = null;
  }
}

async function remove(t: any) {
  if (!confirm(`Delete ticket type ${t.name}?`)) return;
  busyId.value = t.id;
  try {
    const res = await api(`/ticket-types/${t.id}`, { method: "DELETE" });
    showNotice(res.message);
    await load();
  } catch (e: any) {
    showNotice(e.data?.message ?? "Delete failed", true);
  } finally {
    busyId.value = null;
  }
}

// how full a type is, for the bar
const used = (t: any) => (t.quota ? Math.min(100, Math.round((t.registrations_count / t.quota) * 100)) : 0);

onMounted(load);
onBeforeUnmount(() => clearTimeout(noticeTimer));
</script>

<template>
  <div>
    <div class="page-head">
      <div>
        <h1>Ticket types</h1>
        <p>Kinds of tickets (General, VIP…), how many each can have, and whether they're on.</p>
      </div>
      <div class="actions-row">
        <button @click="showAdd = !showAdd">{{ showAdd ? "Close" : "+ Add type" }}</button>
      </div>
    </div>

    <section v-if="showAdd" class="card">
      <h3>New ticket type</h3>
      <form class="add-form" @submit.prevent="create">
        <div>
          <label>Name</label>
          <input v-model="add.name" type="text" placeholder="e.g. Student" />
          <p v-if="addErrors.name" class="error">{{ addErrors.name[0] }}</p>
        </div>
        <div>
          <label>Quota</label>
          <input v-model="add.quota" type="number" min="1" placeholder="Empty = no limit" />
          <p v-if="addErrors.quota" class="error">{{ addErrors.quota[0] }}</p>
        </div>
        <label class="check"><input v-model="add.is_active" type="checkbox" /> On</label>
        <div class="add-btn"><button>Add type</button></div>
      </form>
      <p v-if="addErrors.general" class="error">{{ addErrors.general[0] }}</p>
    </section>

    <section class="card table-wrap">
      <p v-if="notice.text" :class="['notice', { bad: notice.bad }]">{{ notice.text }}</p>
      <table>
        <thead>
          <tr>
            <th>Type</th>
            <th>Tickets / quota</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="t in types" :key="t.id">
            <template v-if="editId === t.id">
              <td>
                <input v-model="edit.name" type="text" />
                <p v-if="editErrors.name" class="error">{{ editErrors.name[0] }}</p>
              </td>
              <td>
                <input v-model="edit.quota" type="number" min="1" placeholder="Empty = no limit" />
                <p v-if="editErrors.quota" class="error">{{ editErrors.quota[0] }}</p>
              </td>
              <td><label class="check"><input v-model="edit.is_active" type="checkbox" /> On</label></td>
            </template>
            <template v-else>
              <td><span class="badge indigo">{{ t.name }}</span></td>
              <td>
                <div class="quota">
                  <span><b>{{ t.registrations_count }}</b> / {{ t.quota ?? "no limit" }}</span>
                  <div v-if="t.quota" class="bar"><div class="bar-fill" :class="{ full: used(t) >= 100 }" :style="{ width: used(t) + '%' }" /></div>
                </div>
              </td>
              <td>
                <span v-if="!t.is_active" class="badge gray">Off</span>
                <span v-else-if="t.quota && t.registrations_count >= t.quota" class="badge red">Full</span>
                <span v-else class="badge green">On</span>
              </td>
            </template>
            <td class="actions">
              <template v-if="editId === t.id">
                <button class="sm" :disabled="busyId === t.id" @click="save(t.id)">Save</button>
                <button class="sm light" @click="editId = null">Cancel</button>
                <p v-if="editErrors.general" class="error">{{ editErrors.general[0] }}</p>
              </template>
              <template v-else>
                <button class="sm light" @click="startEdit(t)">Edit</button>
                <button class="sm danger" :disabled="busyId === t.id" @click="remove(t)">Delete</button>
              </template>
            </td>
          </tr>
          <tr v-if="!types.length">
            <td colspan="4" class="empty">No ticket types.</td>
          </tr>
        </tbody>
      </table>
    </section>
  </div>
</template>

<style scoped>
.add-form { display: grid; grid-template-columns: 2fr 1fr auto auto; gap: 12px; align-items: end; }
.add-form label { margin-top: 8px; }
.add-form .check { margin: 0 0 10px; }
.add-btn button { margin: 0; }
.quota { min-width: 160px; }
.bar { height: 6px; margin-top: 6px; background: var(--bg); border-radius: 99px; overflow: hidden; }
.bar-fill { height: 100%; background: var(--primary); border-radius: 99px; }
.bar-fill.full { background: var(--red); }
td { vertical-align: middle; }
td input[type="checkbox"] { min-width: 0; }
@media (max-width: 860px) { .add-form { grid-template-columns: 1fr; } }
</style>
