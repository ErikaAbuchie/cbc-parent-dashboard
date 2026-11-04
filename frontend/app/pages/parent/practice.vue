<script setup lang="ts">
type Topic = { exercise_id?: number | null; question_count: number; source: string; sub_strand?: string; id: number; name: string; learning_area: string; competency_level?: string }
type History = { id: number; topic: string; completed_at: string; correct_count: number; total: number }
type Answer = { question_id: number; selected_index: number; is_correct: boolean; correct_answer: string; feedback: string }
type Attempt = { exercise_id?: number | null; exercise_title?: string | null; id: number; sub_strand_id: number; sub_strand: string; completed: boolean; questions: { id: number; question: string; options: string[] }[]; answered: Answer[]; review?: { question: string; selected_answer: string; correct_answer: string; is_correct: boolean; feedback: string }[] }
useHead({ title: 'Quiz studio | CBC Parent Insight' })
const { data, error, refresh } = await useFetch<{ topics: Topic[]; history: History[] }>('/api/parent/practice')
if (error.value?.statusCode === 401) await navigateTo('/parent/login', { replace: true })
const attempt = ref<Attempt | null>(null)
const currentIndex = ref(0)
const selection = ref<number | null>(null)
const showResults = ref(false)
const busy = ref(false)
const message = ref('')
const panel = ref<HTMLElement | null>(null)
const current = computed(() => attempt.value?.questions[currentIndex.value])
const feedback = computed(() => attempt.value?.answered.find(a => a.question_id === current.value?.id))
const correct = computed(() => attempt.value?.answered.filter(a => a.is_correct).length || 0)
const accuracy = computed(() => Math.round(correct.value / (attempt.value?.questions.length || 1) * 100))
const streak = computed(() => {
  let count = 0
  for (const q of [...(attempt.value?.questions || [])].reverse()) {
    const a = attempt.value?.answered.find(a => a.question_id === q.id)
    if (!a) continue
    if (!a.is_correct) break
    count++
  }
  return count
})
async function focusPanel() { await nextTick(); panel.value?.focus() }
async function fail(error: any) {
  if (error.statusCode === 401 || error.response?.status === 401) { await navigateTo('/parent/login', { replace: true }); return }
  message.value = error.data?.statusMessage || 'Practice is unavailable. Please try again.'
}
async function openAttempt(topicId?: number, historyId?: number, exerciseId?: number) {
  if (busy.value) return
  busy.value = true; message.value = ''
  try {
    const result = historyId ? await $fetch<Attempt>(`/api/parent/practice/${historyId}`) : await $fetch<Attempt>('/api/parent/practice', { method: 'POST', body: exerciseId ? { exercise_id: exerciseId } : { sub_strand_id: topicId } })
    attempt.value = result; showResults.value = result.completed; selection.value = null
    const next = result.questions.findIndex(q => !result.answered.some(a => a.question_id === q.id))
    currentIndex.value = next < 0 ? 0 : next
    await focusPanel()
  } catch (error) { await fail(error) } finally { busy.value = false }
}
const route = useRoute()
onMounted(() => {
  watch(() => route.query.attempt, value => {
    if (value === undefined) return
    if (typeof value !== 'string' || !/^[1-9][0-9]*$/.test(value) || !Number.isSafeInteger(Number(value))) { message.value = 'This practice review link is invalid. Choose an attempt below.'; return }
    void openAttempt(undefined, Number(value))
  }, { immediate: true })
})
async function checkAnswer() {
  if (!attempt.value || !current.value || selection.value === null || busy.value || feedback.value) return
  busy.value = true; message.value = ''
  try {
    attempt.value = await $fetch<Attempt>(`/api/parent/practice/${attempt.value.id}/answer`, { method: 'POST', body: { question_id: current.value.id, answer_index: selection.value } })
    if (attempt.value.completed) await refresh()
    await focusPanel()
  } catch (error) { await fail(error) } finally { busy.value = false }
}
async function nextQuestion() {
  if (!feedback.value || busy.value) return
  if (attempt.value?.completed) showResults.value = true
  else currentIndex.value++
  selection.value = null; await focusPanel()
}
function backToTopics() { attempt.value = null; selection.value = null; message.value = '' }
</script>
<template>
  <main class="practice-page"><NuxtLink to="/parent/report" class="back-link">← Back to report</NuxtLink>
    <header class="studio-header"><span class="eyebrow">LEARN · PLAY · GROW</span><h1>Quiz studio</h1><p>A little practice. A new discovery.</p></header>
    <p class="muted">Self-paced practice with instant explanations. Scores do not change teacher competency ratings.</p>
    <p v-if="error" class="error" role="alert">{{ error.data?.statusMessage || 'Cannot load practice.' }} <button @click="refresh()">Retry</button></p>
    <p v-if="message" class="error" role="alert">{{ message }}</p><p v-if="busy" role="status">Saving or loading your quiz…</p>
    <template v-if="data && !attempt"><h2>Pick your next challenge</h2><p class="muted">Teacher exercises and starter practice for the topics in this report · No timer</p>
      <p v-if="!data.topics.length">No practice quiz is available for the topics in this report yet.</p>
      <div class="topic-grid"><article v-for="(topic,index) in data.topics" :key="`${topic.exercise_id ? 'exercise' : 'pilot'}-${topic.exercise_id || topic.id}`" class="topic-card"><span class="topic-symbol" aria-hidden="true">{{ index % 2 ? '+' : '123' }}</span><p class="eyebrow">{{ topic.learning_area }}</p><h3>{{ topic.name }}</h3><p>{{ topic.question_count }} {{ topic.question_count === 1 ? 'question' : 'questions' }} · Multiple choice</p><p class="muted">{{ topic.source === 'teacher' ? 'Teacher exercise' : 'Starter practice' }}<span v-if="topic.sub_strand"> · {{ topic.sub_strand }}</span></p><button :disabled="busy" @click="openAttempt(topic.id, undefined, topic.exercise_id || undefined)">Play or resume →</button></article></div>
      <section class="history"><h2>Your recent rounds</h2><p v-if="!data.history.length">Your completed quizzes will appear here.</p><article v-for="item in data.history" :key="item.id"><div><h3>{{ item.topic }}</h3><p>{{ item.correct_count }} / {{ item.total }} correct · {{ new Date(item.completed_at).toLocaleString() }}</p></div><button class="secondary" :disabled="busy" @click="openAttempt(undefined,item.id)">Review</button></article></section>
    </template>
    <section v-if="attempt" ref="panel" class="quiz-panel" tabindex="-1" aria-labelledby="practice-heading" :aria-busy="busy">
      <div class="quiz-top"><span class="eyebrow">{{ showResults ? 'ROUND COMPLETE' : 'YOUR PRACTICE ROUND' }}</span><button class="secondary" :disabled="busy" @click="backToTopics">Back to topics</button></div><h2 id="practice-heading">{{ attempt.exercise_title || attempt.sub_strand }}</h2>
      <template v-if="showResults"><div class="results-hero"><span class="result-symbol" aria-hidden="true">✦</span><h3>{{ accuracy === 100 ? 'Brilliant round!' : 'Every question helps you grow!' }}</h3><strong class="big-score">{{ correct }} / {{ attempt.questions.length }}</strong><p>{{ accuracy }}% accuracy · {{ correct * 100 }} practice points</p><p>These points are for this practice round only.</p></div>
        <div class="result-actions"><button :disabled="busy" @click="openAttempt(attempt.sub_strand_id, undefined, attempt.exercise_id || undefined)">Play again</button><button class="secondary" :disabled="busy" @click="backToTopics">Choose another topic</button></div><h3>Review your answers</h3>
        <article v-for="(item,index) in attempt.review" :key="index" class="review-item"><span :class="['answer-tag',{ incorrect: !item.is_correct }]">{{ item.is_correct ? 'Correct' : 'Keep practising' }}</span><h3>{{ index+1 }}. {{ item.question }}</h3><p>Your answer: {{ item.selected_answer }}</p><p>Correct answer: <strong>{{ item.correct_answer }}</strong></p><p>{{ item.feedback }}</p></article>
      </template>
      <template v-else-if="current"><div class="score-strip"><span>Question {{ currentIndex+1 }} / {{ attempt.questions.length }}</span><span>{{ correct * 100 }} points</span><span>{{ streak }} answer streak</span></div><progress :value="attempt.answered.length" :max="attempt.questions.length" aria-label="Questions answered" />
        <form @submit.prevent="checkAnswer"><fieldset :disabled="busy || !!feedback"><legend>{{ current.question }}</legend><div class="answer-grid"><label v-for="(option,choice) in current.options" :key="`${current.id}-${choice}`" :class="['answer-option',`option-${choice}`,{chosen: selection === choice || feedback?.selected_index === choice}]"><input v-model="selection" type="radio" :name="`question-${current.id}`" :value="choice" required><span class="choice-letter">{{ ['A','B','C','D'][choice] }}</span><span>{{ option }}</span></label></div></fieldset><button v-if="!feedback" class="check-button" :disabled="busy || selection === null">{{ busy ? 'Checking…' : 'Check answer' }}</button></form>
        <div v-if="feedback" :class="['feedback',{incorrect: !feedback.is_correct}]" role="status"><h3>{{ feedback.is_correct ? 'Correct! +100 points' : 'Good try — let’s learn from this' }}</h3><p>Correct answer: <strong>{{ feedback.correct_answer }}</strong></p><p>{{ feedback.feedback }}</p><button :disabled="busy" @click="nextQuestion">{{ attempt.completed ? 'See my results →' : 'Next question →' }}</button></div><p class="muted">Each checked answer is saved and locked. Resume from the next unanswered question if you leave.</p>
      </template>
    </section>
  </main>
</template>
<style scoped>
.practice-page{max-width:1000px;margin:auto}.back-link{display:inline-block;margin-bottom:20px}.studio-header{padding:30px;border-radius:22px;background:linear-gradient(120deg,#7540b5,#a474dd);color:white}.studio-header h1{color:white;margin:12px 0;font-size:clamp(1.875rem,5vw,2.75rem)}.studio-header p,.studio-header .eyebrow{color:#f3e9fc}.muted{color:#655374;font-size:0.875rem;line-height:1.6}.topic-grid,.answer-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.topic-card,.quiz-panel,.history{background:white;border:1px solid #e6daf0;border-radius:20px;padding:26px;margin-top:20px}.topic-card h3{font-size:1.4375rem}.topic-symbol{display:inline-grid;place-items:center;width:60px;height:60px;border-radius:16px;background:#eee2fb;color:#7540b5;font-size:1.5625rem;font-weight:800}.topic-card button{width:100%;margin-top:18px}.history article,.quiz-top,.score-strip,.result-actions{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap}.history article{padding:14px 0;border-top:1px solid #e9e0f2}.history h3{margin:0}.history p{font-size:0.8125rem;color:#655374}.secondary{background:#f3ecfa;color:#654487;border:1px solid #ddcbea}.quiz-panel:focus{outline:2px solid #9263c8;outline-offset:4px}.quiz-top .secondary{padding:9px 14px}.score-strip{background:#f5effb;padding:14px;border-radius:12px;font-size:0.8125rem;font-weight:700}progress{width:100%;height:10px;margin:18px 0;accent-color:#9155c9}fieldset{border:0;padding:0;margin:16px 0;min-width:0}legend{font-size:clamp(1.3125rem,3vw,1.75rem);font-weight:750;line-height:1.5;margin-bottom:25px;width:100%}.answer-option{display:flex;align-items:center;gap:12px;border:3px solid transparent;border-radius:16px;min-height:110px;padding:22px;margin:0;color:white;cursor:pointer;font-size:1.125rem;font-weight:650;overflow-wrap:anywhere}.option-0{background:#236991}.option-1{background:#8a407d}.option-2{background:#93600d}.option-3{background:#6540a3}.answer-option.chosen{border-color:#39204f;box-shadow:0 0 0 3px #dac5ee}.answer-option:focus-within{outline:3px solid #39204f;outline-offset:4px}.answer-option input{width:18px;height:18px;flex-shrink:0;accent-color:#5e308e;margin:0}.choice-letter{font-size:0.75rem;border:1px solid #ffffff80;padding:5px 8px;border-radius:7px}.check-button{display:block;margin:24px 0 0 auto;min-width:180px}.feedback{padding:24px;background:#f3ebfa;border:1px solid #d5bce9;border-radius:16px;margin:22px 0}.feedback.incorrect{background:#fff6e7;border-color:#ead1a3}.feedback h3{margin-top:0}.feedback p{line-height:1.65}.results-hero{text-align:center;padding:25px 0}.result-symbol{font-size:3.75rem;color:#9155c9}.big-score{display:block;font-size:4rem;color:#7540b5}.result-actions{justify-content:center;margin-bottom:35px}.review-item{padding:22px 0;border-top:1px solid #e6daf0}.review-item p{line-height:1.6}.answer-tag{display:inline-block;background:#eee2fb;color:#654487;font-size:0.75rem;padding:5px 10px;border-radius:20px;font-weight:700}.answer-tag.incorrect{background:#fff0d6;color:#765013}fieldset:disabled .answer-option{cursor:default}@media(max-width:600px){.topic-grid,.answer-grid{grid-template-columns:1fr}.studio-header,.quiz-panel,.history,.topic-card{padding:20px 16px}.answer-option{min-height:78px;padding:16px;font-size:1.0625rem}.check-button,.result-actions button{width:100%}.score-strip{gap:8px;font-size:0.75rem}.quiz-top{align-items:flex-start}}
</style>
