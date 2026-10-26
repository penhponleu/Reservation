export default defineNuxtConfig({
  compatibilityDate: "2025-07-15",
  devtools: { enabled: true },
  ssr: false, // runs in the browser only (camera needs it)
  devServer: { host: "localhost", port: 3000 }, // print/serve http://localhost:3000 instead of [::1]
  css: ["~/assets/css/main.css"],
  runtimeConfig: {
    public: {
      // Laravel backend: local on `npm run dev`, Dokploy on build (NUXT_PUBLIC_API_BASE overrides both)
      apiBase:
        process.env.NODE_ENV === "production"
          ? "https://reservation.devams.site/api"
          : "http://127.0.0.1:8000/api",
    },
  },
  app: {
    head: { title: "Event Reservation" },
  },
});
