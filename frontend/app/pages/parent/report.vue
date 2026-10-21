<script setup lang="ts">
useHead({ title: 'Learner report | CBC Parent Insight' })
const { data: report, error, refresh } = await useFetch('/api/parent/report')
if (error.value?.statusCode === 401) await navigateTo('/parent/login', { replace: true })
const busy = ref(false)
const errorMessage = ref('')
function printReport() {
  if (report.value && !busy.value) window.print()
}
const levels: Record<string, string> = { EE: 'Exceeding Expectations', ME: 'Meeting Expectations', AE: 'Approaching Expectations', BE: 'Below Expectations' }
async function closeReport() {
  busy.value = true; errorMessage.value = ''
  try { await $fetch('/api/parent/logout', { method: 'POST' }); report.value = null; await navigateTo('/parent/login', { replace: true }) }
  catch (error: any) { errorMessage.value = error.data?.statusMessage || 'Could not close the report. Please try again.' }
  finally { busy.value = false }
}
</script>

<template>
  <main class="parent-report">
    <template v-if="report">
      <section class="learner-banner"><div><span class="eyebrow">YOUR LEARNER'S JOURNEY</span><h1>{{ report.student.full_name }}</h1><p>{{ report.student.school_name }} · {{ report.student.class_name }}</p><span class="term-badge">{{ report.term }}</span><span class="admission-label">Admission {{ report.student.admission_number }}</span></div><span class="learner-emblem"><HubIcon name="leaf" /></span></section>
      <div class="report-actions report-toolbar"><NuxtLink class="practice-link" to="/parent/practice"><HubIcon name="practice" />Open learner practice</NuxtLink><button type="button" :disabled="busy" @click="printReport"><HubIcon name="report" />Print / Save as PDF</button></div>
      <p class="report-actions print-help">In the print window, choose your printer or Save as PDF. Turn off browser headers and footers for a cleaner copy.</p>
      <ParentCompetencySummary :assessments="report.assessments" />
      <ParentReportCharts :subjects="report.subjects" :trend="report.trend" />
      <ParentSubjectResults :subjects="report.subjects" :method="report.summary_method" :demo="report.has_demo_results" />
      <ParentReportExplanation v-if="report.assessments.length" :subjects="report.subjects" />
      <ParentPracticeResults />
      <div class="report-actions close-report-row"><span>Your report is available only through verified parent access.</span><button class="secondary" :disabled="busy" @click="closeReport">{{ busy ? 'Closing…' : 'Close report' }}</button></div><p v-if="errorMessage" class="error report-actions" role="alert">{{ errorMessage }}</p>
      <p class="printed-note">This copy shows the results displayed when printed. Later teacher updates will appear when the online report is reloaded.</p>
    </template>
    <template v-else><h1>Unable to load report</h1><p class="error" role="alert">{{ error?.data?.statusMessage || 'Please try again.' }}</p><button @click="refresh()">Retry</button><p><NuxtLink to="/parent/login">Verify access again</NuxtLink></p></template>
  </main>
</template>

<style scoped>
.parent-report { max-width: 1000px; }
.learner-banner { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 26px; border-radius: 15px; background: linear-gradient(110deg, #eaf7ed, #e4f3ef); }.learner-banner h1 { margin-top: 12px; }.learner-banner p { color: #607d72; }.learner-emblem { display: grid; place-items: center; width: 84px; height: 84px; border-radius: 50%; background: #cdebd8; color: #19946b; flex-shrink: 0; }.learner-emblem svg { width: 44px; height: 44px; }.term-badge { display: inline-block; border-radius: 6px; background: #fff; color: #317357; font-size: 12px; font-weight: 650; padding: 7px 10px; }.admission-label { display: inline-block; font-size: 12px; color: #6c857a; margin-left: 12px; }.report-toolbar { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; margin-top: 20px; }.report-toolbar button { display: inline-flex; gap: 8px; align-items: center; margin: 0; font-size: 13px; }.report-toolbar svg { width: 18px; height: 18px; }.practice-link { display: inline-flex; align-items: center; gap: 8px; font-weight: 600; font-size: 13px; padding: 12px 0; }.print-help { color: #6e837c; font-size: 12px; }.result-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }.result-card { border: 1px solid #deebe5; border-radius: 12px; padding: 20px; }.subject-label { font-size: 11px; font-weight: 700; color: #387a60; text-transform: uppercase; letter-spacing: .8px; }.result-card h3 { margin: 10px 0 6px; }.strand-label { color: #688178; font-size: 12px; margin: 5px 0; }.rating-pill { font-size: 12px; display: inline-flex; align-items: center; gap: 9px; padding: 8px 10px; border-radius: 7px; background: #e9f6ed; color: #276c4e; }.rating-ae { background: #fff3da; color: #865f12; }.rating-be { background: #fceceb; color: #a3433f; }.rating-me { background: #e7f3f5; color: #326f7c; }.teacher-remark { padding-top: 12px; border-top: 1px solid #e6eee8; font-size: 14px; color: #4f7063; }.close-report-row { display: flex; gap: 14px; flex-wrap: wrap; justify-content: space-between; align-items: center; font-size: 12px; color: #71897e; }.close-report-row button { margin: 0; }.secondary { background: #eaf3ed; color: #285b47; }
@media (max-width: 600px) { .learner-banner { padding: 20px 16px; }.learner-emblem { display: none; }.admission-label { display: block; margin: 10px 0 0; }.result-grid { grid-template-columns: 1fr; }.result-card { padding: 17px; }.report-toolbar { align-items: stretch; }.report-toolbar button { justify-content: center; }.learner-banner .eyebrow { font-size: 10px; } }

article { border-top: 1px solid #dce6e2; padding: 12px 0; overflow-wrap: anywhere; }
.report-actions p { font-size: 14px; }
.printed-note { display: none; }
@media print {
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
