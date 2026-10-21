export default defineEventHandler(async (event) => {
  setResponseHeader(event, 'Cache-Control', 'no-store')
  const token = getCookie(event, 'parent_session')
  if (!token) throw createError({ statusCode: 401, statusMessage: 'Please verify your access code.' })
  try {
    return await $fetch(`${useRuntimeConfig(event).apiBase}/parent/explanation`, {
      method: 'POST', headers: { Accept: 'application/json', Authorization: `Bearer ${token}` }, timeout: 55000
    })
  } catch (error: any) {
    const status = error.response?.status
    if (status === 401) deleteCookie(event, 'parent_session', { path: '/' })
    throw createError({ statusCode: status === 401 || status === 429 ? status : 502,
      statusMessage: status === 401 ? 'Your session has ended. Please verify your code again.'
        : status === 429 ? 'Please wait a minute, then try again. An explanation may already be in progress.'
        : 'Cannot load the explanation. Your recorded results are still available.' })
  }
})
