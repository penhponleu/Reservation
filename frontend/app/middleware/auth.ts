// Admin pages: no token → go to login page
export default defineNuxtRouteMiddleware(() => {
  const token = useCookie("token");
  if (!token.value) {
    return navigateTo("/admin/login");
  }
});
