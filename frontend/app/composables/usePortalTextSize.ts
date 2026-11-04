export type PortalTextSize = 'standard' | 'large' | 'extra-large'

export function usePortalTextSize() {
  const route = useRoute()
  const parent = computed(() => route.path.startsWith('/parent/'))
  const cookieOptions = { default: () => 'standard', maxAge: 60 * 60 * 24 * 365, sameSite: 'lax' as const, path: '/' }
  const teacherSize = useCookie<string>('cbc_teacher_text_size', cookieOptions)
  const parentSize = useCookie<string>('cbc_parent_text_size', cookieOptions)
  const sizes: PortalTextSize[] = ['standard', 'large', 'extra-large']
  const size = computed<PortalTextSize>({
    get() {
      const saved = parent.value ? parentSize.value : teacherSize.value
      return sizes.includes(saved as PortalTextSize) ? saved as PortalTextSize : 'standard'
    },
    set(value) {
      if (!sizes.includes(value)) return
      if (parent.value) parentSize.value = value
      else teacherSize.value = value
    },
  })
  const scale = computed(() => ({ standard: 1, large: 1.125, 'extra-large': 1.25 })[size.value])
  return { size, scale }
}
