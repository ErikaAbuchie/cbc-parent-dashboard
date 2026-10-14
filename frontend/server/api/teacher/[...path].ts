export default defineEventHandler(async (event) => {
  const path = getRouterParam(event, 'path') || ''
  const method = getMethod(event)
  const allowedGet = path === 'classes' || /^classes\/\d+\/(assessment-options|assessments)$/.test(path)
  const allowedPost = /^classes\/\d+\/assessments$/.test(path)
  if (!((method === 'GET' && allowedGet) || (method === 'POST' && allowedPost))) {
    throw createError({ statusCode: 404, statusMessage: 'Endpoint not found.' })
  }
  const token = getCookie(event, 'teacher_token')
  if (!token) throw createError({ statusCode: 401, statusMessage: 'Please sign in.' })
  try {
    return await $fetch(`${useRuntimeConfig(event).apiBase}/teacher/${path}`, {
      method: method as 'GET' | 'POST',
      query: method === 'GET' ? getQuery(event) : undefined,
      body: method === 'POST' ? await readBody(event) : undefined,
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }, timeout: 15000
    })
  } catch (error: any) {
    const status = error.response?.status
    if (status === 401) deleteCookie(event, 'teacher_token', { path: '/' })
    const errors = error.data?.errors
    throw createError({
      statusCode: [401, 403, 404, 422, 429].includes(status) ? status : 502,
      statusMessage: status === 401 ? 'Your session has ended. Please sign in again.'
        : status === 403 ? 'A teacher account linked to a school is required.'
        : status === 404 ? 'This class is unavailable.'
        : status === 422 ? 'Check the assessment fields and try again.'
        : status === 429 ? 'Too many requests. Please wait and try again.'
        : 'Cannot reach the assessment service. Please try again.',
      data: errors ? { errors } : undefined
    })
  }
})
