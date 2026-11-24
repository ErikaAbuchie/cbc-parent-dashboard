<script setup lang="ts">
const route = useRoute()
const section = computed(() => ['subjects', 'trends', 'guidance'].includes(String(route.query.section)) ? String(route.query.section) : 'overview')
const sectionNames: Record<string, string> = { overview: 'Overview', subjects: 'Subject results', trends: 'Trends', guidance: 'Guidance' }
useHead({ title: computed(() => `${sectionNames[section.value]} | CBC Parent Insight`) })
const sectionHeading = ref<HTMLElement | null>(null)
watch(section, async () => {
  await nextTick()
  if (route.hash) {
    const target = document.getElementById(route.hash.slice(1))
    if (target instanceof HTMLDetailsElement) target.open = true
    target?.scrollIntoView({ block: 'start', behavior: 'auto' })
  } else {
    sectionHeading.value?.focus({ preventScroll: true })
    sectionHeading.value?.scrollIntoView({ block: 'start', behavior: 'auto' })
  }
})
const selectedReport = ref('')
const reportQuery = computed(() => selectedReport.value ? { report_id: selectedReport.value } : {})
const { data: report, error, refresh, status } = await useFetch('/api/parent/report', { query: reportQuery })
const lastChecked = ref('')
let refreshTimer: ReturnType<typeof setInterval> | undefined
async function refreshProgress() {
  if (document.visibilityState === 'hidden' || status.value === 'pending') return
  await refresh()
  if (!error.value) lastChecked.value = new Date().toLocaleTimeString('en-KE', { hour: '2-digit', minute: '2-digit' })
}
watch(error, async value => { if (value?.statusCode === 401) await navigateTo('/parent/login', { replace: true }) })
onMounted(() => {
  refreshTimer = setInterval(() => { void refreshProgress() }, 30000)
  document.addEventListener('visibilitychange', refreshProgress)
})
onBeforeUnmount(() => {
  if (refreshTimer) clearInterval(refreshTimer)
  document.removeEventListener('visibilitychange', refreshProgress)
})
if (error.value?.statusCode === 401) await navigateTo('/parent/login', { replace: true })
const busy = ref(false)
const errorMessage = ref('')
function printReport() {
  if (report.value && !busy.value) window.print()
}
async function closeReport() {
  busy.value = true; errorMessage.value = ''
  try { await $fetch('/api/parent/logout', { method: 'POST' }); report.value = null; await navigateTo('/parent/login', { replace: true }) }
  catch (error: any) { errorMessage.value = error.data?.statusMessage || 'Could not close the report. Please try again.' }
  finally { busy.value = false }
}
</script>

<template>
  <main class="parent-report parent-dashboard-page" :class="{ 'is-overview': section === 'overview' }">
    <template v-if="report">
      <div v-show="section === 'overview'" class="overview-welcome"><ParentWelcomeBanner :learner-name="displayName(report.student.full_name)" compact /></div>
      <section class="learner-banner"><div><span class="eyebrow">YOUR LEARNER'S JOURNEY</span><h1>{{ displayName(report.student.full_name) }}</h1><p>{{ displayName(report.student.school_name) }} · {{ displayName(report.student.class_name) }}</p><span class="term-badge">{{ report.term }}</span><span class="admission-label">Admission {{ report.student.admission_number }}</span></div><span class="learner-emblem"><HubIcon name="leaf" /></span></section>
      <section class="term-controls report-actions" aria-label="Progress history">
        <div><label for="parent-term">View progress for</label><select id="parent-term" v-model="selectedReport" :disabled="status === 'pending'"><option value="">Latest available term</option><option v-for="item in report.available_terms" :key="item.id" :value="String(item.id)">{{ item.term }}</option></select></div>
        <button type="button" :disabled="status === 'pending'" @click="refreshProgress">{{ status === 'pending' ? 'Checking…' : 'Refresh results' }}</button>
        <p role="status">Checks for new results every 30 seconds.<span v-if="lastChecked"> Last checked {{ lastChecked }}.</span></p>
        <p v-if="error" class="error" role="alert">Could not refresh results. The previous results remain displayed; please retry.</p>
      </section>
      <div class="report-actions report-toolbar"><h2 ref="sectionHeading" class="section-heading" tabindex="-1">{{ sectionNames[section] }}</h2><button v-if="section !== 'overview'" type="button" :disabled="busy" @click="printReport"><HubIcon name="report" />Print / Save full report</button></div>
      <section id="report-overview" v-show="section === 'overview'" class="report-view overview-view" aria-label="Dashboard overview"><ParentDashboardOverview :subjects="report.subjects" :assessments="report.assessments" :trend="report.trend"><template #actions><button type="button" :disabled="busy" @click="printReport">Print / Save full report</button></template></ParentDashboardOverview></section>
      <div class="print-summary"><ParentCompetencySummary :assessments="report.assessments" /></div>
      <section v-show="section === 'subjects'" class="report-view" aria-label="Subject results"><ParentSubjectResults :subjects="report.subjects" :method="report.summary_method" /></section>
      <section v-show="section === 'trends'" class="report-view" aria-label="Subject overview and term trends"><ParentReportCharts :subjects="report.subjects" :trend="report.trend" /></section>
      <section v-show="section === 'guidance'" class="report-view" aria-label="Explanations and home guidance"><ParentReportExplanation v-if="report.assessments.length" :key="`${report.report_id}:${report.last_updated_at}`" :report-id="report.report_id" :subjects="report.subjects" /><p v-else>No assessment results are available for guidance yet.</p></section>
      <section class="print-summary" aria-label="Practice results"><ParentPracticeResults :key="report.report_id" :report-id="report.report_id" /></section>
      <p class="report-actions print-help">Print includes all report sections, whichever view is open. Choose Save as PDF in the print window.</p>
      <div class="report-actions close-report-row"><span>Your report is available only through verified parent access.</span><button class="secondary" :disabled="busy" @click="closeReport">{{ busy ? 'Closing…' : 'Close report' }}</button></div><p v-if="errorMessage" class="error report-actions" role="alert">{{ errorMessage }}</p>
      <p class="printed-note">This copy shows the results displayed when printed. Later teacher updates will appear when the online report is reloaded.</p>
    </template>
    <template v-else><h1>Unable to load report</h1><p class="error" role="alert">{{ error?.data?.statusMessage || 'Please try again.' }}</p><button @click="refresh()">Retry</button><p><NuxtLink to="/parent/login">Verify access again</NuxtLink></p></template>
  </main>
</template>

<style scoped>
.parent-report { max-width: 1000px; }
.term-controls { display: flex; align-items: end; flex-wrap: wrap; gap: 16px; margin: 24px 0; padding: 18px; border: 1px solid #e5dced; border-radius: 12px; }
.term-controls label { display: block; margin-bottom: 8px; font-weight: 650; }
.term-controls select { min-width: 220px; max-width: 100%; padding: 12px; font: inherit; }
.term-controls button { margin: 0; }.term-controls p { flex-basis: 100%; margin: 0; }
.report-overview { scroll-margin-top: 24px; }
.section-heading { margin: 0; scroll-margin-top: 24px; }
.print-summary { display: none; }
.report-toolbar > button { position: relative; inset-block-start: -8px; }
.learner-banner { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 26px; border-radius: 15px; background: linear-gradient(110deg, #f1ecf5, #ece5f2); }.learner-banner h1 { margin-top: 12px; }.learner-banner p { color: #724796; }.learner-emblem { display: grid; place-items: center; width: 84px; height: 84px; border-radius: 50%; background: #ddcfe9; color: #5b2885; flex-shrink: 0; }.learner-emblem svg { width: 44px; height: 44px; }.term-badge { display: inline-block; border-radius: 6px; background: #fff; color: #562b79; font-size: 0.75rem; font-weight: 650; padding: 7px 10px; }.admission-label { display: inline-block; font-size: 0.75rem; color: #7d4fa2; margin-left: 12px; }.report-toolbar { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; margin-top: 20px; }.report-toolbar button { display: inline-flex; gap: 8px; align-items: center; margin: 0; font-size: 0.8125rem; }.report-toolbar svg { width: 18px; height: 18px; }.practice-link { display: inline-flex; align-items: center; gap: 8px; font-weight: 600; font-size: 0.8125rem; padding: 12px 0; }.print-help { color: #7c51a0; font-size: 0.75rem; }.result-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }.result-card { border: 1px solid #e5dced; border-radius: 12px; padding: 20px; }.subject-label { font-size: 0.6875rem; font-weight: 700; color: #5d2e84; text-transform: uppercase; letter-spacing: .8px; }.result-card h3 { margin: 10px 0 6px; }.strand-label { color: #784d9c; font-size: 0.75rem; margin: 5px 0; }.rating-pill { font-size: 0.75rem; display: inline-flex; align-items: center; gap: 9px; padding: 8px 10px; border-radius: 7px; background: #f0ebf4; color: #4d256e; }.rating-ae { background: #fff3da; color: #865f12; }.rating-be { background: #fceceb; color: #a3433f; }.rating-me { background: #efe9f3; color: #326f7c; }.teacher-remark { padding-top: 12px; border-top: 1px solid #ebe4f0; font-size: 0.875rem; color: #633c83; }.close-report-row { display: flex; gap: 14px; flex-wrap: wrap; justify-content: space-between; align-items: center; font-size: 0.75rem; color: #8152a8; }.close-report-row button { margin: 0; }.secondary { background: #efeaf3; color: #44255e; }
@media (max-width: 600px) { .learner-banner { padding: 20px 16px; }.learner-emblem { display: none; }.admission-label { display: block; margin: 10px 0 0; }.result-grid { grid-template-columns: 1fr; }.result-card { padding: 17px; }.report-toolbar { align-items: stretch; }.report-toolbar button { justify-content: center; }.learner-banner .eyebrow { font-size: 0.625rem; } }

article { border-top: 1px solid #e2d8ea; padding: 12px 0; overflow-wrap: anywhere; }
.report-actions p { font-size: 0.875rem; }
.printed-note { display: none; }
@media print {
  .report-view, .print-summary { display: block !important; }
  .overview-view, .overview-welcome { display: none !important; }
  .parent-report { max-width: none; width: 100%; margin: 0; padding: 0; border: 0; border-radius: 0; color: #000; }
  .report-actions { display: none !important; }
  .learner-banner { padding: 0; background: white; }.learner-emblem { display: none; }.result-grid { display: block; }.result-card { margin: 12px 0; }.rating-pill { color: black; background: white; border: 1px solid #aaa; }
  .printed-note { display: block; border-top: 1px solid #999; margin-top: 24px; padding-top: 12px; font-size: 10pt; }
  h1 { font-size: 20pt; }
  h2, h3 { break-after: avoid; }
  article { break-inside: avoid; }
  p { orphans: 3; widows: 3; }
}
</style>
<style>
@media print {
  @page { size: A4; margin: 16mm; }
  body { background: #fff; }
}
</style>
