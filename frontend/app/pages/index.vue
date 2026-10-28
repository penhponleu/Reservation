<script setup lang="ts">
definePageMeta({ alias: "/register" }); // same page at / and /register

const api = useApi();
const form = reactive({ fullname: "", email: "", phone: "" });
const errors = ref<Record<string, string[]>>({});
const message = ref("");
const ticket = ref<{ qr: string; fullname: string; email: string; phone: string } | null>(null); // ticket box after register
const loading = ref(false);

async function submit() {
  loading.value = true;
  errors.value = {};
  message.value = "";
  ticket.value = null;
  try {
    const res = await api("/register", { method: "POST", body: form });
    message.value = res.message;
    ticket.value = { qr: res.qr, ...form }; // same QR as in the email
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

    <!-- Ticket box: the QR code this guest just registered with -->
    <div v-if="ticket" class="ticket">
      <div class="ticket-top">
        <span class="ticket-label">Your ticket</span>
        <span class="ticket-status">Registered</span>
      </div>
      <img :src="ticket.qr" width="220" height="220" alt="Your QR code" />
      <p class="ticket-hint">Show this QR code at the door to check in.</p>
      <div class="ticket-cut" aria-hidden="true" />
      <dl class="ticket-info">
        <dt>Name</dt><dd>{{ ticket.fullname }}</dd>
        <dt>Email</dt><dd>{{ ticket.email }}</dd>
        <dt>Phone</dt><dd>{{ ticket.phone }}</dd>
      </dl>
      <a :href="ticket.qr" download="my-qr-ticket.png"><button type="button">Download QR</button></a>
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

/* Ticket box */
.ticket {
  margin-top: 16px;
  padding: 20px;
  text-align: center;
  background: #fff;
  border: 1px solid var(--border);
  border-top: 6px solid var(--primary);
  border-radius: 12px;
  box-shadow: var(--shadow);
}
.ticket-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.ticket-label { font-weight: 700; }
.ticket-status { padding: 2px 10px; font-size: 12px; font-weight: 600; color: var(--green); background: var(--green-bg); border-radius: 99px; }
.ticket img { display: block; margin: 0 auto; border: 1px solid var(--border); border-radius: 8px; }
.ticket-hint { margin: 10px 0 0; color: var(--muted); font-size: 13px; }
/* dashed tear line with notches, like a paper ticket */
.ticket-cut { position: relative; margin: 16px -20px; border-top: 2px dashed var(--border); }
.ticket-cut::before, .ticket-cut::after { content: ""; position: absolute; top: -10px; width: 18px; height: 18px; border-radius: 50%; background: var(--card); border: 1px solid var(--border); }
.ticket-cut::before { left: -10px; }
.ticket-cut::after { right: -10px; }
.ticket-info { display: grid; grid-template-columns: auto 1fr; gap: 6px 12px; margin: 0; text-align: left; font-size: 14px; }
.ticket-info dt { color: var(--muted); }
.ticket-info dd { margin: 0; font-weight: 600; overflow-wrap: anywhere; }
.ticket button { width: 100%; }
</style>
