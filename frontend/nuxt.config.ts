export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  ssr: false, // runs in the browser only (camera needs it)
  css: ['~/assets/css/main.css'],
  runtimeConfig: {
    public: {
      apiBase: 'http://127.0.0.1:8000/api', // Laravel backend
    },
  },
  app: {
    head: { title: 'Event Reservation' },
  },
})
