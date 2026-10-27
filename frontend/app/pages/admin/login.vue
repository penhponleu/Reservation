<script setup lang="ts">
const api = useApi();
const route = useRoute();
const token = useCookie<string | null>("token", { maxAge: 60 * 60 * 12 });
const form = reactive({ email: "", password: "" });
const error = ref("");
const loading = ref(false);
const showPassword = ref(false);

async function login() {
  loading.value = true;
  error.value = "";
  try {
    const res = await api("/login", { method: "POST", body: form });
    token.value = res.token;
    // back to the page that asked for login (e.g. /admin/scan); only local paths
    const back = String(route.query.redirect ?? "");
    await navigateTo(back.startsWith("/admin") ? back : "/admin");
  } catch (e: any) {
    error.value = e.data?.message ?? "Wrong email or password";
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="card narrow">
    <h1>Admin Login</h1>
    <form @submit.prevent="login">
      <label>Email</label>
      <input v-model="form.email" type="email" placeholder="admin" autocomplete="username">
      <label>Password</label>
      <input v-model="form.password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password">
      <label class="check">
        <input v-model="showPassword" type="checkbox"> Show password
      </label>
      <p v-if="error" class="error">{{ error }}</p>
      <button :disabled="loading">Login</button>
    </form>
  </div>
</template>
