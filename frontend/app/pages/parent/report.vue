<script setup lang="ts">
useHead({ title: 'Learner report | CBC Parent Insight' })
const { data: report, error, refresh } = await useFetch('/api/parent/report')
if (error.value?.statusCode === 401) await navigateTo('/parent/login', { replace: true })
const busy = ref(false)
const errorMessage = ref('')
const levels: Record<string, string> = { EE: 'Exceeding Expectations', ME: 'Meeting Expectations', AE: 'Approaching Expectations', BE: 'Below Expectations' }
async function closeReport() {
  busy.value = true; errorMessage.value = ''
  try { await $fetch('/api/parent/logout', { method: 'POST' }); report.value = null; await navigateTo('/parent/login', { replace: true }) }
  catch (error: any) { errorMessage.value = error.data?.statusMessage || 'Could not close the report. Please try again.' }
  finally { busy.value = false }
}
</script>

<template><main class="parent-report"><span class="eyebrow">CBC Parent Insight</span><template v-if="report"><h1>{{ report.student.full_name }}</h1><p>{{ report.student.school_name }} · {{ report.student.class_name }}</p><p>Admission: {{ report.student.admission_number }} · {{ report.term }}</p><h2>Competency results</h2><p>These are the assessed sub-strands included by the teacher when this report was finalised.</p><article v-for="(assessment, index) in report.assessments" :key="index"><h3>{{ assessment.learning_area }} · {{ assessment.sub_strand }}</h3><p>{{ assessment.strand }}</p><p><strong>{{ assessment.competency_level }} — {{ levels[assessment.competency_level] }}</strong></p><p v-if="assessment.remarks">Teacher's remark: {{ assessment.remarks }}</p></article><p v-if="!report.assessments.length">No results are available for this report.</p><p v-if="errorMessage" class="error" role="alert">{{ errorMessage }}</p><button :disabled="busy" @click="closeReport">{{ busy ? 'Closing…' : 'Close report' }}</button></template><template v-else><h1>Unable to load report</h1><p class="error" role="alert">{{ error?.data?.statusMessage || 'Please try again.' }}</p><button @click="refresh()">Retry</button><p><NuxtLink to="/parent/login">Verify access again</NuxtLink></p></template></main></template>

<style scoped>.parent-report { max-width: 800px; } article { border-top: 1px solid #dce6e2; padding: 12px 0; }</style>
