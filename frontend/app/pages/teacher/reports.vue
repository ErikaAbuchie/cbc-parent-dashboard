<script setup lang="ts">
type Sub = { id: number; name: string }
type Area = { id: number; name: string; strands: { id: number; name: string; sub_strands: Sub[] }[] }
type ReadyStudent = { id: number; full_name: string; admission_number: string; outstanding: Sub[]; phone_ready: boolean; sms_status: string | null; code_active: boolean }
useHead({ title: 'Finalise reports | CBC Parent Insight' })
const { data: teacher, error: sessionError } = await useFetch('/api/auth/session')
if (sessionError.value?.statusCode === 401) await navigateTo('/teacher/login', { replace: true })
const { data: classData, error: classError } = await useFetch<{ classes: { id: number; name: string }[] }>('/api/teacher/classes')
const classId = ref('')
const areas = ref<Area[]>([])
const selected = ref<number[]>([])
const termNumber = ref('1')
const year = ref(new Date().getFullYear())
const term = computed(() => `Term ${termNumber.value} ${year.value}`)
const learners = ref<ReadyStudent[]>([])
const checked = ref(false)
const smsConfigured = ref(false)
const busy = ref(false)
const errorMessage = ref('')
const fieldErrors = ref<string[]>([])
const message = ref('')
const confirming = ref<ReadyStudent | null>(null)
const statuses: Record<string, string> = { queued: 'Queued for SMS', accepted: 'Accepted by SMS provider; delivery unconfirmed', retrying: 'SMS retry pending', failed: 'SMS failed; code deactivated', cancelled: 'SMS cancelled', not_queued: 'No SMS queued' }
watch([classId, termNumber, year, selected], () => { learners.value = []; checked.value = false; confirming.value = null; message.value = ''; errorMessage.value = ''; fieldErrors.value = [] }, { deep: true })
async function handleError(error: any) {
  if (error.statusCode === 401 || error.response?.status === 401) { await navigateTo('/teacher/login', { replace: true }); return }
  errorMessage.value = error.data?.statusMessage || 'The request failed. Please try again.'
  fieldErrors.value = Object.values(error.data?.data?.errors || {}).flat() as string[]
}
async function loadCurriculum() {
  areas.value = []; selected.value = []
  if (!classId.value) return
  busy.value = true
  try {
    const options = await $fetch<{ learning_areas: Area[] }>(`/api/teacher/classes/${classId.value}/assessment-options`)
    areas.value = options.learning_areas
  } catch (error) { await handleError(error) }
  finally { busy.value = false }
}
async function fetchReadiness() {
  const result = await $fetch<{ students: ReadyStudent[]; sms_configured: boolean }>(`/api/teacher/classes/${classId.value}/report-readiness`, {
    method: 'POST', body: { term: term.value, sub_strand_ids: selected.value }
  })
  learners.value = result.students; smsConfigured.value = result.sms_configured; checked.value = true
}
async function checkReadiness() {
  busy.value = true; errorMessage.value = ''; fieldErrors.value = []; confirming.value = null
  try { await fetchReadiness() } catch (error) { await handleError(error) }
  finally { busy.value = false }
}
async function finalise() {
  if (!confirming.value || busy.value) return
  const student = confirming.value
  busy.value = true; errorMessage.value = ''; fieldErrors.value = []; message.value = ''
  try {
    const result = await $fetch<{ message: string }>(`/api/teacher/classes/${classId.value}/students/${student.id}/finalise-report`, {
      method: 'POST', body: { term: term.value, sub_strand_ids: selected.value }
    })
    confirming.value = null
    message.value = `${student.full_name}: ${result.message}`
    await fetchReadiness()
  } catch (error) { await handleError(error) }
  finally { busy.value = false }
}
</script>

<template><main class="reports-page"><NuxtLink to="/teacher/dashboard">← Teacher dashboard</NuxtLink><h1>Finalise term reports</h1><p>Select the sub-strands required for this term. Each learner must have an assessment from you for every selected sub-strand before a code can be issued.</p>
  <p v-if="classError || sessionError" class="error" role="alert">{{ classError?.data?.statusMessage || sessionError?.data?.statusMessage }}</p>
  <template v-else-if="teacher">
    <form @submit.prevent="checkReadiness"><fieldset :disabled="busy || !!confirming"><legend>Term requirements</legend>
      <label for="report-class">Assigned class</label><select id="report-class" v-model="classId" required @change="loadCurriculum"><option value="">Select class</option><option v-for="item in classData?.classes" :key="item.id" :value="String(item.id)">{{ item.name }}</option></select>
      <p v-if="!classData?.classes.length">No classes are assigned to you.</p>
      <label for="report-term">Term</label><select id="report-term" v-model="termNumber"><option value="1">Term 1</option><option value="2">Term 2</option><option value="3">Term 3</option></select>
      <label for="report-year">Year</label><input id="report-year" v-model.number="year" type="number" min="2000" max="2099" required>
      <div v-for="area in areas" :key="area.id"><h2>{{ area.name }}</h2><div v-for="strand in area.strands" :key="strand.id"><h3>{{ strand.name }}</h3><label v-for="sub in strand.sub_strands" :key="sub.id" class="sub-option"><input v-model="selected" type="checkbox" :value="sub.id">{{ sub.name }}</label></div></div>
      <p v-if="classId && !areas.length && !busy">No curriculum is available for this class.</p>
      <p>{{ selected.length }} sub-strands selected.</p><button :disabled="!selected.length">Check completeness and SMS status</button>
    </fieldset></form>
    <p v-if="busy" role="status">Working…</p><div v-if="errorMessage" class="error" role="alert">{{ errorMessage }}<p v-for="(item, i) in fieldErrors" :key="i">{{ item }}</p></div><p v-if="message" class="success" role="status">{{ message }}</p>
    <section v-if="confirming" class="confirmation" aria-labelledby="send-heading"><h2 id="send-heading">Finalise and send a parent code?</h2><p>{{ confirming.full_name }} · {{ term }} · {{ selected.length }} selected sub-strands.</p><p>A new code will be sent to the registered parent number. It replaces any earlier code for this learner and term. The code will not be displayed to you.</p><button :disabled="busy" @click="finalise">Confirm finalisation and SMS</button><button class="secondary" :disabled="busy" @click="confirming = null">Cancel</button></section>
    <template v-if="checked"><h2>{{ term }} readiness</h2><p v-if="!smsConfigured" class="error">SMS is not configured yet. Set the sandbox credentials, enable SMS, and use a database or Redis queue before finalising.</p><p v-if="!learners.length">No active learners in this class.</p><article v-for="student in learners" :key="student.id"><h3>{{ student.full_name }} · {{ student.admission_number }}</h3><p v-if="student.outstanding.length">Missing assessments: {{ student.outstanding.map(sub => sub.name).join(', ') }}</p><p v-else>All selected sub-strands have assessments.</p><p v-if="!student.phone_ready">Add a valid parent number in <NuxtLink to="/teacher/students">Manage students</NuxtLink>.</p><p v-if="student.sms_status">{{ statuses[student.sms_status] || student.sms_status }}. {{ student.code_active ? 'Code active.' : 'No active code.' }}</p><button :disabled="busy || !!confirming || !smsConfigured || !student.phone_ready || !!student.outstanding.length" @click="confirming = student">{{ student.code_active ? 'Issue replacement code' : 'Finalise and send code' }}</button></article></template>
  </template>
</main></template>

<style scoped>.reports-page { max-width: 900px; }fieldset { border: 1px solid #dce6e2; border-radius: 8px; padding: 20px; }legend { font-weight: bold; }select { width: 100%; padding: 12px; font: inherit; border: 1px solid #9cafa7; border-radius: 6px; background: white; }select:focus-visible { outline: 3px solid #e7b64d; outline-offset: 3px; }.sub-option { display: inline-flex; align-items: center; gap: 8px; margin: 6px 20px 6px 0; font-weight: normal; }.sub-option input { width: auto; }.success { padding: 12px; background: #e4f3e9; }.confirmation { border: 2px solid #176349; border-radius: 8px; padding: 20px; margin: 20px 0; }.secondary { margin-left: 12px; background: #e5ece9; color: #18342d; }article { border-top: 1px solid #dce6e2; padding: 12px 0; }</style>
