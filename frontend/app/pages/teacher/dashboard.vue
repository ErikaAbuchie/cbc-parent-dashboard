<script setup lang="ts">
useHead({ title: 'Teacher dashboard | CBC Parent Insight' })
const { data: teacher, error } = await useFetch('/api/auth/session')
if (error.value?.statusCode === 401) await navigateTo('/teacher/login', { replace: true })
const { data: classes, error: classError } = await useFetch<{ classes: { id: number; name: string; grade_level: number }[] }>('/api/teacher/classes')
const pending = ref(false)
const errorMessage = ref('')
async function logout() {
  pending.value = true
  errorMessage.value = ''
  try {
    await $fetch('/api/auth/logout', { method: 'POST' })
    teacher.value = null
    await navigateTo('/teacher/login', { replace: true })
  } catch (error: any) {
    errorMessage.value = error.data?.statusMessage || 'Unable to sign out. Please try again.'
  } finally { pending.value = false }
}
</script>

<template>
  <main class="teacher-home">
    <template v-if="teacher">
      <section class="welcome-banner"><HubLandscape /><div class="welcome-copy"><span class="eyebrow">A NEW DAY TO MAKE A DIFFERENCE</span><h1>Welcome back,<br>{{ teacher.name }}.</h1><p>Your classroom, connected.<br>Keep every learner's next step in sight.</p><NuxtLink v-if="teacher.school_id" class="hero-link" to="/teacher/assessments">Record an assessment <HubIcon name="arrow" /></NuxtLink></div></section>
      <p v-if="!teacher.school_id" class="error">Your account is awaiting a school assignment. Contact your school administrator before recording assessments.</p>
      <template v-else>
        <div class="dashboard-metrics"><div class="metric-card"><span class="metric-icon"><HubIcon name="learners" /></span><div><small>Your teaching space</small><strong>{{ classError ? 'Unavailable' : classes?.classes.length ?? '—' }}</strong><span>Assigned classes</span></div></div><div class="metric-card"><span class="metric-icon gold"><HubIcon name="assessment" /></span><div><small>Evidence that matters</small><strong>EE · ME · AE · BE</strong><span>Four competency levels</span></div></div><div class="metric-card"><span class="metric-icon blue"><HubIcon name="report" /></span><div><small>Keep families connected</small><strong>Parent reports</strong><span>Secure access by SMS code</span></div></div></div>
        <div class="dashboard-grid"><section class="dashboard-card"><div class="card-heading"><div><span class="eyebrow">YOUR WORKSPACE</span><h2>Make the next step count</h2></div></div><NuxtLink class="action-row" to="/teacher/students"><span class="metric-icon"><HubIcon name="learners" /></span><span><strong>Manage learners</strong><small>Add, update or restore learners in your classes.</small></span><HubIcon name="arrow" /></NuxtLink><NuxtLink class="action-row" to="/teacher/assessments"><span class="metric-icon gold"><HubIcon name="assessment" /></span><span><strong>Record assessments</strong><small>Capture competency ratings and teacher remarks.</small></span><HubIcon name="arrow" /></NuxtLink><NuxtLink class="action-row" to="/teacher/reports"><span class="metric-icon blue"><HubIcon name="report" /></span><span><strong>Share a learner report</strong><small>Check completeness and send a parent access code.</small></span><HubIcon name="arrow" /></NuxtLink></section>
        <section class="dashboard-card"><span class="eyebrow">READY FOR LEARNING</span><h2>Your assigned classes</h2><p class="muted-copy">Assessment entry is available for the classes assigned to your account.</p><p v-if="classError" class="error">{{ classError.data?.statusMessage || 'Cannot load classes.' }}</p><p v-else-if="!classes?.classes.length" class="empty-state">No classes assigned yet. Ask your school administrator to connect your class.</p><ul v-else class="class-list"><li v-for="item in classes.classes" :key="item.id"><span class="class-initial">{{ item.grade_level }}</span><span><strong>{{ item.name }}</strong><small>Grade {{ item.grade_level }}</small></span><HubIcon name="leaf" /></li></ul><div class="workflow-note"><HubIcon name="lock" /><p>Record evidence → finalise the report → connect with parents.</p></div></section></div>
      </template>
      <div class="dashboard-session"><p v-if="errorMessage" class="error" role="alert">{{ errorMessage }}</p><span>Signed in as {{ teacher.name }}</span><button class="secondary" :disabled="pending" @click="logout">{{ pending ? 'Signing out…' : 'Sign out' }}</button></div>
    </template>
    <template v-else><h1>Unable to load your dashboard</h1><p class="error" role="alert">{{ error?.data?.statusMessage || 'Please try again.' }}</p><NuxtLink to="/teacher/login">Return to login</NuxtLink></template>
  </main>
</template>

<style scoped>
.teacher-home { background: transparent; border: 0; box-shadow: none; padding: 0; }
.welcome-banner { position: relative; min-height: 260px; border: 1px solid #d9e9df; border-radius: 20px; overflow: hidden; background: linear-gradient(110deg, #eaf6e9, #e0f2ed); }
.hub-landscape { position: absolute; right: 0; bottom: 0; width: 70%; height: 100%; opacity: .85; }
.welcome-banner:after { content: ''; position: absolute; inset: 0; background: linear-gradient(90deg, #edf8eef5, #edf8eebf 37%, transparent 70%); }
.welcome-copy { position: relative; z-index: 1; padding: 30px; max-width: 600px; }.welcome-copy h1 { margin: 12px 0; font-size: clamp(27px, 2.5vw, 36px); }.welcome-copy p { color: #5a7a70; font-size: 14px; }.hero-link { display: inline-flex; align-items: center; gap: 14px; text-decoration: none; font-weight: 650; font-size: 13px; }.hero-link svg { width: 18px; height: 18px; }
.dashboard-metrics { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin: 20px 0; }.metric-card { display: flex; gap: 14px; align-items: center; padding: 22px 18px; border: 1px solid #deebe5; border-radius: 15px; background: white; }.metric-icon { width: 48px; height: 48px; display: grid; place-items: center; color: #008363; background: #e1f5e9; border-radius: 12px; flex-shrink: 0; }.metric-icon svg { width: 25px; height: 25px; }.metric-icon.gold { color: #97610c; background: #fff0d0; }.metric-icon.blue { color: #26877e; background: #e0f3f0; }.metric-card small { display: block; font-size: 11px; }.metric-card strong { display: block; margin: 7px 0; font-size: clamp(17px, 1.7vw, 23px); letter-spacing: -.5px; }.metric-card span:not(.metric-icon) { font-size: 11px; color: #68847b; }
.dashboard-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 20px; }.dashboard-card { padding: 26px; border: 1px solid #deebe5; background: white; border-radius: 16px; min-width: 0; }.dashboard-card h2 { margin-top: 8px; margin-bottom: 18px; font-size: 20px; }.action-row { display: flex; gap: 14px; align-items: center; padding: 18px 0; text-decoration: none; border-top: 1px solid #e9f1ec; color: #193d38; }.action-row>span:nth-child(2) { flex: 1; }.action-row strong { display: block; font-size: 14px; }.action-row small { display: block; margin-top: 6px; line-height: 1.6; font-size: 12px; }.action-row>svg { width: 18px; height: 18px; flex-shrink: 0; color: #188065; }.muted-copy { color: #6b837e; font-size: 13px; }.class-list { padding: 0; list-style: none; }.class-list li { display: flex; align-items: center; gap: 12px; padding: 15px 0; border-bottom: 1px solid #e8f0eb; }.class-initial { width: 42px; height: 44px; display: grid; place-items: center; border-radius: 10px; background: #edf5e9; font-size: 20px; font-weight: 650; color: #337546; }.class-list small { display: block; margin-top: 5px; font-size: 12px; }.class-list svg { margin-left: auto; width: 20px; height: 20px; color: #58a278; }.workflow-note { display: flex; gap: 10px; align-items: center; background: #f2f8f3; border-radius: 10px; padding: 12px; margin-top: 24px; color: #5c7968; }.workflow-note svg { width: 19px; height: 19px; flex-shrink: 0; }.workflow-note p { margin: 0; font-size: 12px; }.dashboard-session { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; margin-top: 22px; color: #72877f; font-size: 12px; }.secondary { background: white; border: 1px solid #d8e7df; color: #376659; margin: 0; font-size: 12px; }
@media (max-width: 1100px) { .dashboard-metrics { grid-template-columns: 1fr; }.metric-card { padding: 16px; }.metric-card strong { font-size: 21px; }.dashboard-grid { grid-template-columns: 1fr; } }
@media (max-width: 760px) { .teacher-home { padding: 0; border: 0; background: transparent; }.welcome-copy { padding: 24px 20px; }.welcome-copy .eyebrow { font-size: 9px; letter-spacing: 1px; }.welcome-banner { min-height: 264px; }.hub-landscape { width: 100%; opacity: .5; }.welcome-copy h1 { font-size: 29px; }.dashboard-metrics { gap: 10px; margin: 14px 0; }.dashboard-card { padding: 22px 18px; }.metric-card strong { margin: 4px 0; }.dashboard-grid { gap: 14px; } }
</style>
