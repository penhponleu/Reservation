<template>
  <!-- No top menu: open pages by URL (/register, /admin, /admin/scan).
       Admin pages use layouts/admin.vue (dark left sidebar), others layouts/default.vue -->
  <NuxtLayout>
    <NuxtPage />
  </NuxtLayout>
</template>
