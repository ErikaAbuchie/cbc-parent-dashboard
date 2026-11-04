export default defineEventHandler(async (event) => {
  const path = getRouterParam(event, 'path') || ''
  const method = getMethod(event)
  const allowedGet = path === 'classes' || /^classes\/\d+\/(assessment-options|assessments|students|practice-exercises)$/.test(path) || /^classes\/\d+\/assessments\/\d+\/history$/.test(path)
  const allowedPost = /^classes\/\d+\/(assessments|students|report-readiness|practice-exercises)$/.test(path) || /^classes\/\d+\/students\/\d+\/(restore|finalise-report)$/.test(path) || /^classes\/\d+\/practice-exercises\/\d+\/(publish|archive|duplicate)$/.test(path)
  const allowedEdit = /^classes\/\d+\/(students|practice-exercises)\/\d+$/.test(path)
  if (!((method === 'GET' && allowedGet) || (method === 'POST' && allowedPost) || (['PATCH', 'DELETE'].includes(method) && allowedEdit))) {
    throw createError({ statusCode: 404, statusMessage: 'Endpoint not found.' })
  }
  const token = getCookie(event, 'teacher_token')
  if (!token) throw createError({ statusCode: 401, statusMessage: 'Please sign in.' })
  try {
    return await $fetch(`${useRuntimeConfig(event).apiBase}/teacher/${path}`, {
      method: method as 'GET' | 'POST' | 'PATCH' | 'DELETE',
      query: method === 'GET' ? getQuery(event) : undefined,
      body: ['POST', 'PATCH'].includes(method) ? await readBody(event) : undefined,
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }, timeout: 15000
    })
  } catch (error: any) {
    const status = error.response?.status
    if (status === 401) deleteCookie(event, 'teacher_token', { path: '/' })
    const errors = error.data?.errors
    throw createError({
      statusCode: [401, 403, 404, 409, 422, 429, 503].includes(status) ? status : 502,
      statusMessage: status === 401 ? 'Your session has ended. Please sign in again.'
        : status === 403 ? 'A teacher account linked to a school is required.'
        : status === 404 ? 'This class or student is unavailable, or the class is not assigned to you.'
        : status === 409 ? 'This record cannot be changed in its current state. Reload the list; only draft exercises can be edited or deleted.'
        : status === 422 ? 'Check the submitted fields and try again.'
        : status === 429 ? 'Please wait one minute before issuing another code.'
        : status === 503 ? 'SMS is not configured. Configure the sandbox credentials and queue first.'
        : 'Cannot reach the assessment service. Please try again.',
      data: errors ? { errors } : undefined
    })
  }
})
