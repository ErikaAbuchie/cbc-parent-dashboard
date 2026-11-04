/** Remove seed labels from display text without changing stored records or identifiers. */
export function displayName(value: string | null | undefined): string {
  return (value || '').replace(/\bdemo\b/gi, '').replace(/\s{2,}/g, ' ').trim()
}

export function displayRemark(value: string | null | undefined): string {
  if (!value) return ''
  // Seeded preview remarks are metadata, rather than teacher observations.
  if (/^\[DEMO\]/i.test(value) || /^Simulated assessment for Sprint 2 testing\.?$/i.test(value)) return ''
  return value
}
