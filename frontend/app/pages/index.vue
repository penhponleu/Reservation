<script setup lang="ts">
const api = useApi();
const form = reactive({ username: "", email: "", phone: "" });
const errors = ref<Record<string, string[]>>({});
const message = ref("");
const qr = ref(""); // QR image after register (same as the email)
const loading = ref(false);

async function submit() {
  loading.value = true;
  errors.value = {};
  message.value = "";
  qr.value = "";
  try {
    const res = await api("/register", { method: "POST", body: form });
    message.value = res.message;
    qr.value = res.qr;
    form.username = "";
    form.email = "";
    form.phone = "";
  } catch (e: any) {
    // Form valid? No → Show errors
    errors.value = e.data?.errors ?? {
      general: [e.data?.message ?? "Server error"],
    };
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="card narrow">
    <h1>Event Registration</h1>
    <form @submit.prevent="submit">
      <label>Username</label>
      <input v-model="form.username" type="text" placeholder="Your name" autocomplete="name" />
      <p v-if="errors.username" class="error">{{ errors.username[0] }}</p>

      <label>Email</label>
      <input v-model="form.email" type="email" placeholder="you@example.com" />
      <p v-if="errors.email" class="error">{{ errors.email[0] }}</p>

      <label>Phone</label>
      <input v-model="form.phone" type="tel" placeholder="012 345 678" />
      <p v-if="errors.phone" class="error">{{ errors.phone[0] }}</p>

      <p v-if="errors.general" class="error">{{ errors.general[0] }}</p>

      <button :disabled="loading">
        {{ loading ? "Sending..." : "Register" }}
      </button>
    </form>
    <p v-if="message" class="success">{{ message }}</p>

    <div v-if="qr" class="qr">
      <img :src="qr" width="250" height="250" alt="Your QR code" />
      <p>Show this QR code at the door.</p>
      <a :href="qr" download="my-qr-code.png"><button type="button">Download QR</button></a>
    </div>
  </div>
</template>
