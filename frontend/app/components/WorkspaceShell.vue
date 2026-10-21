<script setup lang="ts">
const route = useRoute()
const mobileOpen = ref(false)
const menuButton = ref<HTMLButtonElement | null>(null)
const drawer = ref<HTMLElement | null>(null)
const teacher = computed(() => route.path.startsWith('/teacher/'))
const links = computed(() => teacher.value ? [
  { to: '/teacher/dashboard', label: 'Dashboard', icon: 'home' },
  { to: '/teacher/students', label: 'Learners', icon: 'learners' },
  { to: '/teacher/assessments', label: 'Assessments', icon: 'assessment' },
  { to: '/teacher/reports', label: 'Reports', icon: 'report' },
] : [
  { to: '/parent/report', label: 'Learner report', icon: 'report' },
  { to: '/parent/practice', label: 'Practice', icon: 'practice' },
])
useHead({ bodyAttrs: { class: computed(() => mobileOpen.value ? 'navigation-open' : '') } })
const title = computed(() => links.value.find(link => link.to === route.path)?.label || 'Workspace')
watch(() => route.path, () => { mobileOpen.value = false })
watch(mobileOpen, async (open) => {
  await nextTick()
  if (open) drawer.value?.querySelector<HTMLButtonElement>('button')?.focus()
})
function closeMenu() { mobileOpen.value = false; menuButton.value?.focus() }
function trapKeys(event: KeyboardEvent) {
  if (!mobileOpen.value) return
  if (event.key === 'Escape') { event.preventDefault(); closeMenu(); return }
  if (event.key !== 'Tab') return
  const controls = drawer.value?.querySelectorAll<HTMLElement>('a, button')
  if (!controls?.length) return
  const first = controls[0]; const last = controls[controls.length - 1]
  if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus() }
  else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus() }
}
</script>

<template>
  <div class="workspace" @keydown="trapKeys">
    <a class="skip-link" href="#workspace-content">Skip to content</a>
    <button v-if="mobileOpen" class="drawer-backdrop" aria-label="Close navigation" tabindex="-1" @click="closeMenu" />
    <aside id="workspace-nav" ref="drawer" class="sidebar" :class="{ 'is-open': mobileOpen }" :role="mobileOpen ? 'dialog' : undefined" :aria-modal="mobileOpen ? true : undefined" aria-label="Workspace navigation">
      <button class="drawer-close icon-button" aria-label="Close navigation" @click="closeMenu"><HubIcon name="close" /></button>
      <NuxtLink class="brand-link" :to="teacher ? '/teacher/dashboard' : '/parent/report'"><HubBrand /></NuxtLink>
      <p class="nav-caption">{{ teacher ? 'TEACHER WORKSPACE' : 'PARENT WORKSPACE' }}</p>
      <nav aria-label="Main navigation"><NuxtLink v-for="link in links" :key="link.to" :to="link.to" :class="{ active: route.path === link.to }" :aria-current="route.path === link.to ? 'page' : undefined"><HubIcon :name="link.icon" />{{ link.label }}<span v-if="route.path === link.to" class="active-dot" /></NuxtLink></nav>
      <div class="sidebar-note"><HubIcon name="leaf" /><strong>Small steps.<br>Growing possibilities.</strong><p>Supporting every learner, together.</p></div>
      <div class="workspace-role"><span class="role-avatar"><HubIcon :name="teacher ? 'assessment' : 'learners'" /></span><span><strong>{{ teacher ? 'Teacher portal' : 'Parent portal' }}</strong><small>CBC Parent Insight</small></span></div>
    </aside>
    <div class="workspace-body" :inert="mobileOpen">
      <header class="workspace-header"><button ref="menuButton" class="icon-button menu-toggle" aria-label="Open navigation" :aria-expanded="mobileOpen" aria-controls="workspace-nav" @click="mobileOpen = true"><HubIcon name="menu" /></button><div class="header-label"><span>{{ teacher ? 'Teacher workspace' : 'Family workspace' }}</span><strong>{{ title }}</strong></div><span class="header-badge"><span />{{ teacher ? 'Learning, connected' : 'Every step matters' }}</span><span class="header-emblem"><HubIcon name="leaf" /></span></header>
      <div id="workspace-content" class="workspace-content" tabindex="-1"><slot /></div>
      <footer class="workspace-footer">CBC Parent Insight <span>Supporting learning. Strengthening connection.</span></footer>
    </div>
    <nav :inert="mobileOpen" class="mobile-bottom-nav" aria-label="Quick navigation"><NuxtLink v-for="link in links" :key="link.to" :to="link.to" :class="{ active: route.path === link.to }" :aria-current="route.path === link.to ? 'page' : undefined"><HubIcon :name="link.icon" /><span>{{ link.label }}</span></NuxtLink></nav>
  </div>
</template>
