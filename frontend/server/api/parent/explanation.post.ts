export default defineEventHandler(async (event) => {
  setResponseHeader(event, 'Cache-Control', 'no-store')
  const token = getCookie(event, 'parent_session')
  if (!token) throw createError({ statusCode: 401, statusMessage: 'Please verify your access code.' })
  const body = await readBody(event) || {}
  try {
    return await $fetch(`${useRuntimeConfig(event).apiBase}/parent/explanation`, {
      method: 'POST', body: { mode: body.mode ?? 'summary', subject_id: body.subject_id, strand_id: body.strand_id },
      headers: { Accept: 'application/json', Authorization: `Bearer ${token}` }, timeout: 135000
    })
  } catch (error: any) {
    const status = error.response?.status
    if (status === 401) deleteCookie(event, 'parent_session', { path: '/' })
    throw createError({ statusCode: [401, 404, 422, 429].includes(status) ? status : 502,
      statusMessage: status === 401 ? 'Your session has ended. Please verify your code again.'
        : status === 404 || status === 422 ? 'Choose a subject and strand included in this report.'
        : status === 429 ? 'Please wait a minute, then try again. An explanation may already be in progress.'
        : 'Cannot load the explanation. Your recorded results are still available.' })
  }
})
