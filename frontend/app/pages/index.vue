<script setup lang="ts">
definePageMeta({ alias: "/register" }); // same page at / and /register

const api = useApi();
const form = reactive({ fullname: "", email: "", phone: "" });
const errors = ref<Record<string, string[]>>({});
const message = ref("");
// ticket box after register (day/date = when they registered, e.g. WED · 28 OCT 2026)
const ticket = ref<{ qr: string; fullname: string; email: string; phone: string; day: string; date: string } | null>(null);
const loading = ref(false);
const ticketOpen = ref(false); // "My ticket" button opens / closes the small QR box

async function submit() {
  loading.value = true;
  errors.value = {};
  message.value = "";
  ticket.value = null;
  ticketOpen.value = false;
  try {
    const res = await api("/register", { method: "POST", body: form });
    message.value = res.message;
    const now = new Date();
    ticket.value = {
      qr: res.qr, // same QR as in the email
      ...form,
      day: now.toLocaleDateString("en-GB", { weekday: "short" }).toUpperCase(),
      date: now.toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" }).toUpperCase(),
    };
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

    <!-- "My ticket" button → small drop-down box with the QR code this guest registered with -->
    <div v-if="ticket" class="ticket-wrap">
      <button
        type="button"
        class="ticket-toggle"
        :aria-expanded="ticketOpen"
        aria-controls="ticket-box"
        @click="ticketOpen = !ticketOpen"
      >
        <svg class="ticket-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M3 8a2 2 0 0 0 2-2h14a2 2 0 0 0 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 0-2 2H5a2 2 0 0 0-2-2v-2a2 2 0 0 0 0-4z" />
          <path d="M14 6v2m0 3v2m0 3v2" />
        </svg>
        <span>{{ ticketOpen ? "Hide ticket" : "Open ticket" }}</span>
        <svg :class="['chevron', { up: ticketOpen }]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="m6 9 6 6 6-6" />
        </svg>
      </button>

      <div v-show="ticketOpen" id="ticket-box">
        <!-- Event ticket: main part + tear-off stub with the QR code -->
        <div class="tk">
          <div class="tk-main">
            <div class="tk-head">
              <span class="tk-icon" aria-hidden="true">🎟</span>
              <span class="tk-title">TICKET</span>
            </div>
            <div class="tk-event">EVENT RESERVATION</div>
            <div class="tk-name">{{ ticket.fullname }}</div>
            <div class="tk-row">
              <div class="tk-box">
                <span class="tk-day">{{ ticket.day }}</span>
                <span>{{ ticket.date }}</span>
              </div>
              <div class="tk-box tk-access">ACCESS</div>
            </div>
            <div class="tk-contact">{{ ticket.email }} · {{ ticket.phone }}</div>
          </div>
          <div class="tk-stub">
            <img :src="ticket.qr" width="104" height="104" alt="Your QR code" />
            <span class="tk-scan">SCAN AT DOOR</span>
          </div>
        </div>
        <a :href="ticket.qr" download="my-qr-ticket.png"><button type="button" class="ticket-download">Download QR</button></a>
      </div>
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

/* "My ticket" button */
.ticket-wrap { margin-top: 12px; }
.ticket-toggle {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
  width: 100%;
  margin: 0;
  padding: 13px 18px;
  font-size: 15px;
  letter-spacing: 0.02em;
  color: #fff;
  background: linear-gradient(115deg, #ea7a2c 0%, #c8512f 48%, #7a3b8f 100%);
  border-radius: 10px;
  box-shadow: 0 6px 16px rgba(200, 81, 47, 0.3);
  transition: transform 0.15s, box-shadow 0.15s, filter 0.15s;
}
.ticket-toggle:hover:not(:disabled) {
  background: linear-gradient(115deg, #ea7a2c 0%, #c8512f 48%, #7a3b8f 100%);
  filter: brightness(1.06);
  transform: translateY(-1px);
  box-shadow: 0 10px 22px rgba(200, 81, 47, 0.38);
}
.ticket-toggle:active { transform: translateY(0); }
.ticket-toggle:focus-visible { outline: 3px solid rgba(122, 59, 143, 0.35); outline-offset: 2px; }
.ticket-ico { width: 20px; height: 20px; }
.chevron { width: 18px; height: 18px; transition: transform 0.2s; }
.chevron.up { transform: rotate(180deg); }

/* Event ticket (orange → purple, tear-off stub with the QR code) */
.tk {
  --ink: #fff4e6;
  position: relative;
  display: flex;
  margin-top: 10px;
  color: var(--ink);
  background: linear-gradient(115deg, #ea7a2c 0%, #c8512f 48%, #7a3b8f 100%);
  border-radius: 14px;
  box-shadow: 0 10px 28px rgba(122, 59, 143, 0.35);
  overflow: hidden;
}
.tk-main { flex: 1; min-width: 0; padding: 16px 16px 14px 18px; }
.tk-head { display: flex; align-items: center; gap: 10px; }
.tk-icon { display: grid; place-items: center; width: 34px; height: 34px; border: 2px solid var(--ink); border-radius: 50%; font-size: 16px; }
.tk-title { font-size: 30px; font-weight: 900; letter-spacing: 0.06em; line-height: 1; }
.tk-event { margin-top: 8px; font-size: 12px; font-weight: 800; letter-spacing: 0.12em; opacity: 0.9; }
.tk-name { margin-top: 2px; font-size: 17px; font-weight: 800; text-transform: uppercase; overflow-wrap: anywhere; }
.tk-row { display: flex; gap: 6px; margin-top: 10px; }
.tk-box { display: flex; align-items: center; gap: 8px; padding: 5px 8px; font-size: 11px; font-weight: 700; letter-spacing: 0.06em; white-space: nowrap; border: 1px solid rgba(255, 244, 230, 0.7); }
.tk-day { padding-right: 8px; border-right: 1px solid rgba(255, 244, 230, 0.7); }
.tk-access { font-size: 13px; letter-spacing: 0.14em; }
.tk-contact { margin-top: 8px; font-size: 11px; opacity: 0.85; overflow-wrap: anywhere; }

/* tear-off stub: dashed line + half-circle notches top and bottom */
.tk-stub {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  width: 136px;
  padding: 12px;
  border-left: 2px dashed rgba(255, 244, 230, 0.6);
  background: rgba(0, 0, 0, 0.08);
}
.tk-stub::before, .tk-stub::after { content: ""; position: absolute; left: -11px; width: 20px; height: 20px; border-radius: 50%; background: var(--card); }
.tk-stub::before { top: -10px; }
.tk-stub::after { bottom: -10px; }
.tk-stub img { display: block; padding: 6px; background: #fff; border-radius: 8px; }
.tk-scan { font-size: 10px; font-weight: 800; letter-spacing: 0.12em; }

.ticket-download { width: 100%; margin-top: 12px; }

/* very narrow phones: QR stub goes under the ticket */
@media (max-width: 380px) {
  .tk { flex-direction: column; }
  .tk-stub { width: auto; border-left: 0; border-top: 2px dashed rgba(255, 244, 230, 0.6); }
  .tk-stub::before, .tk-stub::after { display: none; }
}
</style>
