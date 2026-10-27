<script setup lang="ts">
import { QrcodeStream } from "vue-qrcode-reader";

// No login needed: page opens straight to the camera
const api = useApi();
const token = useCookie<string | null>("token");
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
    result.value = await api("/checkin", { method: "POST", body: { value } });
  } catch (e: any) {
    result.value = {
      result: "invalid",
      message: e.data?.message ?? "Request failed",
    };
  }
  loadLogs();
}

function onDetect(codes: { rawValue: string }[]) {
  if (codes.length) check(codes[0].rawValue);
}

function onError(err: any) {
  cameraError.value =
    err.name === "NotAllowedError"
      ? "Camera permission denied. Please allow the camera."
      : `Camera error: ${err.message}`;
}

// "Allow camera" button → ask the browser for the camera again
function retryCamera() {
  cameraError.value = "";
  cameraKey.value++;
}

function next() {
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

      <div v-if="result" :class="['box', result.result]">
        {{ result.message }}
        <div
          v-if="result.registration"
          style="font-size: 14px; font-weight: 400"
        >
          {{ result.registration.fullname }} · {{ result.registration.email }} ·
          {{ result.registration.phone }}
        </div>
      </div>
      <button v-if="result" @click="next">Scan next</button>
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
