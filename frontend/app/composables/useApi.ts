// One helper for all API calls: base URL + admin token
export function useApi() {
  const config = useRuntimeConfig();
  const token = useCookie<string | null>("token");

  return <T = any>(url: string, opts: Record<string, any> = {}) =>
    $fetch<T>(url, {
      baseURL: config.public.apiBase as string,
      ...opts,
      headers: {
        Accept: "application/json", // Laravel answers errors as JSON
        ...(token.value ? { Authorization: `Bearer ${token.value}` } : {}),
      },
    });
}
