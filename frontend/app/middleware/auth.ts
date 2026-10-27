// Admin pages: no token → go to login page, then come back here after login
export default defineNuxtRouteMiddleware((to) => {
  const token = useCookie("token");
  if (!token.value) {
    return navigateTo({ path: "/admin/login", query: { redirect: to.fullPath } });
  }
});
