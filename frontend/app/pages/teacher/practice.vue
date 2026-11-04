<script setup lang="ts">
type Question = { question: string; options: string[]; correct: number | null; feedback: string }
type Exercise = { id: number; title: string; sub_strand_id: number; status: 'draft' | 'published' | 'archived'; questions: Question[]; sub_strand?: { name: string } }
type Area = { id: number; name: string; strands: { id: number; name: string; sub_strands: { id: number; name: string }[] }[] }
useHead({ title: 'Practice exercises | Teacher portal' })
const { data: teacher, error: sessionError } = await useFetch('/api/auth/session')
if (sessionError.value?.statusCode === 401) await navigateTo('/teacher/login', { replace: true })
const { data: classes, error: classError } = await useFetch<{ classes: { id: number; name: string }[] }>('/api/teacher/classes')
const classId = ref('')
const areas = ref<Area[]>([])
const exercises = ref<Exercise[]>([])
const areaId = ref('')
const strandId = ref('')
const editor = ref<{ id?: number; title: string; sub_strand_id: string; questions: Question[] } | null>(null)
const baseline = ref('')
const dirty = computed(() => !!editor.value && JSON.stringify(editor.value) !== baseline.value)
const currentSaved = computed(() => exercises.value.find(exercise => exercise.id === editor.value?.id && exercise.status === 'draft'))
const strands = computed(() => areas.value.find(area => String(area.id) === areaId.value)?.strands || [])
const subs = computed(() => strands.value.find(strand => String(strand.id) === strandId.value)?.sub_strands || [])
const busy = ref(false)
const message = ref('')
const errors = ref<string[]>([])
const panel = ref<HTMLElement | null>(null)
const confirmation = ref<{ exercise: Exercise; action: 'publish' | 'archive' | 'delete' } | null>(null)
const preview = ref<Question[]>([])
const previewIndex = ref(0)
const previewAnswer = ref<number | null>(null)
const previewChecked = ref(false)
const previewQuestion = computed(() => preview.value[previewIndex.value])
const previewPanel = ref<HTMLElement | null>(null)

async function feedback() { await nextTick(); panel.value?.focus(); panel.value?.scrollIntoView({ block: 'nearest', behavior: 'auto' }) }
async function fail(error: any) {
  if (error.statusCode === 401 || error.response?.status === 401) { await navigateTo('/teacher/login', { replace: true }); return }
  message.value = error.data?.statusMessage || 'The exercise could not be saved. Please try again.'
  errors.value = Object.values(error.data?.data?.errors || {}).flat() as string[]
}
async function refreshList() {
  const result = await $fetch<{ exercises: Exercise[] }>(`/api/teacher/classes/${classId.value}/practice-exercises`)
  exercises.value = result.exercises
}
watch(classId, async (id) => {
  editor.value = null; confirmation.value = null; preview.value = []; areas.value = []; exercises.value = []; message.value = ''; errors.value = []
  if (!id) return
  busy.value = true
  try {
    const [options, list] = await Promise.all([
      $fetch<{ learning_areas: Area[] }>(`/api/teacher/classes/${id}/assessment-options`),
      $fetch<{ exercises: Exercise[] }>(`/api/teacher/classes/${id}/practice-exercises`)
    ])
    if (id === classId.value) { areas.value = options.learning_areas; exercises.value = list.exercises }
  } catch (error) { await fail(error) } finally { busy.value = false }
})
function edit(exercise?: Exercise) {
  if (dirty.value) { message.value = 'Save or cancel your current draft first.'; void feedback(); return }
  message.value = ''; errors.value = []; confirmation.value = null; preview.value = []
  editor.value = { id: exercise?.id, title: exercise?.title || '', sub_strand_id: exercise ? String(exercise.sub_strand_id) : '', questions: exercise ? JSON.parse(JSON.stringify(exercise.questions)).map(({ question, options, correct, feedback }: Question) => ({ question, options, correct, feedback })) : [] }
  const area = areas.value.find(area => area.strands.some(strand => strand.sub_strands.some(sub => sub.id === exercise?.sub_strand_id)))
  areaId.value = area ? String(area.id) : ''
  strandId.value = String(area?.strands.find(strand => strand.sub_strands.some(sub => sub.id === exercise?.sub_strand_id))?.id || '')
  baseline.value = JSON.stringify(editor.value)
}
function cancel() { if (!dirty.value || window.confirm('Discard the unsaved draft changes?')) { editor.value = null; preview.value = [] } }
async function save() {
  if (!editor.value || busy.value) return
  busy.value = true; message.value = ''; errors.value = []; preview.value = []
  try {
    const id = editor.value.id
    const result = await $fetch<{ exercise: Exercise }>(`/api/teacher/classes/${classId.value}/practice-exercises${id ? `/${id}` : ''}`, {
      method: id ? 'PATCH' : 'POST', body: { title: editor.value.title, sub_strand_id: Number(editor.value.sub_strand_id), questions: editor.value.questions }
    })
    baseline.value = JSON.stringify(editor.value)
    edit(result.exercise)
    await refreshList()
    message.value = 'Draft saved. Preview it, then publish it when ready. Parents cannot see drafts.'
  } catch (error) { await fail(error) } finally { busy.value = false; await feedback() }
}
async function duplicate(exercise: Exercise) {
  if (dirty.value) { message.value = 'Save or cancel your current draft first.'; await feedback(); return }
  busy.value = true; errors.value = []
  try {
    const result = await $fetch<{ exercise: Exercise }>(`/api/teacher/classes/${classId.value}/practice-exercises/${exercise.id}/duplicate`, { method: 'POST', body: {} })
    await refreshList(); edit(result.exercise); message.value = 'A separate draft copy is ready to edit.'
  } catch (error) { await fail(error) } finally { busy.value = false; await feedback() }
}
async function confirm(exercise: Exercise, action: 'publish' | 'archive' | 'delete') {
  if (dirty.value) { message.value = 'Save or cancel your current draft first.'; await feedback(); return }
  confirmation.value = { exercise, action }; message.value = ''; errors.value = []; await feedback()
}
async function applyAction() {
  if (!confirmation.value || busy.value) return
  const { exercise, action } = confirmation.value
  busy.value = true; errors.value = []
  try {
    await $fetch(`/api/teacher/classes/${classId.value}/practice-exercises/${exercise.id}${action === 'delete' ? '' : `/${action}`}`, { method: action === 'delete' ? 'DELETE' : 'POST', ...(action === 'delete' ? {} : { body: {} }) })
    if (editor.value?.id === exercise.id) { editor.value = null; preview.value = [] }
    confirmation.value = null; await refreshList()
    message.value = action === 'publish' ? 'Exercise published. It is available for learners whose parent reports include this assessed sub-strand.' : action === 'archive' ? 'Exercise archived. New attempts are stopped; existing attempts and results are retained.' : 'Draft deleted.'
  } catch (error) { await fail(error) } finally { busy.value = false; await feedback() }
}
async function showPreview(questions: Question[]) {
  if (!questions.length || questions.some(q => !q.question.trim() || !q.feedback.trim() || q.correct === null || q.options.some(option => !option.trim()))) {
    message.value = 'Complete at least one question, four options, its correct answer and explanation before previewing.'; await feedback(); return
  }
  preview.value = JSON.parse(JSON.stringify(questions)); previewIndex.value = 0; previewAnswer.value = null; previewChecked.value = false
  await nextTick(); previewPanel.value?.focus(); previewPanel.value?.scrollIntoView({ block: 'start', behavior: 'auto' })
}
function nextPreview() { previewIndex.value++; previewAnswer.value = null; previewChecked.value = false }
onBeforeRouteLeave(() => !dirty.value || window.confirm('Leave and discard unsaved exercise changes?'))
function beforeUnload(event: BeforeUnloadEvent) { if (dirty.value) { event.preventDefault(); event.returnValue = '' } }
onMounted(() => window.addEventListener('beforeunload', beforeUnload))
onBeforeUnmount(() => window.removeEventListener('beforeunload', beforeUnload))
</script>

<template>
  <main class="teacher-practice"><span class="eyebrow">CREATE · PREVIEW · SHARE</span><h1>Practice exercises</h1><p>Prepare short multiple-choice exercises for your assigned classes. Practice marks stay separate from competency ratings.</p>
    <p v-if="sessionError || classError" class="error" role="alert">{{ sessionError?.data?.statusMessage || classError?.data?.statusMessage }}</p>
    <template v-else-if="teacher"><label for="exercise-class">Assigned class</label><select id="exercise-class" v-model="classId" :disabled="busy || dirty"><option value="">Choose a class</option><option v-for="item in classes?.classes" :key="item.id" :value="String(item.id)">{{ item.name }}</option></select><p v-if="!classes?.classes.length">No classes are assigned to you.</p><p v-if="dirty" class="hint">Unsaved changes. Save or cancel before switching classes or exercises.</p>
      <div v-if="classId" class="exercise-tools"><button type="button" :disabled="busy || dirty" @click="edit()">Create exercise</button><button class="secondary" type="button" :disabled="busy" @click="refreshList().catch(fail)">Refresh list</button></div>
      <section ref="panel" tabindex="-1" class="exercise-feedback" aria-live="polite"><p v-if="busy" role="status">Working…</p><p v-if="message" role="status">{{ message }}</p><ul v-if="errors.length" class="error"><li v-for="(error,index) in errors" :key="index">{{ error }}</li></ul><div v-if="confirmation" class="action-confirmation"><h2>{{ confirmation.action === 'publish' ? 'Publish' : confirmation.action === 'archive' ? 'Archive' : 'Delete draft' }}: {{ confirmation.exercise.title }}?</h2><p>{{ confirmation.action === 'publish' ? 'Published questions are locked. You can duplicate the exercise to revise it later. It will be available only through eligible parent reports in this class.' : confirmation.action === 'archive' ? 'Parents cannot start new attempts after archiving. Existing snapshots and results remain accessible.' : 'This removes the unpublished draft and its questions.' }}</p><button type="button" :disabled="busy" @click="applyAction">Confirm {{ confirmation.action }}</button><button class="secondary" type="button" :disabled="busy" @click="confirmation = null">Cancel</button></div></section>
      <form v-if="editor" class="exercise-editor" @submit.prevent="save"><h2>{{ editor.id ? 'Edit draft' : 'New draft' }}</h2><fieldset :disabled="busy"><legend>Exercise details</legend><label for="exercise-title">Title</label><input id="exercise-title" v-model="editor.title" maxlength="160" required placeholder="For example, Fractions at home"><div class="curriculum-grid"><div><label for="exercise-area">Subject</label><select id="exercise-area" v-model="areaId" required @change="strandId = ''; editor.sub_strand_id = ''"><option value="">Choose subject</option><option v-for="area in areas" :key="area.id" :value="String(area.id)">{{ area.name }}</option></select></div><div><label for="exercise-strand">Strand</label><select id="exercise-strand" v-model="strandId" required :disabled="!areaId" @change="editor.sub_strand_id = ''"><option value="">Choose strand</option><option v-for="strand in strands" :key="strand.id" :value="String(strand.id)">{{ strand.name }}</option></select></div><div><label for="exercise-sub">Sub-strand</label><select id="exercise-sub" v-model="editor.sub_strand_id" required :disabled="!strandId"><option value="">Choose sub-strand</option><option v-for="sub in subs" :key="sub.id" :value="String(sub.id)">{{ sub.name }}</option></select></div></div></fieldset>
        <p>You may save incomplete questions as a draft. Publishing requires 1–30 complete questions with four different options each.</p>
        <fieldset v-for="(question,index) in editor.questions" :key="index" :disabled="busy" class="question-editor"><legend>Question {{ index + 1 }}</legend><label :for="`question-${index}`">Question text</label><textarea :id="`question-${index}`" v-model="question.question" maxlength="1000" rows="3" /><div class="options-grid"><div v-for="(_,option) in question.options" :key="option"><label :for="`question-${index}-option-${option}`">Option {{ ['A','B','C','D'][option] }}</label><input :id="`question-${index}-option-${option}`" v-model="question.options[option]" maxlength="400"></div></div><label :for="`correct-${index}`">Correct answer</label><select :id="`correct-${index}`" v-model="question.correct"><option :value="null">Select the correct answer</option><option v-for="(_,option) in question.options" :key="option" :value="option">Option {{ ['A','B','C','D'][option] }}</option></select><label :for="`feedback-${index}`">Explanation shown after answering</label><textarea :id="`feedback-${index}`" v-model="question.feedback" maxlength="1000" rows="2" /><button class="secondary" type="button" @click="editor.questions.splice(index,1); preview = []">Remove question {{ index + 1 }}</button></fieldset>
        <div class="exercise-buttons"><button type="button" :disabled="busy || editor.questions.length >= 30" @click="editor.questions.push({question:'', options:['','','',''], correct:null, feedback:''}); preview = []">Add question</button><button type="submit" :disabled="busy">Save draft</button><button v-if="currentSaved && !dirty" type="button" :disabled="busy" @click="confirm(currentSaved,'publish')">Publish saved draft</button><button class="secondary" type="button" :disabled="busy" @click="showPreview(editor.questions)">Preview questions</button><button class="secondary" type="button" :disabled="busy" @click="cancel">Cancel editing</button></div>
      </form>
      <section v-if="previewQuestion" ref="previewPanel" tabindex="-1" class="exercise-preview" aria-labelledby="exercise-preview-title"><div class="exercise-tools"><h2 id="exercise-preview-title">Learner preview</h2><button class="secondary" type="button" @click="preview = []">Close preview</button></div><p>Preview only. These answers are not saved. Question {{ previewIndex + 1 }} of {{ preview.length }}.</p><fieldset :disabled="previewChecked"><legend>{{ previewQuestion.question }}</legend><label v-for="(option,index) in previewQuestion.options" :key="index" class="preview-option"><input v-model="previewAnswer" type="radio" name="exercise-preview-answer" :value="index">{{ option }}</label></fieldset><button v-if="!previewChecked" type="button" :disabled="previewAnswer === null" @click="previewChecked = true">Check answer</button><div v-else role="status"><h3>{{ previewAnswer === previewQuestion.correct ? 'Correct!' : 'Try learning from the explanation' }}</h3><p>Correct answer: {{ previewQuestion.options[previewQuestion.correct!] }}</p><p>{{ previewQuestion.feedback }}</p><button v-if="previewIndex + 1 < preview.length" type="button" @click="nextPreview">Next question</button><p v-else>Preview complete.</p></div></section>
      <section v-if="classId" class="exercise-list"><h2>Your exercises</h2><p v-if="!exercises.length && !busy">No exercises yet. Create a draft above.</p><article v-for="exercise in exercises" :key="exercise.id"><div class="exercise-title-row"><h3>{{ exercise.title }}</h3><span class="status-badge">{{ exercise.status }}</span></div><p>{{ exercise.sub_strand?.name }} · {{ exercise.questions.length }} {{ exercise.questions.length === 1 ? 'question' : 'questions' }}</p><div class="exercise-buttons"><button v-if="exercise.status === 'draft'" type="button" :disabled="busy || !!confirmation" @click="edit(exercise)">Edit draft</button><button v-if="exercise.status === 'draft'" type="button" :disabled="busy || !!confirmation" @click="confirm(exercise,'publish')">Publish</button><button class="secondary" type="button" :disabled="busy || !!confirmation" @click="showPreview(exercise.questions)">Preview</button><button class="secondary" type="button" :disabled="busy || !!confirmation" @click="duplicate(exercise)">Duplicate</button><button v-if="exercise.status === 'published'" class="secondary" type="button" :disabled="busy || !!confirmation" @click="confirm(exercise,'archive')">Archive</button><button v-if="exercise.status === 'draft'" class="secondary" type="button" :disabled="busy || !!confirmation" @click="confirm(exercise,'delete')">Delete draft</button></div></article></section>
    </template>
  </main>
</template>

<style scoped>
.teacher-practice p{font-size:1.0625rem;line-height:1.7}.exercise-tools,.exercise-buttons,.exercise-title-row{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.exercise-tools{justify-content:space-between;margin:18px 0}.exercise-buttons button,.exercise-tools button{margin-top:0}.exercise-buttons{margin-top:18px}.secondary{background:#f2eaf9;color:#654487;border-color:#ddcbea}.exercise-editor,.exercise-preview,.exercise-list article{padding:24px;border:1px solid #e4d7ee;border-radius:16px;margin:24px 0;background:#fff}.exercise-editor>h2{margin-top:0}.teacher-practice fieldset{border:1px solid #e4d7ee;border-radius:12px;padding:20px;margin:20px 0}.teacher-practice legend{font-weight:700;padding:0 8px;line-height:1.6}.curriculum-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.options-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 16px}.exercise-feedback{scroll-margin-top:24px}.exercise-feedback:empty{display:none}.exercise-feedback:focus{outline:none}.exercise-feedback .error{line-height:1.7}.action-confirmation,.exercise-preview{background:#f7f1fc;padding:24px;border:2px solid #b38cd2;border-radius:14px}.action-confirmation button{margin-right:10px}.hint{color:#745092}.status-badge{padding:6px 12px;background:#eee2fb;color:#654487;border-radius:30px;font-size:.875rem;text-transform:capitalize}.exercise-title-row{justify-content:space-between}.exercise-title-row h3{margin:0;overflow-wrap:anywhere}.preview-option{display:flex;gap:12px;align-items:center;font-weight:500;padding:14px;border:1px solid #d6bfe7;border-radius:10px;background:#fff}.preview-option input{flex-shrink:0}.exercise-preview{scroll-margin-top:24px}.exercise-preview:focus{outline:3px solid #9263c8;outline-offset:4px}@media(max-width:760px){.curriculum-grid,.options-grid{grid-template-columns:1fr}.exercise-editor,.exercise-preview,.exercise-list article{padding:20px 14px}.teacher-practice fieldset{padding:16px 12px;margin-inline:0}.exercise-buttons{align-items:stretch}.exercise-buttons button{flex:1 1 140px}}
</style>
