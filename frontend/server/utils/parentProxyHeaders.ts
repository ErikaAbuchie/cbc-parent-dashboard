import { createHmac } from 'node:crypto'
import type { H3Event } from 'h3'

export function parentProxyHeaders(event: H3Event) {
  const secret = useRuntimeConfig(event).parentProxySecret
  const ip = getRequestIP(event) || '127.0.0.1'
  const timestamp = String(Math.floor(Date.now() / 1000))
  const headers: Record<string, string> = { Accept: 'application/json' }
  if (secret) {
    headers['X-Parent-Client-IP'] = ip
    headers['X-Parent-Timestamp'] = timestamp
    headers['X-Parent-Signature'] = createHmac('sha256', secret).update(`${timestamp}|${ip}`).digest('hex')
  }
  return headers
}
