<script setup lang="ts">
// Opened from the email link → show the guest's QR code big, ready to scan at the door
const route = useRoute();
const config = useRuntimeConfig();
const src = `${config.public.apiBase}/qr/${encodeURIComponent(String(route.params.token))}`;
const failed = ref(false);
</script>

<template>
  <div class="card narrow qr-page">
    <h1>Your QR code</h1>
    <p v-if="failed" class="error">This QR code was not found.</p>
    <template v-else>
      <p>Show this QR code at the door to check in.</p>
      <img :src="src" alt="Your QR code" @error="failed = true" />
      <a :href="src" download="my-qr-code.png"><button type="button">Download QR</button></a>
    </template>
  </div>
</template>

<style scoped>
.qr-page { text-align: center; }
.qr-page p { color: var(--muted); }
.qr-page img { display: block; width: 100%; max-width: 320px; margin: 16px auto 0; border: 1px solid var(--border); border-radius: 8px; }
</style>
