<script setup lang="ts">
type Topic = { id: number; name: string; learning_area: string; competency_level: string }
type History = { id: number; topic: string; completed_at: string; correct_count: number; total: number }
type Attempt = { id: number; sub_strand: string; completed: boolean; questions: { id: number; question: string; options: string[] }[];
  correct_count?: number; total?: number; review?: { question: string; selected_answer: string; correct_answer: string; is_correct: boolean; feedback: string }[] }
useHead({ title: 'Learner practice | CBC Parent Insight' })
const { data, error, refresh } = await useFetch<{ topics: Topic[]; history: History[] }>('/api/parent/practice')
if (error.value?.statusCode === 401) await navigateTo('/parent/login', { replace: true })
const attempt = ref<Attempt | null>(null)
const answers = ref<Record<number, number>>({})
const busy = ref(false)
const message = ref('')
const panel = ref<HTMLElement | null>(null)
async function fail(error: any) {
  if (error.statusCode === 401 || error.response?.status === 401) { await navigateTo('/parent/login', { replace: true }); return }
  message.value = error.data?.statusMessage || 'Practice is unavailable. Please try again.'
}
async function openAttempt(topicId?: number, historyId?: number) {
  busy.value = true; message.value = ''
  try {
    attempt.value = historyId ? await $fetch<Attempt>(`/api/parent/practice/${historyId}`)
      : await $fetch<Attempt>('/api/parent/practice', { method: 'POST', body: { sub_strand_id: topicId } })
    answers.value = {}; await nextTick(); panel.value?.focus()
  } catch (error) { await fail(error) }
  finally { busy.value = false }
}
async function submit() {
  if (!attempt.value || busy.value) return
  busy.value = true; message.value = ''
  try {
    attempt.value = await $fetch<Attempt>(`/api/parent/practice/${attempt.value.id}/submit`, { method: 'POST',
      body: { answers: attempt.value.questions.map(q => ({ question_id: q.id, answer_index: answers.value[q.id] })) } })
    await refresh(); await nextTick(); panel.value?.focus()
  } catch (error) { await fail(error) }
  finally { busy.value = false }
}
</script>

<template>
  <main class="practice-page"><NuxtLink to="/parent/report">← Back to report</NuxtLink><h1>Learner practice</h1>
    <p>Work through a short quiz together. Practice results do not change the teacher's competency ratings.</p>
    <p>This pilot has three authored questions each for Grade 5 Mathematics Whole Numbers and Addition. Available topics must already be included in your report.</p>
    <p v-if="error" class="error" role="alert">{{ error.data?.statusMessage || 'Cannot load practice.' }} <button @click="refresh()">Retry</button></p>
    <p v-if="message" class="error" role="alert">{{ message }}</p><p v-if="busy" role="status">Working…</p>
    <template v-if="data && !attempt"><h2>Choose a topic</h2><p v-if="!data.topics.length">No practice quiz is available for the topics in this report yet.</p>
      <div v-for="topic in data.topics" :key="topic.id"><h3>{{ topic.learning_area }} · {{ topic.name }}</h3><p>Recorded competency: {{ topic.competency_level }}</p><button :disabled="busy" @click="openAttempt(topic.id)">Start or resume practice</button></div>
      <h2>Recent completed attempts</h2><p v-if="!data.history.length">No completed practice attempts yet.</p><div v-for="item in data.history" :key="item.id"><p>{{ item.topic }} · {{ item.correct_count }} of {{ item.total }} correct · {{ new Date(item.completed_at).toLocaleString() }}</p><button :disabled="busy" @click="openAttempt(undefined, item.id)">Review answers</button></div>
    </template>
    <section v-if="attempt" ref="panel" tabindex="-1" aria-labelledby="practice-heading"><h2 id="practice-heading">{{ attempt.sub_strand }}</h2>
      <template v-if="attempt.completed"><h3>Practice complete: {{ attempt.correct_count }} of {{ attempt.total }} correct</h3><p>This is practice feedback, not a new CBC competency rating.</p>
        <article v-for="(item, index) in attempt.review" :key="index"><h3>{{ index + 1 }}. {{ item.question }}</h3><p><strong>{{ item.is_correct ? 'Correct' : 'Keep practising' }}</strong></p><p>Your answer: {{ item.selected_answer }}</p><p>Correct answer: {{ item.correct_answer }}</p><p>{{ item.feedback }}</p></article>
      </template>
      <form v-else @submit.prevent="submit"><fieldset v-for="(q, index) in attempt.questions" :key="q.id" :disabled="busy"><legend>{{ index + 1 }}. {{ q.question }}</legend>
        <label v-for="(option, choice) in q.options" :key="choice"><input v-model="answers[q.id]" type="radio" :name="`question-${q.id}`" :value="choice" required>{{ option }}</label>
      </fieldset><button :disabled="busy">{{ busy ? 'Saving…' : 'Submit answers' }}</button></form>
      <button :disabled="busy" @click="attempt = null; answers = {}">Back to topics</button><p v-if="!attempt.completed">Your attempt can be resumed, but unsent answer selections are not saved.</p>
    </section>
  </main>
</template>

<style scoped>
.practice-page { max-width: 800px; }fieldset { border: 1px solid #9cafa7; border-radius: 8px; padding: 16px; margin: 24px 0; }legend { font-weight: 600; line-height: 1.5; }label { display: flex; align-items: center; gap: 12px; font-weight: normal; }input[type=radio] { width: auto; flex-shrink: 0; }article { border-top: 1px solid #dce6e2; padding-top: 12px; }section:focus { outline: 2px solid #176349; outline-offset: 8px; }
</style>
