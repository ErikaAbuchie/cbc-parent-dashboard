import { parentProxyHeaders } from '../../utils/parentProxyHeaders'

export default defineEventHandler(async (event) => {
  setResponseHeader(event, 'Cache-Control', 'no-store')
  const body = await readBody(event)
  try {
    const result = await $fetch<{ token: string; expires_at: string }>(`${useRuntimeConfig(event).apiBase}/parent/verify`, {
      method: 'POST', headers: parentProxyHeaders(event),
      body: { admission_number: body?.admission_number, code: body?.code }, timeout: 10000
    })
    setCookie(event, 'parent_session', result.token, {
      httpOnly: true, sameSite: 'strict', secure: getRequestURL(event).protocol === 'https:', path: '/',
      maxAge: Math.max(0, Math.floor((Date.parse(result.expires_at) - Date.now()) / 1000))
    })
    return { message: 'Access verified.' }
  } catch (error: any) {
    deleteCookie(event, 'parent_session', { path: '/' })
    const status = error.response?.status
    throw createError({ statusCode: [401, 429].includes(status) ? status : 502,
      statusMessage: status === 401 ? 'The admission number or access code is invalid or expired.'
        : status === 429 ? 'Too many attempts. Please wait ten minutes before trying again.'
        : 'Cannot reach the report service. Please try again.' })
  }
})
