<script setup lang="ts">
type Point = { term: string; average_score: number | null; assessed_count: number; has_demo_results: boolean }
type Subject = { id: number; name: string; average_score: number | null; assessed_count: number }
type Trend = { year: number | null; terms: string[]; subjects: { id: number; name: string; points: Point[] }[] }
const props = defineProps<{ subjects: Subject[]; trend: Trend }>()
const selectedId = ref('')
watch(() => props.trend.subjects, (subjects) => {
  if (!subjects.some(subject => String(subject.id) === selectedId.value)) selectedId.value = String((subjects.find(subject => subject.name === 'Mathematics') || subjects[0])?.id || '')
}, { immediate: true })
const selected = computed(() => props.trend.subjects.find(subject => String(subject.id) === selectedId.value))
const available = computed(() => selected.value?.points.filter(point => point.average_score !== null).length || 0)
const scoredSubjects = computed(() => props.subjects.filter(subject => subject.average_score !== null))
const aliases: Record<string, string> = { Mathematics: 'Maths', 'Science and Technology': 'Science & Tech', 'Christian Religious Education': 'CRE', 'Hindu Religious Education': 'HRE', 'Islamic Religious Education': 'IRE', 'Creative Arts': 'Creative Arts', 'Social Studies': 'Social Studies' }
const radarLabels = computed(() => scoredSubjects.value.map(subject => aliases[subject.name] || subject.name))
const radarValues = computed(() => scoredSubjects.value.map(subject => subject.average_score))
const trendLabels = computed(() => selected.value?.points.map(point => point.term.replace(/ 20[0-9]{2}$/, '')) || [])
const trendValues = computed(() => selected.value?.points.map(point => point.average_score) || [])
</script>

<template>
  <section class="report-charts" aria-label="Subject overview and term trends">
    <section class="chart-card" aria-labelledby="radar-heading"><span class="eyebrow">THE CURRENT TERM</span><h2 id="radar-heading">Subject overview</h2><p>Compare average rubric scores across the subjects included in this report.</p>
      <ReportChart v-if="scoredSubjects.length >= 3" kind="radar" :labels="radarLabels" :values="radarValues" title="Subject average rubric scores out of 4; values listed below" />
      <p v-else class="empty-chart">The radar needs at least three assessed subjects. Available scores are listed below.</p>
      <details class="chart-data"><summary>View subject chart data</summary><ul><li v-for="subject in scoredSubjects" :key="subject.id"><span>{{ subject.name }}</span><strong>{{ subject.average_score?.toFixed(2) }} / 4</strong></li></ul></details>
      <p class="chart-note">These are descriptive averages on a 1–4 scale, not percentages or official overall grades.</p>
    </section>
    <section class="chart-card" aria-labelledby="trend-heading"><span class="eyebrow">LEARNING OVER TIME</span><h2 id="trend-heading">Term-by-term trend<span v-if="trend.year"> · {{ trend.year }}</span></h2><label for="trend-subject">Choose a subject</label><select id="trend-subject" v-model="selectedId" :disabled="!trend.subjects.length"><option v-if="!trend.subjects.length" value="">No subject available</option><option v-for="subject in trend.subjects" :key="subject.id" :value="String(subject.id)">{{ subject.name }}</option></select>
      <ReportChart v-if="available" kind="line" :labels="trendLabels" :values="trendValues" :title="`${selected?.name} term averages out of 4; values listed below`" />
      <p v-if="available < 2" class="empty-chart">{{ available === 1 ? 'Only one term is available. A trend line needs at least two finalised terms.' : 'No finalised term results are available for this subject yet.' }}</p>
      <div v-if="selected" class="trend-values"><div v-for="point in selected.points" :key="point.term"><span>{{ point.term }}</span><strong>{{ point.average_score === null ? 'Not available' : `${point.average_score.toFixed(2)} / 4` }}</strong><small>{{ point.assessed_count }} assessed sub-strands<span v-if="point.has_demo_results"> · Includes demo data</span></small></div></div>
      <p class="chart-note">Uses this learner's finalised reports from the same teacher, up to the displayed term. Topic coverage may differ between terms. Gaps mean unavailable results, not zero.</p>
    </section>
  </section>
</template>

<style scoped>
.report-charts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; margin: 24px 0; }.chart-card { border: 1px solid #dce9e0; border-radius: 14px; padding: 22px; min-width: 0; background: white; }.chart-card h2 { margin-top: 8px; font-size: 20px; }.chart-card p { font-size: 13px; color: #607b6e; }.chart-card label { font-size: 12px; }.chart-card select { margin-bottom: 12px; }.chart-note { font-size: 12px !important; line-height: 1.6; }.chart-data { margin-top: 14px; }.chart-data summary { cursor: pointer; color: #14765a; padding: 10px 0; font-size: 13px; font-weight: 600; }.chart-data ul { list-style: none; padding: 0; }.chart-data li { display: flex; justify-content: space-between; gap: 10px; padding: 8px 0; border-bottom: 1px solid #e5eee8; font-size: 12px; }.chart-data strong { white-space: nowrap; }.trend-values { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 8px; }.trend-values>div { padding: 10px; border-radius: 8px; background: #f2f8f4; }.trend-values span, .trend-values strong, .trend-values small { display: block; line-height: 1.5; }.trend-values span { font-size: 11px; color: #567464; }.trend-values strong { font-size: 13px; margin: 5px 0; }.trend-values small { font-size: 10px; }.empty-chart { padding: 14px; border: 1px dashed #c9dfd1; border-radius: 10px; }
@media(max-width:1000px) { .report-charts { grid-template-columns: 1fr; } }@media(max-width:480px) { .chart-card { padding: 18px 12px; }.trend-values { grid-template-columns: 1fr; }.trend-values>div { display: flex; flex-wrap: wrap; gap: 8px; justify-content: space-between; }.trend-values small { flex-basis: 100%; }.trend-values strong { margin: 0; } }
@media print { .report-charts { display: block; }.chart-card { break-inside: avoid; margin-bottom: 16px; }.chart-data { display: block; }.chart-data>ul { display: block; }.chart-card select { border: 0; padding: 0; }.chart-data summary { color: black; } }
</style>
