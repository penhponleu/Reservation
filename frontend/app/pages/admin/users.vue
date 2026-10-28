<script setup lang="ts">
definePageMeta({ middleware: "auth", layout: "admin" });

const api = useApi();
const me = useState<any>("me");
const users = ref<any[]>([]);
const busyId = ref<number | null>(null);
const notice = ref({ text: "", bad: false });

const initials = (name: string) =>
  (name || "?").trim().split(/\s+/).slice(0, 2).map((w) => w[0]?.toUpperCase()).join("");
const fmtDate = (d: string | null) =>
  d ? new Date(d).toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" }) : "—";

async function load() {
  users.value = await api("/users");
}

let noticeTimer: ReturnType<typeof setTimeout> | undefined;
function showNotice(text: string, bad = false) {
  notice.value = { text, bad };
  clearTimeout(noticeTimer);
  noticeTimer = setTimeout(() => (notice.value.text = ""), 4000);
}

// Add user form
const showAdd = ref(false);
const add = reactive({ fullname: "", email: "", password: "" });
const addErrors = ref<Record<string, string[]>>({});
const adding = ref(false);

async function create() {
  adding.value = true;
  addErrors.value = {};
  try {
    const res = await api("/users", { method: "POST", body: add });
    showNotice(res.message);
    Object.assign(add, { fullname: "", email: "", password: "" });
    showAdd.value = false;
    await load();
  } catch (e: any) {
    addErrors.value = e.data?.errors ?? { general: [e.data?.message ?? "Add failed"] };
  } finally {
    adding.value = false;
  }
}

// Edit in the table (empty password = keep the old one)
const editId = ref<number | null>(null);
const edit = reactive({ fullname: "", email: "", password: "" });
const editErrors = ref<Record<string, string[]>>({});

function startEdit(u: any) {
  editId.value = u.id;
  editErrors.value = {};
  Object.assign(edit, { fullname: u.fullname, email: u.email, password: "" });
}

async function save(id: number) {
  busyId.value = id;
  editErrors.value = {};
  try {
    const res = await api(`/users/${id}`, { method: "PUT", body: edit });
    showNotice(res.message);
    editId.value = null;
    if (me.value?.id === id) me.value = res.data; // sidebar shows the new name
    await load();
  } catch (e: any) {
    editErrors.value = e.data?.errors ?? { general: [e.data?.message ?? "Save failed"] };
  } finally {
    busyId.value = null;
  }
}

async function remove(u: any) {
  if (!confirm(`Delete user ${u.fullname} (${u.email})? They will no longer be able to log in.`)) return;
  busyId.value = u.id;
  try {
    const res = await api(`/users/${u.id}`, { method: "DELETE" });
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
        <h1>Users</h1>
        <p>Staff and admin accounts that can log in to this dashboard.</p>
      </div>
      <div class="actions-row">
        <button @click="showAdd = !showAdd">{{ showAdd ? "Close" : "+ Add user" }}</button>
      </div>
    </div>

    <!-- Add user -->
    <section v-if="showAdd" class="card">
      <h3>New user</h3>
      <form class="add-form" @submit.prevent="create">
        <div>
          <label>Full name</label>
          <input v-model="add.fullname" type="text" autocomplete="off" />
          <p v-if="addErrors.fullname" class="error">{{ addErrors.fullname[0] }}</p>
        </div>
        <div>
          <label>Email (login)</label>
          <input v-model="add.email" type="email" autocomplete="off" />
          <p v-if="addErrors.email" class="error">{{ addErrors.email[0] }}</p>
        </div>
        <div>
          <label>Password</label>
          <input v-model="add.password" type="password" autocomplete="new-password" placeholder="At least 6 characters" />
          <p v-if="addErrors.password" class="error">{{ addErrors.password[0] }}</p>
        </div>
        <div class="add-btn">
          <button :disabled="adding">{{ adding ? "Adding..." : "Add user" }}</button>
        </div>
      </form>
      <p v-if="addErrors.general" class="error">{{ addErrors.general[0] }}</p>
    </section>

    <section class="card table-wrap">
      <p v-if="notice.text" :class="['notice', { bad: notice.bad }]">{{ notice.text }}</p>
      <table>
        <thead>
          <tr>
            <th>User</th>
            <th>Email (login)</th>
            <th>Password</th>
            <th>Added</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in users" :key="u.id">
            <template v-if="editId === u.id">
              <td>
                <input v-model="edit.fullname" type="text" />
                <p v-if="editErrors.fullname" class="error">{{ editErrors.fullname[0] }}</p>
              </td>
              <td>
                <input v-model="edit.email" type="email" />
                <p v-if="editErrors.email" class="error">{{ editErrors.email[0] }}</p>
              </td>
              <td>
                <input v-model="edit.password" type="password" autocomplete="new-password" placeholder="Leave empty to keep" />
                <p v-if="editErrors.password" class="error">{{ editErrors.password[0] }}</p>
              </td>
            </template>
            <template v-else>
              <td>
                <div class="person">
                  <span class="avatar">{{ initials(u.fullname) }}</span>
                  <div class="person-name">
                    {{ u.fullname }}
                    <span v-if="me?.id === u.id" class="badge indigo">You</span>
                  </div>
                </div>
              </td>
              <td>{{ u.email }}</td>
              <td class="muted">••••••</td>
            </template>
            <td class="muted small">{{ fmtDate(u.created_at) }}</td>
            <td class="actions">
              <template v-if="editId === u.id">
                <button class="sm" :disabled="busyId === u.id" @click="save(u.id)">
                  {{ busyId === u.id ? "Saving..." : "Save" }}
                </button>
                <button class="sm light" @click="editId = null">Cancel</button>
                <p v-if="editErrors.general" class="error">{{ editErrors.general[0] }}</p>
              </template>
              <template v-else>
                <button class="sm light" @click="startEdit(u)">Edit</button>
                <button class="sm danger" :disabled="busyId === u.id || me?.id === u.id" @click="remove(u)">Delete</button>
              </template>
            </td>
          </tr>
          <tr v-if="!users.length">
            <td colspan="5" class="empty">No users.</td>
          </tr>
        </tbody>
      </table>
    </section>
  </div>
</template>

<style scoped>
.add-form { display: grid; grid-template-columns: repeat(3, 1fr) auto; gap: 12px; align-items: start; }
.add-form label { margin-top: 8px; }
.add-btn { align-self: end; }
.add-btn button { margin: 0; }
.person-name .badge { margin-left: 6px; }
td { vertical-align: middle; }
@media (max-width: 860px) { .add-form { grid-template-columns: 1fr; } }
</style>
