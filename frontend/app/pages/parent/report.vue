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

<template><main class="parent-report"><span class="eyebrow">CBC Parent Insight</span><template v-if="report"><h1>{{ report.student.full_name }}</h1><p>{{ report.student.school_name }} · {{ report.student.class_name }}</p><p>Admission: {{ report.student.admission_number }} · {{ report.term }}</p><div class="report-actions"><p><NuxtLink to="/parent/practice">Open learner practice</NuxtLink></p><button type="button" :disabled="busy" @click="printReport">Print / Save as PDF</button><p>In the print window, choose your printer or Save as PDF. Turn off browser headers and footers for a cleaner copy.</p></div><ParentCompetencySummary :assessments="report.assessments" /><ParentReportExplanation v-if="report.assessments.length" /><h2>Competency results</h2><p>These are the assessed sub-strands included by the teacher when this report was finalised.</p><article v-for="(assessment, index) in report.assessments" :key="index"><h3>{{ assessment.learning_area }} · {{ assessment.sub_strand }}</h3><p>{{ assessment.strand }}</p><p><strong>{{ assessment.competency_level }} — {{ levels[assessment.competency_level] }}</strong></p><p v-if="assessment.remarks">Teacher's remark: {{ assessment.remarks }}</p></article><p v-if="!report.assessments.length">No results are available for this report.</p><p v-if="errorMessage" class="error report-actions" role="alert">{{ errorMessage }}</p><button class="report-actions" :disabled="busy" @click="closeReport">{{ busy ? 'Closing…' : 'Close report' }}</button><p class="printed-note">This copy shows the results displayed when printed. Later teacher updates will appear when the online report is reloaded.</p></template><template v-else><h1>Unable to load report</h1><p class="error" role="alert">{{ error?.data?.statusMessage || 'Please try again.' }}</p><button @click="refresh()">Retry</button><p><NuxtLink to="/parent/login">Verify access again</NuxtLink></p></template></main></template>

<style scoped>
.parent-report { max-width: 800px; }
article { border-top: 1px solid #dce6e2; padding: 12px 0; overflow-wrap: anywhere; }
.report-actions p { font-size: 14px; }
.printed-note { display: none; }
@media print {
  .parent-report { max-width: none; width: 100%; margin: 0; padding: 0; border: 0; border-radius: 0; color: #000; }
  .report-actions { display: none !important; }
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
