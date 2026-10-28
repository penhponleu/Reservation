<script setup lang="ts">
import { QrcodeStream } from "vue-qrcode-reader";

// No login needed: page opens straight to the camera
const api = useApi();
const token = useCookie<string | null>("token");
if (token.value) setPageLayout("admin"); // logged-in staff see the right sidebar
const paused = ref(false);
const result = ref<any>(null);
const cameraError = ref("");
const cameraKey = ref(0); // change → camera restarts and asks permission again
const logs = ref<any[]>([]);

// Staff scans QR code → backend decides success / already_checked_in / invalid
async function check(value: string) {
  if (!value) return;
  paused.value = true; // stop camera until "Scan next"
  try {
    const res = await api("/checkin", { method: "POST", body: { value } });
    // server time is UTC → show "already checked in" time in this device's time zone
    if (res.result === "already_checked_in" && res.registration?.checked_in_at) {
      res.message = `Already checked in at ${new Date(res.registration.checked_in_at).toLocaleString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      })}`;
    }
    result.value = res;
  } catch (e: any) {
    result.value = {
      result: "invalid",
      message: e.data?.message ?? "Request failed",
    };
  }
  startCountdown();
  loadLogs();
}

// Pop-up counts down, then closes and turns the camera back on
const COUNTDOWN = 10;
const countdown = ref(0);
let timer: ReturnType<typeof setInterval> | undefined;

function startCountdown() {
  clearInterval(timer);
  countdown.value = COUNTDOWN;
  timer = setInterval(() => {
    if (--countdown.value <= 0) next();
  }, 1000);
}

onBeforeUnmount(() => clearInterval(timer));

function onDetect(codes: { rawValue: string }[]) {
  if (codes.length) check(codes[0].rawValue);
}

function onError(err: any) {
  cameraError.value =
    err.name === "NotAllowedError"
      ? "Camera permission denied. Please allow the camera."
      : err.name === "NotReadableError"
        ? "Camera is busy in another app or tab. Close it, then click Allow camera."
        : `Camera error: ${err.message}`;
}

// "Allow camera" button → ask the browser for the camera again
function retryCamera() {
  cameraError.value = "";
  cameraKey.value++;
}

function next() {
  clearInterval(timer);
  result.value = null;
  paused.value = false;
}

// Recent scans show guest names/emails → only for logged-in staff
async function loadLogs() {
  if (!token.value) return;
  try {
    logs.value = await api("/checkin-logs");
  } catch {
    logs.value = [];
  }
}

onMounted(loadLogs);
</script>

<template>
  <div>
    <div class="card narrow">
      <h1>Scan QR Code</h1>

      <QrcodeStream :key="cameraKey" :paused="paused" @detect="onDetect" @error="onError" />
      <template v-if="cameraError">
        <p class="error">{{ cameraError }}</p>
        <button @click="retryCamera">Allow camera</button>
      </template>
    </div>

    <!-- Scan result pop-up: closes by itself after the countdown -->
    <div v-if="result" class="popup-bg" @click.self="next">
      
      <div :class="['popup', 'box', result.result]">
        <div class="popup-title">{{ result.message }}</div>
        <div v-if="result.registration" class="popup-name">
          {{ result.registration.fullname }}        </div>
        <div class="popup-count">Next scan in {{ countdown }}...</div>
        <button @click="next">Scan next now</button>
      </div>
    </div>
   

    <div v-if="token" class="card table-wrap">
      <h3>Recent scans</h3>
      <table>
        <thead>
          <tr>
            <th>Time</th>
            <th>Guest</th>
            <th>Result</th>
            <th>Staff</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="l in logs" :key="l.id">
            <td>{{ new Date(l.scanned_at).toLocaleString() }}</td>
            <td>
              {{
                l.registration
                  ? `${l.registration.fullname} (${l.registration.email})`
                  : l.scanned_value
              }}
            </td>
            <td>{{ l.result }}</td>
            <td>{{ l.user?.fullname }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
/* Dark screen behind the pop-up */
.popup-bg {
  position: fixed;
  inset: 0;
  z-index: 50;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  background: rgba(15, 23, 42, 0.55);
}
/* Pop-up card (colors come from .box.success / already_checked_in / invalid) */
.popup {
  width: 100%;
  max-width: 360px;
  margin: 0;
  padding: 32px 24px;
  box-shadow: 0 20px 60px rgba(15, 23, 42, 0.3);
}
.popup-title { font-size: 28px; overflow-wrap: anywhere; } /* long messages stay inside */
.popup-name { margin-top: 8px; font-size: 20px; color: var(--text); }.popup-count { margin-top: 16px; font-size: 14px; font-weight: 400; }
.popup button { width: 100%; }
</style>
