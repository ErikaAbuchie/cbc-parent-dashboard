export default defineEventHandler(async (event) => {
  const token = getCookie(event, 'teacher_token')
  if (token) {
    try {
      await $fetch(`${useRuntimeConfig(event).apiBase}/teacher/logout`, {
        method: 'POST', headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }, timeout: 10000
      })
    } catch (error: any) {
      if (error.response?.status !== 401) throw createError({ statusCode: 502, statusMessage: 'Could not sign out. Please try again.' })
    }
  }
  deleteCookie(event, 'teacher_token', { path: '/' })
  return { message: 'Signed out.' }
})
