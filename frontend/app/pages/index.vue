<script setup lang="ts">
definePageMeta({ alias: "/register" }); // same page at / and /register

const api = useApi();
const form = reactive({ fullname: "", email: "", phone: "" });
const errors = ref<Record<string, string[]>>({});
const message = ref("");
const qr = ref(""); // QR shown only if the email failed
const loading = ref(false);

async function submit() {
  loading.value = true;
  errors.value = {};
  message.value = "";
  qr.value = "";
  try {
    const res = await api("/register", { method: "POST", body: form });
    message.value = res.ticket_no ? `${res.message} Your ticket: ${res.ticket_no}` : res.message;
    qr.value = res.email_sent ? "" : res.qr;
    Object.assign(form, { fullname: "", email: "", phone: "" });
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
  <div class="card narrow register">
    <div class="register-head">
      <h1>Event Registration</h1>
      <p>Fill in your details and we'll email your QR code to your Gmail.</p>
    </div>
    <form @submit.prevent="submit">
      <label>Full name</label>
      <input v-model="form.fullname" type="text" placeholder="Your full name" autocomplete="name" />
      <p v-if="errors.fullname" class="error">{{ errors.fullname[0] }}</p>

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

<style scoped>
/* Card */
.register { max-width: 440px; padding: 32px; }

/* Title */
.register-head { text-align: center; margin-bottom: 8px; }
.register-head h1 { font-size: 24px; }
.register-head p { margin: 0; color: var(--muted); font-size: 14px; }

/* Full-width submit button */
.register form button { width: 100%; margin-top: 24px; padding: 12px; }

/* Success message */
.register > .success {
  margin: 16px 0 0;
  padding: 12px;
  background: var(--green-bg);
  border-radius: 8px;
  text-align: center;
}
</style>
