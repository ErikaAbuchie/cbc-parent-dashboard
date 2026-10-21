<script setup lang="ts">
const props = defineProps<{ assessments: { competency_level: string }[] }>()
const definitions = [
  { code: 'EE', label: 'Exceeding Expectations' },
  { code: 'ME', label: 'Meeting Expectations' },
  { code: 'AE', label: 'Approaching Expectations' },
  { code: 'BE', label: 'Below Expectations' }
]
const total = computed(() => props.assessments.length)
const rows = computed(() => definitions.map(level => ({
  ...level,
  count: props.assessments.filter(assessment => assessment.competency_level === level.code).length
})))
const unrecognised = computed(() => total.value - rows.value.reduce((sum, row) => sum + row.count, 0))
</script>

<template>
  <section class="competency-summary" aria-labelledby="summary-heading">
    <h2 id="summary-heading">Competency overview</h2>
    <template v-if="total">
      <p>{{ total }} assessed {{ total === 1 ? 'sub-strand is' : 'sub-strands are' }} included in this report. Each bar counts sub-strands at that level.</p>
      <ul class="level-list">
        <li v-for="row in rows" :key="row.code">
          <div class="level-label"><span><strong>{{ row.code }}</strong> — {{ row.label }}</span><span class="count">{{ row.count }} of {{ total }}</span></div>
          <div class="bar-track" aria-hidden="true"><div class="bar" :style="{ width: `${row.count / total * 100}%` }" /></div>
        </li>
      </ul>
      <p v-if="unrecognised" role="status">{{ unrecognised }} results have an unrecognised competency level and are not counted in the four bars.</p>
      <p class="scope-note">This overview covers only the sub-strands selected by the teacher for this report. It does not show an overall grade or a change over time. Read the teacher's remarks below for context.</p>
    </template>
    <p v-else>No assessment results are available to summarise yet.</p>
  </section>
</template>

<style scoped>
.competency-summary { margin: 24px 0; padding: 20px; border: 1px solid #dce6e2; border-radius: 12px; background: #f7faf9; }
h2 { margin-top: 0; }
.level-list { list-style: none; padding: 0; margin: 20px 0; }
li + li { margin-top: 20px; }
.level-label { display: flex; justify-content: space-between; gap: 12px; line-height: 1.5; }
.count { white-space: nowrap; font-weight: 600; }
.bar-track { height: 16px; margin-top: 6px; background: #eaf3ee; border-radius: 4px; overflow: hidden; }
.bar { height: 100%; border-radius: 4px; background: linear-gradient(90deg, #12634f 0%, #258c6c 55%, #64c8a0 100%); }
.scope-note { margin-bottom: 0; font-size: 14px; }
@media print {
  .competency-summary { break-inside: avoid; background: #fff; border-color: #999; padding: 12px; }
  .bar-track, .bar { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
  .level-label { flex-direction: row; }
}
@media (max-width: 480px) { .competency-summary { padding: 14px; } .level-label { flex-direction: column; gap: 2px; } }
</style>
