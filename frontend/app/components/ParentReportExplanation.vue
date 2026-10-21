<script setup lang="ts">
type Explanation = { source: 'model' | 'fallback' | 'empty'; model: string | null; summary: string; activity: string; level_guide: { code: string; count: number; meaning: string }[] }
const result = ref<Explanation | null>(null)
const loading = ref(false)
const message = ref('')
let cancelled = false
onBeforeUnmount(() => { cancelled = true })
async function explain() {
  if (loading.value) return
  loading.value = true; message.value = ''
  try {
    const response = await $fetch<Explanation>('/api/parent/explanation', { method: 'POST', timeout: 60000 })
    if (!cancelled) result.value = response
  } catch (error: any) {
    if (cancelled) return
    if (error.statusCode === 401 || error.response?.status === 401) {
      await navigateTo('/parent/login', { replace: true }); return
    }
    message.value = error.data?.statusMessage || 'The explanation is unavailable. You can still read the results and teacher remarks.'
  } finally { loading.value = false }
}
</script>

<template>
  <section class="explanation" aria-labelledby="explanation-heading" :aria-busy="loading">
    <h2 id="explanation-heading">Understanding these results</h2>
    <p class="controls">Request a plain-language explanation and one suggested practice activity. It may take up to a minute.</p>
    <button class="controls" type="button" :disabled="loading" @click="explain">{{ loading ? 'Preparing explanation…' : result ? 'Try explanation again' : 'Explain these results' }}</button>
    <p v-if="loading" class="controls" role="status">Preparing your explanation. Your recorded results remain available below.</p>
    <p v-if="message" class="error controls" role="alert">{{ message }}</p>
    <div v-if="result" aria-live="polite">
      <h3>Recorded rating guide</h3>
      <ul><li v-for="level in result.level_guide" :key="level.code">{{ level.meaning }} ({{ level.count }} {{ level.count === 1 ? 'sub-strand' : 'sub-strands' }})</li></ul>
      <p class="source"><strong>{{ result.source === 'model' ? `AI-generated explanation · ${result.model}` : result.source === 'empty' ? 'No results to explain' : 'Standard guidance · AI unavailable' }}</strong></p>
      <p class="generated-text">{{ result.summary }}</p>
      <template v-if="result.activity"><h3>Suggested home practice</h3><p class="generated-text">{{ result.activity }}</p></template>
      <p class="note">Generated suggestions can be inaccurate. The teacher's ratings and remarks are the source of truth. Ask the teacher if the explanation does not match the results.</p>
    </div>
  </section>
</template>

<style scoped>
.explanation { margin: 24px 0; padding: 20px; border: 1px solid #dce6e2; border-radius: 12px; }
h2 { margin-top: 0; }
li { line-height: 1.6; margin-bottom: 8px; }
.generated-text { white-space: pre-wrap; overflow-wrap: anywhere; }
.note { font-size: 14px; }
@media print { .controls { display: none !important; } .explanation { border-color: #999; } h2, h3 { break-after: avoid; } }
</style>
