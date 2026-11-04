export type PortalTheme = 'light' | 'dark'

export function usePortalAppearance() {
  const route = useRoute()
  const parent = computed(() => route.path.startsWith('/parent/'))
  const options = { default: () => 'light', maxAge: 60 * 60 * 24 * 365, sameSite: 'lax' as const, path: '/' }
  const teacherTheme = useCookie<string>('cbc_teacher_theme', options)
  const parentTheme = useCookie<string>('cbc_parent_theme', options)
  const theme = computed<PortalTheme>({
    get: () => (parent.value ? parentTheme.value : teacherTheme.value) === 'dark' ? 'dark' : 'light',
    set(value) {
      if (value !== 'light' && value !== 'dark') return
      if (parent.value) parentTheme.value = value
      else teacherTheme.value = value
    },
  })
  return { theme }
}
