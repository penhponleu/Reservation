<script setup lang="ts">
definePageMeta({ middleware: "auth", layout: "admin" });

const api = useApi();
const me = useState<any>("me");
const form = reactive({ fullname: "", email: "", current_password: "", password: "", password_confirmation: "" });
const errors = ref<Record<string, string[]>>({});
const message = ref("");
const saving = ref(false);

// fill the form with my account (sidebar may have loaded it already)
async function fill() {
  if (!me.value) me.value = await api("/me");
  Object.assign(form, { fullname: me.value.fullname, email: me.value.email });
}

async function save() {
  saving.value = true;
  errors.value = {};
  message.value = "";
  try {
    const res = await api("/me", { method: "PUT", body: form });
    me.value = res.data; // sidebar shows the new name
    message.value = res.message;
    Object.assign(form, { current_password: "", password: "", password_confirmation: "" });
  } catch (e: any) {
    errors.value = e.data?.errors ?? { general: [e.data?.message ?? "Save failed"] };
  } finally {
    saving.value = false;
  }
}

onMounted(fill);
</script>

<template>
  <div>
    <div class="page-head">
      <div>
        <h1>Settings</h1>
        <p>Your account: name, login email and password.</p>
      </div>
    </div>

    <form class="settings" @submit.prevent="save">
      <section class="card">
        <h3>Profile</h3>
        <label>Full name</label>
        <input v-model="form.fullname" type="text" />
        <p v-if="errors.fullname" class="error">{{ errors.fullname[0] }}</p>

        <label>Email (login)</label>
        <input v-model="form.email" type="email" />
        <p v-if="errors.email" class="error">{{ errors.email[0] }}</p>
      </section>

      <section class="card">
        <h3>Change password</h3>
        <p class="muted small">Leave empty to keep your current password.</p>
        <label>Current password</label>
        <input v-model="form.current_password" type="password" autocomplete="current-password" />
        <p v-if="errors.current_password" class="error">{{ errors.current_password[0] }}</p>

        <label>New password</label>
        <input v-model="form.password" type="password" autocomplete="new-password" placeholder="At least 6 characters" />
        <p v-if="errors.password" class="error">{{ errors.password[0] }}</p>

        <label>Confirm new password</label>
        <input v-model="form.password_confirmation" type="password" autocomplete="new-password" />
      </section>

      <div class="save-row">
        <p v-if="message" class="notice">{{ message }}</p>
        <p v-if="errors.general" class="error">{{ errors.general[0] }}</p>
        <button :disabled="saving">{{ saving ? "Saving..." : "Save settings" }}</button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.settings { max-width: 560px; }
.settings h3 { margin-bottom: 0; }
.save-row button { margin-top: 0; }
</style>
