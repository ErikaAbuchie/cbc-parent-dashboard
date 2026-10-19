<script setup lang="ts">
type SubStrand = { id: number; name: string }
type Strand = { id: number; name: string; sub_strands: SubStrand[] }
type Area = { id: number; name: string; strands: Strand[] }
type Learner = { id: number; full_name: string; admission_number: string }
type Rating = { id: number; student_id: number; competency_level: string; remarks: string | null }
type Revision = { id: number; version: number; event: string; competency_level: string; remarks: string | null; recorded_at: string; user_id: number }
type Row = Learner & { competency_level: string; remarks: string }
useHead({ title: 'Assessment entry | CBC Parent Insight' })
const { data: teacher, error: sessionError } = await useFetch('/api/auth/session')
if (sessionError.value?.statusCode === 401) await navigateTo('/teacher/login', { replace: true })
const { data: classData, error: classError, refresh: refreshClasses } = await useFetch<{ classes: { id: number; name: string; grade_level: number }[] }>('/api/teacher/classes')
const classId = ref('')
const areaId = ref('')
const strandId = ref('')
const subStrandId = ref('')
const termNumber = ref('1')
const year = ref(new Date().getFullYear())
const term = computed(() => `Term ${termNumber.value} ${year.value}`)
const areas = ref<Area[]>([])
const learners = ref<Learner[]>([])
const rows = ref<Row[]>([])
const saved = ref<Rating[]>([])
const history = ref<Revision[]>([])
const historyLearner = ref('')
const baseline = ref('[]')
const dirty = computed(() => JSON.stringify(rows.value) !== baseline.value)
const working = ref(false)
const loaded = ref(false)
const message = ref('')
const errorMessage = ref('')
const fieldErrors = ref<string[]>([])
const strands = computed(() => areas.value.find(area => String(area.id) === areaId.value)?.strands || [])
const subStrands = computed(() => strands.value.find(strand => String(strand.id) === strandId.value)?.sub_strands || [])
const locked = computed(() => working.value || dirty.value)
const levels = [{ code: 'EE', label: 'Exceeding Expectations' }, { code: 'ME', label: 'Meeting Expectations' }, { code: 'AE', label: 'Approaching Expectations' }, { code: 'BE', label: 'Below Expectations' }]

function resetRows() {
  rows.value = []
  saved.value = []
  history.value = []
  historyLearner.value = ''
  baseline.value = '[]'
  loaded.value = false
  message.value = ''
  errorMessage.value = ''
  fieldErrors.value = []
}
watch(areaId, () => { strandId.value = ''; subStrandId.value = ''; resetRows() })
watch(strandId, () => { subStrandId.value = ''; resetRows() })
watch([subStrandId, termNumber, year], resetRows)

async function showError(error: any) {
  if (error.statusCode === 401 || error.response?.status === 401) {
    await navigateTo('/teacher/login', { replace: true })
    return
  }
  errorMessage.value = error.data?.statusMessage || 'The request failed. Please try again.'
  fieldErrors.value = Object.values(error.data?.data?.errors || {}).flat() as string[]
}

async function changeClass() {
  resetRows()
  areas.value = []
  learners.value = []
  areaId.value = ''
  strandId.value = ''
  subStrandId.value = ''
  if (!classId.value) return
  working.value = true
  try {
    const options = await $fetch<{ students: Learner[]; learning_areas: Area[] }>(`/api/teacher/classes/${classId.value}/assessment-options`)
    learners.value = options.students
    areas.value = options.learning_areas
  } catch (error) { await showError(error) }
  finally { working.value = false }
}

function populateRows() {
  rows.value = learners.value.map(learner => {
    const rating = saved.value.find(item => item.student_id === learner.id)
    return { ...learner, competency_level: rating?.competency_level || '', remarks: rating?.remarks || '' }
  })
  baseline.value = JSON.stringify(rows.value)
}

async function loadAssessments() {
  working.value = true
  errorMessage.value = ''
  fieldErrors.value = []
  message.value = ''
  loaded.value = false
  try {
    const result = await $fetch<{ assessments: Rating[] }>(`/api/teacher/classes/${classId.value}/assessments`, { query: { sub_strand_id: subStrandId.value, term: term.value } })
    saved.value = result.assessments
    populateRows()
    loaded.value = true
  } catch (error) { await showError(error) }
  finally { working.value = false }
}

async function saveAssessments() {
  history.value = []
  historyLearner.value = ''
  working.value = true
  errorMessage.value = ''
  fieldErrors.value = []
  message.value = ''
  try {
    const result = await $fetch<{ saved_count: number }>(`/api/teacher/classes/${classId.value}/assessments`, {
      method: 'POST', body: { sub_strand_id: Number(subStrandId.value), term: term.value,
        assessments: rows.value.map(row => ({ student_id: row.id, competency_level: row.competency_level, remarks: row.remarks.trim() || null })) }
    })
    // Preserve the draft until both saving and reloading the persisted records succeed.
    const persisted = await $fetch<{ assessments: Rating[] }>(`/api/teacher/classes/${classId.value}/assessments`, { query: { sub_strand_id: subStrandId.value, term: term.value } })
    saved.value = persisted.assessments
    populateRows()
    message.value = `${result.saved_count} assessments saved and reloaded.`
  } catch (error) { await showError(error) }
  finally { working.value = false }
}

async function viewHistory(rating: Rating) {
  working.value = true
  errorMessage.value = ''
  fieldErrors.value = []
  history.value = []
  historyLearner.value = ''
  try {
    const result = await $fetch<{ revisions: Revision[] }>(`/api/teacher/classes/${classId.value}/assessments/${rating.id}/history`)
    history.value = result.revisions
    historyLearner.value = learners.value.find(learner => learner.id === rating.student_id)?.full_name || 'Learner'
  } catch (error) { await showError(error) }
  finally { working.value = false }
}

function discardChanges() {
  if (window.confirm('Discard your unsaved assessment changes?')) populateRows()
}
onBeforeRouteLeave(() => !dirty.value || window.confirm('Leave this page and discard unsaved assessment changes?'))
function beforeUnload(event: BeforeUnloadEvent) {
  if (dirty.value) { event.preventDefault(); event.returnValue = '' }
}
onMounted(() => window.addEventListener('beforeunload', beforeUnload))
onBeforeUnmount(() => window.removeEventListener('beforeunload', beforeUnload))
</script>

<template>
  <main class="assessment-page">
    <NuxtLink to="/teacher/dashboard">← Teacher dashboard</NuxtLink>
    <NuxtLink class="manage-link" to="/teacher/students">Manage students</NuxtLink>
    <h1>Assessment entry</h1>
    <p>Choose a class, curriculum topic, and term. Record one competency rating per learner.</p>
    <template v-if="teacher?.school_id && !classError">
      <form @submit.prevent="loadAssessments">
        <fieldset :disabled="locked">
          <legend>Assessment selection</legend>
          <div class="selectors">
            <div><label for="class">Class</label><select id="class" v-model="classId" required @change="changeClass"><option value="">Select class</option><option v-for="item in classData?.classes" :key="item.id" :value="String(item.id)">{{ item.name }}</option></select></div>
            <div><label for="area">Learning area</label><select id="area" v-model="areaId" required :disabled="!areas.length"><option value="">Select learning area</option><option v-for="area in areas" :key="area.id" :value="String(area.id)">{{ area.name }}</option></select></div>
            <div><label for="strand">Strand</label><select id="strand" v-model="strandId" required :disabled="!areaId"><option value="">Select strand</option><option v-for="strand in strands" :key="strand.id" :value="String(strand.id)">{{ strand.name }}</option></select></div>
            <div><label for="sub-strand">Sub-strand</label><select id="sub-strand" v-model="subStrandId" required :disabled="!strandId"><option value="">Select sub-strand</option><option v-for="sub in subStrands" :key="sub.id" :value="String(sub.id)">{{ sub.name }}</option></select></div>
            <div><label for="term">Term</label><select id="term" v-model="termNumber"><option value="1">Term 1</option><option value="2">Term 2</option><option value="3">Term 3</option></select></div>
            <div><label for="year">Year</label><input id="year" v-model.number="year" type="number" min="2000" max="2099" required></div>
          </div>
          <button type="submit" :disabled="!subStrandId">Load learners and saved ratings</button>
        </fieldset>
      </form>
      <p v-if="!classData?.classes.length">No classes have been assigned to you. Contact your school administrator.</p>
      <p v-if="classId && !working && !areas.length">No curriculum is available for this class grade.</p>
      <p v-if="working" role="status">Working…</p>
      <p v-if="errorMessage" class="error" role="alert">{{ errorMessage }} <span v-for="(item, index) in fieldErrors" :key="index" class="field-error">{{ item }}</span></p>
      <p v-if="message" class="success" role="status">{{ message }}</p>
      <template v-if="loaded">
        <h2>{{ term }} · {{ subStrands.find(sub => String(sub.id) === subStrandId)?.name }}</h2>
        <p class="level-key"><span v-for="level in levels" :key="level.code"><strong>{{ level.code }}</strong>: {{ level.label }}</span></p>
        <p v-if="!rows.length">This class has no learners yet.</p>
        <form v-else @submit.prevent="saveAssessments">
          <fieldset :disabled="working">
            <legend>Learner ratings</legend>
            <div class="table-scroll">
              <table><caption>Enter ratings for {{ rows.length }} learners</caption><thead><tr><th scope="col">Learner</th><th scope="col">Competency</th><th scope="col">Remarks (optional)</th></tr></thead>
                <tbody><tr v-for="row in rows" :key="row.id">
                  <th scope="row">{{ row.full_name }}<small>{{ row.admission_number }}</small></th>
                  <td><select v-model="row.competency_level" :aria-label="`Competency for ${row.full_name}`" required><option value="">Select rating</option><option v-for="level in levels" :key="level.code" :value="level.code">{{ level.code }} — {{ level.label }}</option></select></td>
                  <td><textarea v-model="row.remarks" :aria-label="`Remarks for ${row.full_name}`" maxlength="2000" rows="2" /></td>
                </tr></tbody>
              </table>
            </div>
            <p v-if="dirty">You have unsaved changes. Save or discard them before changing the selection.</p>
            <button type="submit" :disabled="!dirty">{{ working ? 'Saving…' : 'Save assessments' }}</button>
            <button v-if="dirty" type="button" class="secondary" @click="discardChanges">Discard changes</button>
          </fieldset>
        </form>
        <h2>Saved assessments</h2>
        <p v-if="!saved.length">No assessments have been saved for this selection yet.</p>
        <ul v-else><li v-for="rating in saved" :key="rating.student_id">{{ learners.find(learner => learner.id === rating.student_id)?.full_name }} — <strong>{{ rating.competency_level }}</strong><span v-if="rating.remarks"> · {{ rating.remarks }}</span> <button class="history-button" :disabled="working || dirty" :aria-label="`View history for ${learners.find(learner => learner.id === rating.student_id)?.full_name}`" @click="viewHistory(rating)">View history</button></li></ul>
        <section v-if="historyLearner" aria-labelledby="history-title">
          <h2 id="history-title">Revision history: {{ historyLearner }}</h2>
          <p>Each row preserves a saved version. A baseline records the state when history began; earlier edits cannot be reconstructed. Times below are shown in UTC.</p>
          <p v-if="!history.length">No recorded history is available yet.</p>
          <div v-else class="table-scroll"><table><caption>{{ historyLearner }} · {{ term }}</caption><thead><tr><th scope="col">Version</th><th scope="col">Event</th><th scope="col">Rating</th><th scope="col">Remarks</th><th scope="col">Recorded (UTC)</th></tr></thead><tbody><tr v-for="revision in history" :key="revision.id"><td>{{ revision.version }}</td><td>{{ revision.event }}</td><td>{{ revision.competency_level }}</td><td>{{ revision.remarks || '—' }}</td><td><time :datetime="revision.recorded_at">{{ revision.recorded_at.replace('T', ' ').replace('.000000Z', ' UTC').replace('Z', ' UTC') }}</time></td></tr></tbody></table></div>
        </section>
      </template>
    </template>
    <template v-else>
      <p class="error" role="alert">{{ classError?.data?.statusMessage || sessionError?.data?.statusMessage || 'Your teacher account needs a school assignment before recording assessments.' }}</p>
      <button v-if="classError && teacher?.school_id" @click="refreshClasses()">Retry loading classes</button>
    </template>
  </main>
</template>

<style scoped>
.assessment-page { max-width: 1100px; }
.manage-link { float: right; }
.history-button { padding: 7px 12px; margin: 6px 0 6px 12px; }
fieldset { border: 1px solid #dce6e2; border-radius: 8px; padding: 16px; min-width: 0; }
legend { font-weight: bold; padding: 0 8px; }
.selectors { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0 20px; }
select, textarea { width: 100%; padding: 12px; border: 1px solid #9cafa7; border-radius: 6px; font: inherit; background: white; color: inherit; }
select:focus-visible, textarea:focus-visible { outline: 3px solid #e7b64d; outline-offset: 3px; }
.table-scroll { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; min-width: 660px; }
caption { text-align: left; padding: 12px 0; }
th, td { padding: 12px; border-bottom: 1px solid #dce6e2; text-align: left; vertical-align: top; }
small { display: block; font-weight: normal; margin-top: 6px; }
.level-key { display: flex; flex-wrap: wrap; gap: 8px 20px; }
.secondary { background: #e5ece9; color: #18342d; margin-left: 12px; }
.success { background: #e4f3e9; padding: 12px; border-radius: 6px; }
.field-error { display: block; margin-top: 4px; }
li { margin-bottom: 10px; line-height: 1.5; }
@media (max-width: 700px) { .selectors { grid-template-columns: 1fr; } }
</style>
