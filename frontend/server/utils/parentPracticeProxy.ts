import type { H3Event } from 'h3'

export async function parentPracticeProxy(event: H3Event, path = '') {
  setResponseHeader(event, 'Cache-Control', 'no-store')
  const method = getMethod(event)
  if (!((!path && ['GET', 'POST'].includes(method)) || (method === 'GET' && /^\d+$/.test(path)) || (method === 'POST' && /^\d+\/submit$/.test(path)))) {
    throw createError({ statusCode: 404, statusMessage: 'Practice endpoint not found.' })
  }
  const token = getCookie(event, 'parent_session')
  if (!token) throw createError({ statusCode: 401, statusMessage: 'Please verify your access code.' })
  try {
    return await $fetch(`${useRuntimeConfig(event).apiBase}/parent/practice${path ? `/${path}` : ''}`, {
      method: method as 'GET' | 'POST', body: method === 'POST' ? await readBody(event) : undefined,
      headers: { Accept: 'application/json', Authorization: `Bearer ${token}` }, timeout: 10000
    })
  } catch (error: any) {
    const status = error.response?.status
    if (status === 401) deleteCookie(event, 'parent_session', { path: '/' })
    throw createError({ statusCode: [401, 404, 422, 429].includes(status) ? status : 502,
      statusMessage: status === 401 ? 'Your session has ended. Please verify your code again.'
        : status === 404 ? 'This practice topic or attempt is unavailable for your report.'
        : status === 422 ? 'Select one answer for every question, then try again.'
        : status === 429 ? 'Please wait ten minutes before starting more practice attempts.'
        : 'Cannot load practice. Please try again.' })
  }
}
