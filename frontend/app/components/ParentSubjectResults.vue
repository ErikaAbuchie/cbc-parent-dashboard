<script setup lang="ts">
type Sub = { id: number; name: string; competency_level: string; remarks: string | null }
type Strand = { id: number; name: string; summary_level: string | null; average_score: number | null; assessed_count: number; sub_strands: Sub[] }
type Subject = { id: number; name: string; summary_level: string | null; average_score: number | null; assessed_count: number; strands: Strand[] }
defineProps<{ subjects: Subject[]; method: string; demo?: boolean }>()
const levels: Record<string, string> = { EE: 'Exceeding Expectations', ME: 'Meeting Expectations', AE: 'Approaching Expectations', BE: 'Below Expectations' }
const allOpen = ref(false)
const panels = ref<HTMLElement | null>(null)
function expandAll(open: boolean) {
  allOpen.value = open
  panels.value?.querySelectorAll('details').forEach(detail => { detail.open = open })
}
function preparePrint() { panels.value?.querySelectorAll('details').forEach(detail => { detail.setAttribute('data-was-open', String(detail.open)); detail.open = true }) }
function restorePrint() { panels.value?.querySelectorAll('details').forEach(detail => { if (detail.hasAttribute('data-was-open')) { detail.open = detail.getAttribute('data-was-open') === 'true'; detail.removeAttribute('data-was-open') } }) }
onMounted(() => { window.addEventListener('beforeprint', preparePrint); window.addEventListener('afterprint', restorePrint) })
onBeforeUnmount(() => { window.removeEventListener('beforeprint', preparePrint); window.removeEventListener('afterprint', restorePrint) })
</script>

<template>
  <section ref="panels" class="subject-results" aria-labelledby="subject-results-title">
    <div class="subject-heading"><div><span class="eyebrow">TERM RESULTS BY LEARNING AREA</span><h2 id="subject-results-title">Subjects and competency ratings</h2></div><button v-if="subjects.length" class="details-toggle" type="button" @click="expandAll(!allOpen)">{{ allOpen ? 'Collapse all' : 'Expand all details' }}</button></div>
    <p v-if="demo" class="demo-note"><strong>Demonstration report:</strong> includes simulated ratings added for a full-term preview. These are not verified learner results.</p>
    <p class="summary-method">{{ method }} Summaries cover only included assessments. Subjects contain strands, and each strand contains sub-strands.</p>
    <details class="rubric-guide"><summary>How the KNEC rubric levels are used</summary><p>EE (4): exceeds the competency requirements. ME (3): meets the requirements. AE (2): is progressing towards them. BE (1): needs additional time and support. Teachers select levels using the relevant assessment criteria and descriptors.</p><p>An average such as 2.79 / 4 summarises recorded ratings. It is not a percentage or an automatically assessed overall level. The previous prototype cutoffs have been removed.</p><p><a href="https://www.knec.ac.ke/wp-content/uploads/2021/06/CBA-Age-Based-Regular-Training-Manual.pdf" target="_blank" rel="noopener noreferrer">KNEC rubric and reporting guidance (pages 38–40, 77–79)</a></p></details>
    <p v-if="!subjects.length">No assessed subjects are included in this report yet.</p>
    <details v-for="subject in subjects" :key="subject.id" class="subject-panel">
      <summary><span class="disclosure-arrow" aria-hidden="true">›</span><span class="subject-name"><strong>{{ subject.name }}</strong><small>{{ subject.strands.length }} strands · {{ subject.assessed_count }} assessed sub-strands</small></span><span class="grade"><strong>{{ subject.average_score?.toFixed(2) || '—' }} / 4</strong><span>Average rubric score</span><small>Overall performance level not assessed</small></span></summary>
      <div class="strand-list"><details v-for="strand in subject.strands" :key="strand.id" class="strand-panel"><summary><span class="disclosure-arrow" aria-hidden="true">›</span><span class="strand-name"><strong>{{ strand.name }}</strong><small>{{ strand.assessed_count }} assessed sub-strands · View ratings</small></span><span class="strand-grade">{{ strand.average_score?.toFixed(2) || '—' }} / 4<small>Average rubric score</small></span></summary>
        <div class="sub-list"><article v-for="sub in strand.sub_strands" :key="sub.id"><div class="sub-row"><h4>{{ sub.name }}</h4><span class="sub-level" :class="`grade-${sub.competency_level.toLowerCase()}`"><strong>{{ sub.competency_level }}</strong> — {{ levels[sub.competency_level] }}</span></div><p v-if="sub.remarks">{{ sub.remarks }}</p></article></div>
      </details></div>
    </details>
  </section>
</template>

<style scoped>
.subject-results { margin-top: 32px; }.rubric-guide { border: 1px solid #dce9e0; border-radius: 10px; background: #f5faf6; margin: 14px 0; }.rubric-guide summary { padding: 14px; font-weight: 600; font-size: 13px; }.rubric-guide p { padding: 0 14px; font-size: 13px; color: #4b695a; }.subject-heading { display: flex; gap: 16px; justify-content: space-between; align-items: center; flex-wrap: wrap; }h2 { margin-top: 8px; }.details-toggle { margin: 0 0 12px; background: #e9f5ed; color: #216748; font-size: 12px; }.summary-method { color: #61796e; font-size: 13px; }.demo-note { padding: 14px; background: #fff4dc; border: 1px solid #f0d69d; border-radius: 10px; color: #805c18; font-size: 13px; }
.subject-panel { border: 1px solid #dce9e0; border-radius: 14px; margin: 14px 0; overflow: hidden; background: white; }summary { list-style: none; cursor: pointer; display: flex; gap: 14px; align-items: center; padding: 20px; }summary::-webkit-details-marker { display: none; }summary:hover { background: #f4faf6; }.disclosure-arrow { font-size: 28px; color: #5b9474; line-height: 1; transform: rotate(0); flex-shrink: 0; }details[open]>summary>.disclosure-arrow { transform: rotate(90deg); }.subject-name, .strand-name { flex: 1; min-width: 0; }.subject-name strong { font-size: 17px; }.subject-name small, .strand-name small { display: block; font-size: 12px; margin-top: 7px; }.grade { display: grid; grid-template-columns: auto 1fr; align-items: center; gap: 4px 8px; padding: 10px 13px; border-radius: 9px; font-size: 12px; background: #eaf5ec; color: #2d7050; }.grade>strong { font-size: 19px; }.grade small { grid-column: 1/-1; color: inherit; opacity: .85; font-size: 10px; }.grade-ae { background: #fff3da; color: #865f12; }.grade-be { background: #fceceb; color: #a3433f; }.grade-me { background: #e7f3f5; color: #326f7c; }.strand-list { padding: 0 20px 18px; }.strand-panel { border-top: 1px solid #e5eee8; }.strand-panel summary { padding: 17px 0; }.strand-name strong { font-size: 14px; }.strand-grade { padding: 7px 10px; background: #f0f6f1; border-radius: 7px; font-weight: 650; text-align: center; }.strand-grade small { display: block; font-size: 9px; font-weight: 400; margin-top: 3px; }.sub-list { padding: 0 0 8px 28px; }.sub-list article { border-top: 1px dashed #deebe1; padding: 14px 0; break-inside: avoid; }.sub-row { display: flex; gap: 12px; justify-content: space-between; align-items: start; flex-wrap: wrap; }h4 { margin: 0; font-size: 14px; flex: 1; min-width: 140px; }.sub-level { font-size: 11px; padding: 6px 8px; border-radius: 6px; }.sub-list p { margin-bottom: 0; font-size: 12px; color: #6f8176; overflow-wrap: anywhere; }
@media(max-width:600px) { .subject-panel>summary { flex-wrap: wrap; padding: 16px 14px; gap: 10px; }.subject-name { flex-basis: calc(100% - 34px); }.grade { margin-left: 24px; }.subject-name strong { font-size: 16px; }.strand-list { padding: 0 14px 12px; }.sub-list { padding-left: 12px; }.sub-row { flex-direction: column; }.strand-name strong { font-size: 13px; } }
@media print { .details-toggle { display: none; }summary { padding: 12px; }summary::marker, .disclosure-arrow { display: none; }.grade, .strand-grade, .sub-level { color: black; background: white; }.subject-panel { break-inside: auto; }.sub-list { padding-left: 8px; }.subject-panel>summary, .strand-panel>summary { break-after: avoid; } }
</style>
