export default defineEventHandler(async (event) => {
  const body = await readBody(event)
  if (typeof body?.email !== 'string' || typeof body?.password !== 'string') {
    throw createError({ statusCode: 422, statusMessage: 'Enter your email and password.' })
  }
  try {
    const result = await $fetch<{ token: string }>(`${useRuntimeConfig(event).apiBase}/teacher/login`, {
      method: 'POST', body: { email: body.email, password: body.password },
      headers: { Accept: 'application/json' }, timeout: 10000
    })
    setCookie(event, 'teacher_token', result.token, {
      httpOnly: true, secure: getRequestURL(event).protocol === 'https:',
      sameSite: 'strict', path: '/', maxAge: 8 * 60 * 60
    })
    return { message: 'Signed in.' }
  } catch (error: any) {
    const status = error.response?.status
    throw createError({ statusCode: [401, 422, 429].includes(status) ? status : 502,
      statusMessage: status === 401 ? 'Email or password is incorrect.'
        : status === 429 ? 'Too many attempts. Please wait one minute and try again.'
        : status === 422 ? 'Enter a valid email and password.'
        : 'Cannot reach the login service. Please try again.' })
  }
})
