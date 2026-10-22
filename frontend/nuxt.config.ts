export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  ssr: false, // runs in the browser only (camera needs it)
  css: ['~/assets/css/main.css'],
  runtimeConfig: {
    public: {
      // Laravel backend: local on `npm run dev`, Vercel on build (NUXT_PUBLIC_API_BASE overrides both)
      apiBase: process.env.NODE_ENV === 'production'
        ? 'https://reservation-msbe.vercel.app/api'
        : 'http://127.0.0.1:8000/api',
    },
  },
  app: {
    head: { title: 'Event Reservation' },
  },
})
