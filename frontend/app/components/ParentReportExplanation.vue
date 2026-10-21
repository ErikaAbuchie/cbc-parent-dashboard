<script setup lang="ts">
type Explanation = { source: 'model' | 'fallback' | 'empty'; model: string | null; summary: string; activity: string; level_guide: { code: string; count: number; meaning: string }[] }
type Subject = { id: number; name: string; strands: { id: number; name: string }[] }
const props = defineProps<{ subjects: Subject[] }>()
const result = ref<Explanation | null>(null)
const tips = ref<Explanation | null>(null)
const subjectId = ref('')
const strandId = ref('')
const strands = computed(() => props.subjects.find(s => String(s.id) === subjectId.value)?.strands || [])
const tipTitle = ref('')
const loading = ref<'' | 'summary' | 'home_tips'>('')
const message = ref('')
watch(() => props.subjects, subjects => {
  if (!subjects.some(s => String(s.id) === subjectId.value)) subjectId.value = String(subjects[0]?.id || '')
}, { immediate: true })
watch(strands, values => { strandId.value = String(values[0]?.id || ''); tips.value = null }, { immediate: true })
watch(strandId, () => { tips.value = null; message.value = '' })
let cancelled = false
onBeforeUnmount(() => { cancelled = true })
async function explain(mode: 'summary' | 'home_tips') {
  if (loading.value || (mode === 'home_tips' && (!subjectId.value || !strandId.value))) return
  loading.value = mode; message.value = ''
  const selectedSubject = subjectId.value
  const selectedStrand = strandId.value
  const title = `${props.subjects.find(s => String(s.id) === selectedSubject)?.name} · ${strands.value.find(s => String(s.id) === selectedStrand)?.name}`
  try {
    const response = await $fetch<Explanation>('/api/parent/explanation', { method: 'POST', timeout: 60000,
      body: mode === 'summary' ? { mode } : { mode, subject_id: Number(selectedSubject), strand_id: Number(selectedStrand) } })
    if (cancelled) return
    if (mode === 'summary') result.value = response
    else if (selectedSubject === subjectId.value && selectedStrand === strandId.value) { tips.value = response; tipTitle.value = title }
  } catch (error: any) {
    if (cancelled) return
    if (error.statusCode === 401 || error.response?.status === 401) { await navigateTo('/parent/login', { replace: true }); return }
    message.value = error.data?.statusMessage || 'Guidance is unavailable. You can still read the results and teacher remarks.'
  } finally { loading.value = '' }
}
</script>
<template>
  <section class="explanation" aria-labelledby="explanation-heading" :aria-busy="!!loading">
    <span class="eyebrow">SUPPORT FOR PARENTS</span><h2 id="explanation-heading">Understand and support your learner</h2>
    <p class="controls">Choose an explanation or home practice tips. Each request may take up to a minute.</p>
    <div class="ai-grid">
      <section class="ai-card" aria-labelledby="summary-heading"><h3 id="summary-heading">1. Explain the grades simply</h3><p>What do EE, ME, AE and BE mean? What do these results say about the skills assessed?</p>
        <button class="controls" type="button" :disabled="!!loading" @click="explain('summary')">{{ loading === 'summary' ? 'Explaining…' : 'Explain these results' }}</button>
        <div v-if="result" aria-live="polite"><p class="source">{{ result.source === 'model' ? 'AI-generated explanation' : result.source === 'empty' ? 'No results to explain' : 'Standard explanation · AI unavailable' }}</p><h4>The rating letters</h4><ul><li v-for="level in result.level_guide" :key="level.code"><strong>{{ level.code }}</strong> — {{ level.meaning }} <span class="count">({{ level.count }} assessed skills)</span></li></ul><h4>Your learner’s results</h4><p class="generated-text">{{ result.summary }}</p></div>
      </section>
      <section class="ai-card" aria-labelledby="tips-heading"><h3 id="tips-heading">2. Help at home</h3><p>Pick a subject and strand to get short activities based on your learner’s recorded results.</p>
        <div class="controls"><label for="tips-subject">Subject</label><select id="tips-subject" v-model="subjectId" :disabled="!!loading || !subjects.length"><option v-for="subject in subjects" :key="subject.id" :value="String(subject.id)">{{ subject.name }}</option></select>
        <label for="tips-strand">Strand</label><select id="tips-strand" v-model="strandId" :disabled="!!loading || !strands.length"><option v-for="strand in strands" :key="strand.id" :value="String(strand.id)">{{ strand.name }}</option></select>
        <button type="button" :disabled="!!loading || !subjectId || !strandId" @click="explain('home_tips')">{{ loading === 'home_tips' ? 'Preparing home tips…' : 'Get home tips' }}</button></div>
        <div v-if="tips" aria-live="polite"><h4>{{ tipTitle }}</h4><p class="source">{{ tips.source === 'model' ? 'AI-generated home tips' : 'General practice guidance · AI unavailable' }}</p><p class="generated-text">{{ tips.summary }}</p><h4>Try this together</h4><p class="generated-text">{{ tips.activity }}</p></div>
      </section>
    </div>
    <p v-if="loading" class="controls" role="status">Preparing guidance. You can still read the report below.</p><p v-if="message" class="error controls" role="alert">{{ message }}</p>
    <p class="note">AI suggestions may be inaccurate and cannot guarantee a higher grade. The teacher’s ratings and remarks remain the source of truth. Ask the teacher if guidance does not match the results.</p>
  </section>
</template>
<style scoped>
.explanation{margin:24px 0;padding:24px;border:1px solid #dce6e2;border-radius:16px;background:#f4f9f5}h2{margin-top:10px}.ai-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.ai-card{min-width:0;background:white;border:1px solid #dfebe3;border-radius:12px;padding:22px}.ai-card h3{margin-top:0}.ai-card p,li{line-height:1.7;font-size:14px}li{margin-bottom:10px}ul{padding-left:20px}.generated-text{white-space:pre-wrap;overflow-wrap:anywhere}.source,.count{font-size:12px;color:#536e60}.source{margin-top:22px;font-weight:650}.note{font-size:12px;line-height:1.6;margin-bottom:0}.ai-card button{margin-top:12px}.ai-card select{margin-bottom:14px}@media(max-width:850px){.ai-grid{grid-template-columns:1fr}}@media(max-width:480px){.explanation{padding:18px 12px}.ai-card{padding:18px 14px}.ai-card button{width:100%}}@media print{.controls{display:none!important}.ai-grid{display:block}.ai-card{break-inside:avoid;margin-bottom:14px}.explanation{background:white;border-color:#999}h2,h3,h4{break-after:avoid}}
</style>
