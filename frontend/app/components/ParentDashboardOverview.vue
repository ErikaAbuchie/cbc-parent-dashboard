<script setup lang="ts">
type Subject = { id: number; name: string; average_score: number | null; assessed_count: number; strands: { sub_strands: { competency_level: string }[] }[] }
const props = defineProps<{ subjects: Subject[]; assessments: { competency_level: string }[] }>()
const levels = [
  { code: 'EE', label: 'Exceeding expectations', color: '#9b7bf5' },
  { code: 'ME', label: 'Meeting expectations', color: '#007ddd' },
  { code: 'AE', label: 'Approaching expectations', color: '#1cabc9' },
  { code: 'BE', label: 'Below expectations', color: '#64748b' },
]
const total = computed(() => props.assessments.length)
const distribution = computed(() => {
  let offset = 0
  return levels.map(level => {
    const count = props.assessments.filter(item => item.competency_level === level.code).length
    const length = total.value ? count / total.value * 100 : 0
    const segment = { ...level, count, length, offset: -offset }
    offset += length
    return segment
  })
})
const supported = computed(() => distribution.value.filter(item => ['AE', 'BE'].includes(item.code)).reduce((sum, item) => sum + item.count, 0))
const meeting = computed(() => distribution.value.filter(item => ['EE', 'ME'].includes(item.code)).reduce((sum, item) => sum + item.count, 0))
const assessedSubjects = computed(() => props.subjects.filter(subject => subject.average_score !== null))
const supportSubjects = computed(() => props.subjects.filter(subject => subject.strands.some(strand => strand.sub_strands.some(sub => ['AE', 'BE'].includes(sub.competency_level)))))
const orderedSubjects = computed(() => [...assessedSubjects.value].sort((a, b) => a.average_score! - b.average_score!))
</script>

<template>
  <section class="parent-dashboard" aria-labelledby="dashboard-summary-title">
    <div class="dashboard-intro"><div><span class="eyebrow">THE TERM AT A GLANCE</span><h2 id="dashboard-summary-title">Your learner’s learning snapshot</h2></div><div class="dashboard-heading-actions"><NuxtLink to="/parent/report?section=subjects">View full results <HubIcon name="arrow" /></NuxtLink><slot name="actions" /></div></div>
    <div class="parent-stat-grid">
      <div class="parent-stat"><span>Subjects assessed</span><strong>{{ assessedSubjects.length }}</strong><small>Included in this report</small></div>
      <div class="parent-stat"><span>Sub-strands assessed</span><strong>{{ total }}</strong><small>Recorded competency ratings</small></div>
      <div class="parent-stat"><span>Meeting or exceeding</span><strong>{{ meeting }}</strong><small>Sub-strands rated ME or EE</small></div>
      <div class="parent-stat"><span>May need support</span><strong>{{ supported }}</strong><small>Sub-strands rated AE or BE</small></div>
    </div>
    <div class="dashboard-visual-grid">
      <section class="dashboard-panel subject-comparison" aria-labelledby="subject-comparison-title">
        <div class="panel-title"><div><span class="eyebrow">SUBJECT COMPARISON</span><h3 id="subject-comparison-title">Where to focus a closer look</h3></div><span class="scale-label">Rubric average / 4</span></div>
        <p>Subjects are ordered from the lowest recorded average. Open the subject results to understand the strands behind each score.</p>
        <div v-if="orderedSubjects.length" class="subject-bar-list">
          <NuxtLink v-for="subject in orderedSubjects" :key="subject.id" class="subject-bar-row" :to="`/parent/report?section=subjects#subject-${subject.id}`">
            <span class="subject-bar-name">{{ subject.name }}</span><span class="subject-bar-track" aria-hidden="true"><span :style="{ width: `${Math.max(0, Math.min(4, subject.average_score!)) / 4 * 100}%` }" /></span><strong>{{ subject.average_score?.toFixed(2) }}<small> / 4</small></strong><HubIcon name="arrow" />
          </NuxtLink>
        </div>
        <p v-else>No subject averages are available yet.</p>
        <p class="dashboard-chart-note">These are descriptive averages of included sub-strand ratings, not percentages or official overall grades. A shorter bar alone does not determine a support need.</p>
      </section>
      <section class="dashboard-panel rating-distribution" aria-labelledby="rating-distribution-title">
        <span class="eyebrow">COMPETENCY OVERVIEW</span><h3 id="rating-distribution-title">How the ratings are distributed</h3>
        <div class="rating-ring"><svg viewBox="0 0 120 120" aria-hidden="true"><circle cx="60" cy="60" r="48" fill="none" stroke="#f0eaf6" stroke-width="12" /><circle v-for="level in distribution" :key="level.code" cx="60" cy="60" r="48" fill="none" :stroke="level.color" stroke-width="12" pathLength="100" :stroke-dasharray="`${level.length} ${100 - level.length}`" :stroke-dashoffset="level.offset" transform="rotate(-90 60 60)" /></svg><div><strong>{{ total }}</strong><span>assessed<br>sub-strands</span></div></div>
        <ul class="distribution-legend"><li v-for="level in distribution" :key="level.code"><span class="legend-swatch" :style="{ background: level.color }" aria-hidden="true" /><span><strong>{{ level.code }}</strong> · {{ level.label }}</span><strong>{{ level.count }}</strong></li></ul>
        <p class="dashboard-chart-note">Each sub-strand contributes one rating. These counts do not show change over time.</p>
      </section>
    </div>
    <div class="dashboard-next-grid">
      <section class="dashboard-panel"><span class="eyebrow">A LITTLE EXTRA SUPPORT</span><h3>Subjects to explore together</h3><p v-if="!total">No results are available to identify support areas yet.</p><template v-else-if="supportSubjects.length"><p>These subjects contain at least one AE or BE rating. Read the strand details before choosing a home activity.</p><ul class="support-subject-list"><li v-for="subject in supportSubjects" :key="subject.id"><NuxtLink :to="`/parent/report?section=subjects#subject-${subject.id}`">{{ subject.name }} <HubIcon name="arrow" /></NuxtLink></li></ul></template><p v-else>No AE or BE ratings are recorded in the included subjects.</p><NuxtLink class="dashboard-text-link" to="/parent/report?section=guidance">Explore explanations and home tips <HubIcon name="arrow" /></NuxtLink></section>
      <section class="dashboard-panel"><span class="eyebrow">YOUR NEXT STEP</span><h3>Understand. Support. Practise.</h3><div class="dashboard-shortcuts"><NuxtLink to="/parent/report?section=trends"><HubIcon name="trend" /><span><strong>See term trends</strong><small>Compare available finalised terms.</small></span><HubIcon name="arrow" /></NuxtLink><NuxtLink to="/parent/report?section=guidance"><HubIcon name="leaf" /><span><strong>Get home guidance</strong><small>Choose a subject and strand for practical tips.</small></span><HubIcon name="arrow" /></NuxtLink><NuxtLink to="/parent/practice"><HubIcon name="practice" /><span><strong>Open learner practice</strong><small>Try activities and review completed attempts.</small></span><HubIcon name="arrow" /></NuxtLink></div></section>
    </div>
  </section>
</template>

<style scoped>
.dashboard-intro,.panel-title{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}.dashboard-intro h2{margin:8px 0}.dashboard-intro>a,.dashboard-text-link{display:inline-flex;gap:10px;align-items:center;font-weight:650;text-decoration:none}.dashboard-intro svg,.dashboard-text-link svg{width:20px;height:20px}.parent-stat-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin:24px 0}.parent-stat{padding:22px;background:#fff;border:1px solid #e8dff1;border-radius:18px;min-width:0}.parent-stat>span,.parent-stat>small{display:block}.parent-stat>span{font-weight:600;font-size:1rem;line-height:1.5}.parent-stat>strong{display:block;font-size:2.375rem;line-height:1.3;margin:10px 0;color:#7540b5}.parent-stat:last-child{background:#f6effc;border-color:#cdb3e6}.dashboard-visual-grid{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(0,1fr);gap:20px}.dashboard-panel{min-width:0;background:#fff;border:1px solid #e8dff1;border-radius:20px;padding:26px}.dashboard-panel h3{margin:10px 0 14px}.scale-label{font-size:0.875rem;color:#655374}.subject-bar-list{margin-top:24px}.subject-bar-row{display:grid;grid-template-columns:minmax(120px,1fr) minmax(80px,1fr) auto 18px;align-items:center;gap:14px;padding:16px 0;text-decoration:none;color:#392b48;border-top:1px solid #efe8f5}.subject-bar-name{font-size:1rem;font-weight:600}.subject-bar-track{height:12px;background:#f0e9f7;border-radius:20px;overflow:hidden}.subject-bar-track>span{display:block;height:100%;background:linear-gradient(90deg,#9b7bf5 0%,#007ddd 55%,#1cabc9 100%);border-radius:20px}.subject-bar-row svg{width:18px;height:18px}.subject-bar-row>strong{white-space:nowrap;font-size:1.125rem}.rating-ring{position:relative;width:min(250px,100%);aspect-ratio:1;margin:20px auto}.rating-ring svg{width:100%;height:100%}.rating-ring>div{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center}.rating-ring strong{font-size:2.375rem;color:#7540b5}.rating-ring span{font-size:1rem;line-height:1.4;color:#655374}.distribution-legend{list-style:none;padding:0;margin:20px 0}.distribution-legend li{display:grid;grid-template-columns:12px 1fr auto;align-items:start;gap:10px;margin:14px 0}.legend-swatch{width:12px;height:12px;border-radius:3px;margin-top:8px}.dashboard-next-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;margin-top:20px}.support-subject-list{list-style:none;padding:0}.support-subject-list a{display:flex;align-items:center;justify-content:space-between;padding:12px 0;gap:12px;border-bottom:1px solid #efe8f5;text-decoration:none}.support-subject-list svg{width:18px;height:18px;flex-shrink:0}.dashboard-shortcuts>a{display:flex;align-items:center;gap:14px;padding:18px 0;border-bottom:1px solid #efe8f5;text-decoration:none}.dashboard-shortcuts svg{width:22px;height:22px;flex-shrink:0}.dashboard-shortcuts>a>span{flex:1;min-width:0}.dashboard-shortcuts strong,.dashboard-shortcuts small{display:block}.dashboard-shortcuts small{margin-top:6px}.dashboard-chart-note{color:#60516e}
@media(max-width:1200px){.parent-stat-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.dashboard-visual-grid{grid-template-columns:1fr}.rating-distribution{display:grid;grid-template-columns:1fr 1fr;gap:0 24px}.rating-distribution>h3,.rating-distribution>.eyebrow,.rating-distribution>.dashboard-chart-note{grid-column:1/-1}.distribution-legend{align-self:center}}
@media(max-width:600px){.parent-stat-grid{gap:10px}.parent-stat{padding:16px 12px}.parent-stat>strong{font-size:2rem}.dashboard-panel{padding:22px 16px}.dashboard-next-grid,.rating-distribution{display:block}.dashboard-next-grid>.dashboard-panel+section{margin-top:16px}.subject-bar-row{grid-template-columns:minmax(0,1fr) auto 18px;gap:10px}.subject-bar-name{grid-column:1/-1}.subject-bar-track{min-width:0}.dashboard-intro{align-items:flex-start}}
@media print{.parent-dashboard{display:none!important}}
</style>
