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
const subjectFilter = ref('')
const message = ref('')
const panel = ref<HTMLElement | null>(null)
const libraryHeading = ref<HTMLElement | null>(null)
const topicSubjects = computed(() => [...new Set(data.value?.topics.map(topic => topic.learning_area) ?? [])].sort())
const topics = computed(() => (data.value?.topics ?? []).filter(topic => !subjectFilter.value || topic.learning_area === subjectFilter.value))
const current = computed(() => attempt.value?.questions[currentIndex.value])
const feedback = computed(() => attempt.value?.answered.find(a => a.question_id === current.value?.id))
const correct = computed(() => attempt.value?.answered.filter(a => a.is_correct).length || 0)
const accuracy = computed(() => Math.round(correct.value / (attempt.value?.questions.length || 1) * 100))
const progress = computed(() => (attempt.value?.answered.length ?? 0) / (attempt.value?.questions.length || 1) * 100)
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
async function backToTopics() {
  attempt.value = null
  selection.value = null
  message.value = ''
  await nextTick()
  libraryHeading.value?.focus()
}
</script>
<template>
  <main class="practice-page">
    <NuxtLink to="/parent/report" class="back-link">← Back to report</NuxtLink>
    <header v-if="!attempt" class="studio-header">
      <div>
        <h1>Quiz studio</h1>
        <p>Choose a topic and practise at your own pace. Get an explanation after every answer.</p>
      </div>
      <div class="studio-mark" aria-hidden="true"><HubIcon name="practice" /></div>
    </header>

    <p v-if="error" class="error" role="alert">
      {{ error.data?.statusMessage || 'Cannot load practice.' }}
      <button type="button" @click="refresh()">Retry</button>
    </p>
    <p v-if="message" class="error" role="alert">{{ message }}</p>

    <template v-if="data && !attempt">
      <div class="library-heading">
        <h2 ref="libraryHeading" tabindex="-1">Choose a quiz</h2>
        <label v-if="topicSubjects.length > 1">
          Subject
          <select v-model="subjectFilter">
            <option value="">All subjects</option>
            <option v-for="subject in topicSubjects" :key="subject" :value="subject">{{ subject }}</option>
          </select>
        </label>
      </div>
      <p v-if="busy" role="status">Opening your quiz…</p>
      <p v-if="!data.topics.length" class="empty-state">No practice quizzes are available for this report yet.</p>
      <p v-else-if="!topics.length" class="empty-state">No quizzes match this subject.</p>
      <div v-else class="topic-grid">
        <article
          v-for="topic in topics"
          :key="topic.exercise_id ? 'exercise-' + topic.exercise_id : 'topic-' + topic.id"
          class="topic-card"
        >
          <div class="topic-meta">
            <span class="topic-icon"><HubIcon name="practice" /></span>
            <span>{{ topic.source === 'teacher' ? 'Teacher exercise' : 'Starter practice' }}</span>
          </div>
          <p class="topic-subject">{{ topic.learning_area }}</p>
          <h3>{{ topic.name }}</h3>
          <p v-if="topic.sub_strand && topic.sub_strand !== topic.name" class="muted">{{ topic.sub_strand }}</p>
          <p class="muted">{{ topic.question_count }} {{ topic.question_count === 1 ? 'question' : 'questions' }}</p>
          <button type="button" :disabled="busy" @click="openAttempt(topic.id, undefined, topic.exercise_id || undefined)">
            Play or resume <HubIcon name="arrow" />
          </button>
        </article>
      </div>

      <section class="history" aria-labelledby="history-heading">
        <h2 id="history-heading">Recent rounds</h2>
        <p v-if="!data.history.length" class="muted">Your completed quizzes will appear here.</p>
        <article v-for="item in data.history" :key="item.id">
          <div>
            <h3>{{ item.topic }}</h3>
            <time :datetime="item.completed_at">{{ new Date(item.completed_at).toLocaleDateString() }}</time>
          </div>
          <strong>{{ item.correct_count }} / {{ item.total }} correct</strong>
          <button type="button" class="secondary" :disabled="busy" @click="openAttempt(undefined, item.id)">Review</button>
        </article>
      </section>
    </template>

    <section v-if="attempt" ref="panel" class="quiz-panel" tabindex="-1" aria-labelledby="practice-heading" :aria-busy="busy">
      <div class="quiz-top">
        <h1 id="practice-heading">{{ attempt.exercise_title || attempt.sub_strand }}</h1>
        <button type="button" class="secondary" :disabled="busy" @click="backToTopics">Back to quizzes</button>
      </div>

      <template v-if="showResults">
        <div class="results-hero">
          <div class="score-ring" :style="{ '--quiz-progress': accuracy + '%' }">
            <div><strong>{{ correct }} / {{ attempt.questions.length }}</strong><span>correct</span></div>
          </div>
          <div>
            <h2>{{ accuracy === 100 ? 'Perfect round!' : 'Round complete' }}</h2>
            <p>{{ accuracy }}% accuracy · {{ correct * 100 }} practice points</p>
            <p class="muted">Practice scores are separate from teacher ratings.</p>
          </div>
        </div>
        <div class="result-actions">
          <button type="button" :disabled="busy" @click="openAttempt(attempt.sub_strand_id, undefined, attempt.exercise_id || undefined)">{{ busy ? 'Opening…' : 'Play again' }}</button>
          <button type="button" class="secondary" :disabled="busy" @click="backToTopics">Choose another quiz</button>
        </div>
        <h2>Review your answers</h2>
        <details v-for="(item, index) in attempt.review" :key="index" class="review-item">
          <summary>
            <span>{{ index + 1 }}. {{ item.question }}</span>
            <span :class="['answer-tag', { incorrect: !item.is_correct }]">{{ item.is_correct ? 'Correct' : 'Missed' }}</span>
          </summary>
          <p>Your answer: {{ item.selected_answer }}</p>
          <p>Correct answer: <strong>{{ item.correct_answer }}</strong></p>
          <p>{{ item.feedback }}</p>
        </details>
      </template>

      <template v-else-if="current">
        <div class="score-strip">
          <strong>Question {{ currentIndex + 1 }} of {{ attempt.questions.length }}</strong>
          <span>{{ correct * 100 }} points</span>
          <span v-if="streak">{{ streak }} in a row</span>
        </div>
        <div
          class="progress-track"
          role="progressbar"
          aria-label="Questions answered"
          :aria-valuenow="attempt.answered.length"
          :aria-valuemax="attempt.questions.length"
        ><span :style="{ width: progress + '%' }" /></div>

        <div :key="current.id" class="question-stage">
          <form @submit.prevent="checkAnswer">
            <fieldset :disabled="busy || !!feedback">
              <legend>{{ current.question }}</legend>
              <div class="answer-grid">
                <label
                  v-for="(option, choice) in current.options"
                  :key="choice"
                  :class="['answer-option', {
                    chosen: !feedback && selection === choice,
                    correct: feedback?.is_correct && feedback.selected_index === choice,
                    incorrect: feedback && !feedback.is_correct && feedback.selected_index === choice,
                  }]"
                >
                  <input v-model="selection" type="radio" :name="'question-' + current.id" :value="choice" required>
                  <span class="choice-letter" aria-hidden="true">{{ String.fromCharCode(65 + choice) }}</span>
                  <span>{{ option }}</span>
                </label>
              </div>
            </fieldset>
            <div v-if="!feedback" class="quiz-actions">
              <span class="muted">Choose one answer.</span>
              <button class="check-button" :disabled="busy || selection === null">{{ busy ? 'Checking…' : 'Check answer' }}</button>
            </div>
          </form>

          <div v-if="feedback" :class="['feedback', { incorrect: !feedback.is_correct }]">
            <div role="status">
              <h2>{{ feedback.is_correct ? 'Correct! +100 points' : 'Good try' }}</h2>
              <p v-if="!feedback.is_correct">Correct answer: <strong>{{ feedback.correct_answer }}</strong></p>
              <p>{{ feedback.feedback }}</p>
            </div>
            <button type="button" :disabled="busy" @click="nextQuestion">
              {{ attempt.completed ? 'See my results' : 'Next question' }} <HubIcon name="arrow" />
            </button>
          </div>
        </div>
      </template>
    </section>
  </main>
</template>

<style scoped>
.practice-page { --quiz-ink: #2b1c40; --quiz-accent: #7036aa; --quiz-line: #e5d9ef; color: var(--quiz-ink); }
.workspace .workspace-content > .practice-page { background: transparent; padding: 0; border: 0; box-shadow: none; }
.back-link { display: inline-block; margin-bottom: 16px; font-weight: 650; }
.studio-header {
  position: relative; isolation: isolate; overflow: hidden; display: grid; grid-template-columns: minmax(0, 1fr) auto;
  align-items: center; gap: 24px; padding: clamp(24px, 4vw, 52px); border-radius: 24px;
  background: radial-gradient(ellipse at 94% 10%, #a375dd, transparent 55%), #59308b; color: white;
}
.studio-header h1 { margin: 0; color: white; font-size: clamp(2rem, 5vw, 3.75rem); letter-spacing: -.045em; }
.studio-header p { max-width: 39ch; margin: 12px 0 0; color: #f5edff; }
.studio-mark {
  display: grid; place-items: center; width: clamp(70px, 13vw, 148px); aspect-ratio: 1;
  border: 1px solid #ffffff65; border-radius: 30%; background: #ffffff1e; transform: rotate(-8deg);
  box-shadow: 0 0 0 14px #ffffff10, 0 0 0 30px #ffffff0a;
}
.studio-mark svg { width: 50%; }
.library-heading, .quiz-top, .score-strip, .quiz-actions, .result-actions, .feedback {
  display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
}
.library-heading { margin: 26px 0 14px; }
.library-heading h2, .library-heading label, .quiz-top h1 { margin: 0; }
.library-heading label { display: flex; align-items: center; gap: 10px; }
.library-heading select { width: auto; max-width: 100%; }
.topic-grid, .answer-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
.topic-card, .quiz-panel, .history, .empty-state {
  padding: clamp(18px, 3vw, 30px); background: white; border: 1px solid var(--quiz-line); border-radius: 20px;
  box-shadow: 0 10px 24px #37205b0b;
}
.topic-card { display: flex; flex-direction: column; align-items: flex-start; transition: border-color 180ms, box-shadow 180ms, transform 180ms var(--portal-ease); }
.topic-meta { display: flex; align-items: center; gap: 10px; color: #58446c; font-size: .875rem; }
.topic-icon { display: grid; place-items: center; width: 40px; height: 40px; border-radius: 12px; background: #f0e4fb; color: var(--quiz-accent); flex-shrink: 0; }
.topic-icon svg { width: 21px; }
.topic-subject { margin: 16px 0 2px; color: var(--quiz-accent); font-weight: 650; }
.topic-card h3 { margin: 0 0 8px; font-size: 1.375rem; overflow-wrap: anywhere; }
.topic-card p.muted { margin: 0 0 8px; }
.topic-card button, .feedback button { display: inline-flex; justify-content: center; align-items: center; gap: 10px; }
.topic-card button { margin: auto 0 0; }
.topic-card button svg, .feedback button svg { width: 18px; }
.muted, .history time { color: #655374; font-size: .9375rem; }
.history { margin-top: 24px; }
.history h2, .history h3, .history button { margin: 0; }
.history article { display: flex; align-items: center; flex-wrap: wrap; gap: 12px; padding: 14px 0; border-top: 1px solid var(--quiz-line); }
.history article:first-of-type { margin-top: 16px; }
.history article > div { flex: 1 1 180px; }
.quiz-panel { max-width: 1040px; margin-inline: auto; scroll-margin-top: 18px; }
.quiz-panel:focus-visible, .library-heading h2:focus-visible { outline: 2px solid var(--quiz-accent); outline-offset: 4px; }
.quiz-top { padding-bottom: 20px; border-bottom: 1px solid var(--quiz-line); }
.quiz-top button, .quiz-actions button, .feedback button, .result-actions button { margin: 0; }
.score-strip { justify-content: flex-start; margin: 20px 0 10px; }
.score-strip > strong { margin-inline-end: auto; }
.score-strip > span { background: #f2e9fa; padding: 6px 12px; border-radius: 20px; color: #5c357e; }
.progress-track { height: 10px; overflow: hidden; border-radius: 20px; background: #eee7f5; }
.progress-track > span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #7340ae, #9871e2); transition: width 300ms var(--portal-ease); }
.question-stage, .feedback, .results-hero { animation: page-arrival 240ms var(--portal-ease) both; }
fieldset { border: 0; padding: 0; margin: 24px 0; min-width: 0; }
legend { font-size: clamp(1.375rem, 3vw, 2rem); font-weight: 750; line-height: 1.35; margin-bottom: 20px; width: 100%; }
.answer-option {
  display: flex; align-items: center; gap: 12px; min-height: 84px; padding: 15px; margin: 0;
  border: 2px solid var(--quiz-line); border-radius: 14px; cursor: pointer; overflow-wrap: anywhere;
  transition: border-color 180ms, background-color 180ms, box-shadow 180ms;
}
.choice-letter { display: grid; place-items: center; width: 32px; height: 32px; flex-shrink: 0; border-radius: 8px; background: #efe5fa; color: #5f3387; }
.answer-option input { margin: 0; flex-shrink: 0; }
.answer-option.chosen { background: #f5edff; border-color: #8146be; box-shadow: 0 0 0 2px #8146be24; }
.answer-option:focus-within { outline: 2px solid var(--quiz-accent); outline-offset: 3px; }
.answer-option.correct { background: #edf8f2; border-color: #3c8a65; }
.answer-option.incorrect { background: #fff4e5; border-color: #a46e29; }
fieldset:disabled .answer-option { cursor: default; }
.feedback { margin-top: 18px; padding: 18px; background: #edf8f2; border: 1px solid #a8cfba; border-radius: 14px; }
.feedback.incorrect { background: #fff5e6; border-color: #e4c89f; }
.feedback h2 { margin: 0; }
.feedback p { margin: 8px 0 0; }
.results-hero {
  display: flex; align-items: center; gap: clamp(16px, 4vw, 36px); flex-wrap: wrap;
  margin: 22px 0 16px; padding: clamp(18px, 3vw, 32px); border-radius: 18px; background: #f4ebfd;
}
.results-hero h2, .results-hero p { margin: 0 0 6px; }
.score-ring { display: grid; place-items: center; width: 124px; height: 124px; flex-shrink: 0; border-radius: 50%; background: conic-gradient(#824bc4 var(--quiz-progress), #dfd0ef 0); }
.score-ring > div { display: grid; place-content: center; text-align: center; width: 104px; height: 104px; border-radius: 50%; background: white; }
.score-ring strong { font-size: 1.375rem; }
.result-actions { justify-content: flex-start; }
.review-item { border-top: 1px solid var(--quiz-line); }
.review-item summary { padding: 15px 0; cursor: pointer; }
.review-item summary > span:first-child { font-weight: 650; }
.answer-tag { display: inline-block; margin-inline-start: 8px; padding: 3px 9px; border-radius: 20px; background: #e5f4eb; color: #23583e; font-size: .8125rem; font-weight: 700; }
.answer-tag.incorrect { background: #fff0d6; color: #765013; }

@media (hover: hover) {
  .topic-card:hover { transform: translateY(-3px); border-color: #cbb0e5; box-shadow: 0 14px 28px #37205b14; }
  fieldset:not(:disabled) .answer-option:not(.chosen):hover { border-color: #a980cf; background: #fcf8ff; }
}
@media (max-width: 620px) {
  .topic-grid, .answer-grid { grid-template-columns: 1fr; }
  .studio-header { gap: 10px; }
  .studio-mark { width: 55px; }
  .answer-option { min-height: 66px; padding: 12px; }
  .quiz-actions button, .feedback button { width: 100%; }
}
@media (prefers-reduced-motion: reduce) {
  .topic-card:hover { transform: none; }
}
</style>
