<script setup lang="ts">
type PracticeHistory = { id: number; topic: string; completed_at: string; correct_count: number; total: number }
const { data, error, status, refresh } = await useFetch<{ history: PracticeHistory[] }>('/api/parent/practice')
watch(error, async (value) => {
  if (value?.statusCode === 401) await navigateTo('/parent/login', { replace: true })
}, { immediate: true })
function completedDate(value: string) {
  return new Intl.DateTimeFormat('en-KE', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Africa/Nairobi' }).format(new Date(value))
}
</script>

<template>
  <section class="practice-results" aria-labelledby="practice-results-heading" :aria-busy="status === 'pending'">
    <h2 id="practice-results-heading">Recent learner practice results</h2>
    <p>Quiz marks are practice feedback. They do not change the teacher's EE, ME, AE or BE ratings.</p>
    <p v-if="status === 'pending'" role="status">Loading practice results…</p>
    <div v-else-if="error" class="error" role="alert"><p>{{ error.data?.statusMessage || 'Cannot load practice results.' }}</p><button class="practice-actions" @click="refresh()">Retry practice results</button></div>
    <template v-else>
      <p v-if="!data?.history.length">No completed practice attempts for the topics in this report yet.</p>
      <template v-else>
        <p>Up to five most recently completed attempts for the topics currently available in this report. Dates are shown in Kenya time.</p>
        <ol class="attempts"><li v-for="item in data.history" :key="item.id">
          <h3>{{ item.topic }}</h3><p class="mark"><strong>{{ item.correct_count }} of {{ item.total }} correct</strong></p>
          <p>Completed: <time :datetime="item.completed_at">{{ completedDate(item.completed_at) }}</time> (EAT)</p>
          <NuxtLink class="practice-actions" :to="{ path: '/parent/practice', query: { attempt: item.id } }" :aria-label="`Review answers for ${item.topic}, attempt ${item.id}`">Review answers</NuxtLink>
        </li></ol>
      </template>
      <p class="practice-actions"><NuxtLink to="/parent/practice">Open learner practice</NuxtLink></p>
      <button class="practice-actions" type="button" @click="refresh()">Refresh practice results</button>
    </template>
  </section>
</template>

<style scoped>
.practice-results { margin: 24px 0; padding: 20px; border: 1px solid #dce6e2; border-radius: 12px; }
h2 { margin-top: 0; }
.attempts { list-style: none; padding: 0; }
li { border-top: 1px solid #dce6e2; padding: 12px 0; overflow-wrap: anywhere; }
h3 { margin-bottom: 8px; }.mark { margin: 8px 0; }
@media print { .practice-actions { display: none !important; } li { break-inside: avoid; } .practice-results { border-color: #999; } }
</style>
