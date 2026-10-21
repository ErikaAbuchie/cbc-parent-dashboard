<script setup lang="ts">
type Student = { id: number; full_name: string; admission_number: string; parent_phone_number: string; deleted_at: string | null }
useHead({ title: 'Manage students | CBC Parent Insight' })
const { data: session, error: sessionError } = await useFetch('/api/auth/session')
if (sessionError.value?.statusCode === 401) await navigateTo('/teacher/login', { replace: true })
const { data: classData, error: classError } = await useFetch<{ classes: { id: number; name: string }[] }>('/api/teacher/classes')
const classId = ref('')
const students = ref<Student[]>([])
const rosterLoaded = ref(false)
const busy = ref(false)
const errorMessage = ref('')
const message = ref('')
const validationErrors = ref<string[]>([])
const editingId = ref<number | null>(null)
const form = reactive({ full_name: '', admission_number: '', parent_phone_number: '' })
const initial = ref(JSON.stringify(form))
const originalPhone = ref('')
const membershipChange = ref<{ student: Student; restore: boolean } | null>(null)
const membershipPanel = ref<HTMLElement | null>(null)
const dirty = computed(() => JSON.stringify(form) !== initial.value)
const search = ref('')
const matchesSearch = (student: Student) => `${student.full_name} ${student.admission_number}`.toLowerCase().includes(search.value.trim().toLowerCase())
const visibleActive = computed(() => active.value.filter(matchesSearch))
const visibleRemoved = computed(() => removed.value.filter(matchesSearch))
const active = computed(() => students.value.filter(student => !student.deleted_at))
const removed = computed(() => students.value.filter(student => student.deleted_at))
const endpoint = computed(() => `/api/teacher/classes/${classId.value}/students`)

function clearForm() {
  editingId.value = null
  Object.assign(form, { full_name: '', admission_number: '', parent_phone_number: '' })
  originalPhone.value = ''
  initial.value = JSON.stringify(form)
}
async function reportError(error: any) {
  if (error.statusCode === 401 || error.response?.status === 401) {
    await navigateTo('/teacher/login', { replace: true })
    return
  }
  errorMessage.value = error.data?.statusMessage || 'The request failed. Please try again.'
  validationErrors.value = Object.values(error.data?.data?.errors || {}).flat() as string[]
}
async function fetchRoster() {
  const result = await $fetch<{ students: Student[] }>(endpoint.value)
  students.value = result.students
  rosterLoaded.value = true
}
async function loadClass() {
  students.value = []
  rosterLoaded.value = false
  clearForm()
  message.value = ''
  errorMessage.value = ''
  validationErrors.value = []
  if (!classId.value) return
  busy.value = true
  try { await fetchRoster() } catch (error) { await reportError(error) }
  finally { busy.value = false }
}
function editStudent(student: Student) {
  if (dirty.value && !window.confirm('Discard the current unsaved student details?')) return
  editingId.value = student.id
  Object.assign(form, { full_name: student.full_name, admission_number: student.admission_number, parent_phone_number: student.parent_phone_number })
  originalPhone.value = student.parent_phone_number
  initial.value = JSON.stringify(form)
  message.value = ''
  errorMessage.value = ''
  validationErrors.value = []
}
function cancelEdit() {
  if (!dirty.value || window.confirm('Discard the unsaved student details?')) clearForm()
}
async function saveStudent() {
  busy.value = true
  errorMessage.value = ''
  validationErrors.value = []
  message.value = ''
  const editing = editingId.value !== null
  const payload: Record<string, string> = { full_name: form.full_name.trim(), admission_number: form.admission_number.trim() }
  // Existing demo phone placeholders need not be replaced just to correct a name.
  if (!editing || form.parent_phone_number !== originalPhone.value) payload.parent_phone_number = form.parent_phone_number.trim()
  try {
    await $fetch(editing ? `${endpoint.value}/${editingId.value}` : endpoint.value, { method: editing ? 'PATCH' : 'POST', body: payload })
    clearForm()
    message.value = editing ? 'Student updated.' : 'Student added to the class.'
    await fetchRoster()
  } catch (error) { await reportError(error) }
  finally { busy.value = false }
}
async function requestMembershipChange(student: Student, restore = false) {
  if (dirty.value) { errorMessage.value = 'Save or cancel your student details before changing the roster.'; return }
  membershipChange.value = { student, restore }
  await nextTick()
  if (membershipChange.value?.student.id !== student.id) return
  membershipPanel.value?.focus({ preventScroll: true })
  membershipPanel.value?.scrollIntoView({ block: 'start', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' })
}
async function confirmMembershipChange() {
  if (!membershipChange.value || busy.value) return
  const { student, restore } = membershipChange.value
  busy.value = true
  errorMessage.value = ''
  validationErrors.value = []
  message.value = ''
  try {
    await $fetch(`${endpoint.value}/${student.id}${restore ? '/restore' : ''}`, { method: restore ? 'POST' : 'DELETE' })
    membershipChange.value = null
    clearForm()
    message.value = restore ? 'Student restored. Previous parent access codes remain inactive.' : 'Student removed. Assessment history was retained.'
    await fetchRoster()
  } catch (error) { await reportError(error) }
  finally { busy.value = false }
}
onBeforeRouteLeave(() => !dirty.value || window.confirm('Leave and discard unsaved student details?'))
function beforeUnload(event: BeforeUnloadEvent) { if (dirty.value) { event.preventDefault(); event.returnValue = '' } }
onMounted(() => window.addEventListener('beforeunload', beforeUnload))
onBeforeUnmount(() => window.removeEventListener('beforeunload', beforeUnload))
</script>

<template>
  <main class="student-page">
    <NuxtLink to="/teacher/dashboard">← Teacher dashboard</NuxtLink>
    <NuxtLink class="assessment-link" to="/teacher/assessments">Enter assessments</NuxtLink>
    <h1>Manage students</h1>
    <p>Add learners who join your class, correct their details, or remove those who leave. Removed learners can be restored, and their assessment history is retained.</p>
    <p v-if="classError || sessionError" class="error" role="alert">{{ classError?.data?.statusMessage || sessionError?.data?.statusMessage }}</p>
    <template v-else-if="session">
      <label for="student-class">Assigned class</label>
      <select id="student-class" v-model="classId" :disabled="busy || dirty || !!membershipChange" @change="loadClass"><option value="">Select class</option><option v-for="item in classData?.classes" :key="item.id" :value="String(item.id)">{{ item.name }}</option></select>
      <p v-if="!classData?.classes.length">No classes have been assigned to you. Contact your school administrator.</p>
      <p v-if="busy" role="status">Working…</p>
      <div v-if="errorMessage" class="error" role="alert">{{ errorMessage }}<p v-for="(item, index) in validationErrors" :key="index">{{ item }}</p></div>
      <p v-if="message" class="success" role="status">{{ message }}</p>
      <button v-if="classId && !rosterLoaded && !busy" @click="loadClass">Retry loading class</button>
      <template v-if="rosterLoaded">
        <h2>{{ editingId ? 'Edit student' : 'Add student' }}</h2>
        <section v-if="membershipChange" ref="membershipPanel" class="confirmation" tabindex="-1" role="region" aria-labelledby="membership-heading">
          <h2 id="membership-heading">{{ membershipChange.restore ? 'Restore student?' : 'Remove student?' }}</h2>
          <p>{{ membershipChange.student.full_name }} will be {{ membershipChange.restore ? 'restored to' : 'removed from' }} this class's active roster.</p>
          <p v-if="!membershipChange.restore">Assessment history will be retained. Existing parent access codes will be deactivated.</p>
          <p v-else>Previous parent access codes will remain inactive.</p>
          <button :disabled="busy" @click="confirmMembershipChange">{{ membershipChange.restore ? 'Confirm restore' : 'Confirm removal' }}</button>
          <button class="secondary" :disabled="busy" @click="membershipChange = null">Cancel change</button>
        </section>
        <form @submit.prevent="saveStudent"><fieldset :disabled="busy || !!membershipChange">
          <label for="student-name">Full name</label><input id="student-name" v-model="form.full_name" maxlength="255" required>
          <label for="student-admission">Admission number</label><input id="student-admission" v-model="form.admission_number" maxlength="50" required>
          <label for="student-phone">Parent phone number</label><input id="student-phone" v-model="form.parent_phone_number" type="tel" placeholder="+2547XXXXXXXX" :required="!editingId" maxlength="20" aria-describedby="phone-help">
          <p id="phone-help">Use international format: +254 followed by a 7 or 1 and eight digits. Adding or editing a student does not send an SMS.</p>
          <button type="submit" :disabled="!dirty">{{ editingId ? 'Save student details' : 'Add student' }}</button>
          <button v-if="editingId || dirty" type="button" class="secondary" @click="cancelEdit">Cancel</button>
        </fieldset></form>
        <h2>Active learners ({{ active.length }})</h2><label for="learner-search">Find a learner</label><input id="learner-search" v-model="search" type="search" placeholder="Search name or admission number" autocomplete="off"><p v-if="search.trim()">{{ visibleActive.length }} active {{ visibleActive.length === 1 ? 'learner matches' : 'learners match' }} your search.</p>
        <p v-if="!active.length">No active learners in this class.</p>
        <ul class="roster"><li v-for="student in visibleActive" :key="student.id"><div><strong>{{ student.full_name }}</strong><small>{{ student.admission_number }}</small></div><div><button :disabled="busy || !!membershipChange" @click="editStudent(student)">Edit</button><button class="remove" :disabled="busy || dirty || !!membershipChange" @click="requestMembershipChange(student)">Remove</button></div></li></ul>
        <h2>Removed learners ({{ removed.length }})</h2>
        <p v-if="!removed.length">No removed learners.</p>
        <ul class="roster"><li v-for="student in visibleRemoved" :key="student.id"><div><strong>{{ student.full_name }}</strong><small>{{ student.admission_number }}</small></div><button :disabled="busy || dirty || !!membershipChange" @click="requestMembershipChange(student, true)">Restore</button></li></ul>
      </template>
    </template>
  </main>
</template>

<style scoped>
.student-page { max-width: 900px; }
.assessment-link { float: right; }
select { width: 100%; padding: 12px; font: inherit; border: 1px solid #9cafa7; border-radius: 6px; background: white; }
select:focus-visible { outline: 3px solid #e7b64d; outline-offset: 3px; }
fieldset { border: 1px solid #dce6e2; border-radius: 8px; padding: 20px; }
.secondary { background: #e5ece9; color: #18342d; margin-left: 12px; }
.remove { background: #942c2c; margin-left: 12px; }
.roster { list-style: none; padding: 0; }
.roster li { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; padding: 16px 0; border-bottom: 1px solid #dce6e2; }
.roster button { margin-top: 0; }
small { display: block; margin-top: 6px; }
.success { padding: 12px; border-radius: 6px; background: #e4f3e9; }
.confirmation { padding: 20px; margin: 20px 0; border: 2px solid #176349; border-radius: 8px; background: #f2f6f5; scroll-margin-top: 96px; }
.confirmation:focus-visible { outline: 2px solid #176349; outline-offset: 4px; }
</style>
