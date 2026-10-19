export default defineEventHandler(async (event) => {
  const token = getCookie(event, 'parent_session')
  if (token) {
    try {
      await $fetch(`${useRuntimeConfig(event).apiBase}/parent/logout`, { method: 'POST',
        headers: { Accept: 'application/json', Authorization: `Bearer ${token}` }, timeout: 10000 })
    } catch {
      throw createError({ statusCode: 502, statusMessage: 'Could not close the session. Please try again.' })
    }
  }
  deleteCookie(event, 'parent_session', { path: '/' })
  return { message: 'Session ended.' }
})
