<script setup lang="ts">
import { QrcodeStream } from "vue-qrcode-reader";

definePageMeta({ middleware: "auth" });

const api = useApi();
const paused = ref(false);
const result = ref<any>(null);
const manual = ref("");
const cameraError = ref("");
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

function next() {
  result.value = null;
  manual.value = "";
  paused.value = false;
}

async function loadLogs() {
  logs.value = await api("/checkin-logs");
}

onMounted(loadLogs);
</script>

<template>
  <div>
    <div class="card narrow">
      <h1>Scan QR Code</h1>

      <QrcodeStream :paused="paused" @detect="onDetect" @error="onError" />
      <p v-if="cameraError" class="error">{{ cameraError }}</p>

      <div v-if="result" :class="['box', result.result]">
        {{ result.message }}
        <div
          v-if="result.registration"
          style="font-size: 14px; font-weight: 400"
        >
          {{ result.registration.username }} · {{ result.registration.email }} · {{ result.registration.phone }}
        </div>
      </div>
      <button v-if="result" @click="next">Scan next</button>

      <form style="margin-top: 16px" @submit.prevent="check(manual)">
        <label>Or type the code manually</label>
        <input v-model="manual" placeholder="QR code value" />
        <button class="light">Check in</button>
      </form>
    </div>

    <div class="card table-wrap">
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
            <td>{{ l.registration ? `${l.registration.username} (${l.registration.email})` : l.scanned_value }}</td>
            <td>{{ l.result }}</td>
            <td>{{ l.user?.username }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
