export default defineEventHandler(async (event) => {
  const token = getCookie(event, 'teacher_token')
  if (!token) throw createError({ statusCode: 401, statusMessage: 'Please sign in.' })
  try {
    const user = await $fetch<{ id: number; name: string; email: string; role: string; school_id: number | null }>(`${useRuntimeConfig(event).apiBase}/user`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }, timeout: 10000
    })
    if (user.role !== 'teacher') throw createError({ statusCode: 401 })
    return user
  } catch (error: any) {
    if (error.response?.status === 401 || error.statusCode === 401) {
      deleteCookie(event, 'teacher_token', { path: '/' })
      throw createError({ statusCode: 401, statusMessage: 'Your session has ended. Please sign in again.' })
    }
    throw createError({ statusCode: 502, statusMessage: 'Cannot reach the login service. Please try again.' })
  }
})
