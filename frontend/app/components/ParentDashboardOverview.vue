<script setup lang="ts">
type Subject = { id: number; name: string; average_score: number | null; assessed_count: number; strands: { sub_strands: { competency_level: string }[] }[] }
type TrendSubject = { id: number; name: string; points: { term: string; average_score: number | null }[] }
const props = defineProps<{
  subjects: Subject[]
  assessments: { competency_level: string }[]
  trend: { subjects: TrendSubject[] }
}>()
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
const order = ref('lowest')
const orderedSubjects = computed(() => [...assessedSubjects.value].sort((a, b) => {
  if (order.value === 'name') return a.name.localeCompare(b.name)
  const difference = (a.average_score ?? 0) - (b.average_score ?? 0)
  return order.value === 'highest' ? -difference : difference
}))
const activeRating = ref<string | null>(null)
const selectedRating = computed(() => distribution.value.find(level => level.code === activeRating.value))
const trendSubjectId = ref<number>()
const trendSubjects = computed(() => props.trend.subjects.filter(subject => subject.points.some(point => point.average_score !== null)))
const trendSubject = computed(() => trendSubjects.value.find(subject => subject.id === trendSubjectId.value) ?? trendSubjects.value[0])
const trendLabels = computed(() => trendSubject.value?.points.map(point => point.term.replace(/ 20\d{2}$/, '')) ?? [])
const trendScores = computed(() => trendSubject.value?.points.map(point => point.average_score) ?? [])
</script>

<template>
  <section class="parent-dashboard" aria-labelledby="dashboard-summary-title">
    <div class="dashboard-intro"><h2 id="dashboard-summary-title">Learning at a glance</h2><div class="dashboard-heading-actions"><slot name="actions" /></div></div>
    <div class="parent-stat-grid">
      <div class="parent-stat"><span>Subjects assessed</span><strong>{{ assessedSubjects.length }}</strong><small>Included in this report</small></div>
      <div class="parent-stat"><span>Sub-strands assessed</span><strong>{{ total }}</strong><small>Recorded competency ratings</small></div>
      <div class="parent-stat"><span>Meeting or exceeding</span><strong>{{ meeting }}</strong><small>Sub-strands rated ME or EE</small></div>
      <div class="parent-stat"><span>May need support</span><strong>{{ supported }}</strong><small>Sub-strands rated AE or BE</small></div>
    </div>
    <div class="dashboard-visual-grid">
      <section class="dashboard-panel trend-panel" aria-labelledby="overview-trend-title">
        <div class="panel-title">
          <h3 id="overview-trend-title">Progress across terms</h3>
          <NuxtLink class="dashboard-text-link" to="/parent/report?section=trends">Full trends <HubIcon name="arrow" /></NuxtLink>
        </div>
        <template v-if="trendSubject">
          <label class="dashboard-control">Subject
            <select :value="trendSubject.id" @change="trendSubjectId = Number(($event.target as HTMLSelectElement).value)">
              <option v-for="subject in trendSubjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
            </select>
          </label>
          <ReportChart kind="line" overview :labels="trendLabels" :values="trendScores" :title="`${trendSubject.name} term averages out of 4`" />
          <ul class="term-score-list" aria-live="polite">
            <li v-for="point in trendSubject.points" :key="point.term">
              <span>{{ point.term }}</span>
              <strong>{{ point.average_score === null ? 'Unavailable' : `${point.average_score.toFixed(2)} / 4` }}</strong>
            </li>
          </ul>
          <p class="dashboard-chart-note">Averages come from finalised reports. The topics assessed can differ by term.</p>
        </template>
        <p v-else>Term scores will appear here when a report with recorded ratings is finalised.</p>
      </section>
      <section class="dashboard-panel rating-distribution" aria-labelledby="rating-distribution-title">
        <h3 id="rating-distribution-title">Ratings breakdown</h3>
        <div class="rating-ring"><svg viewBox="0 0 120 120" aria-hidden="true"><circle cx="60" cy="60" r="48" fill="none" stroke="#f0eaf6" stroke-width="12" /><circle v-for="level in distribution.filter(item => item.count)" :key="level.code" cx="60" cy="60" r="48" fill="none" :stroke="level.color" stroke-width="12" pathLength="100" :stroke-dasharray="`${level.length} ${100 - level.length}`" :stroke-dashoffset="level.offset" :class="{ muted: activeRating && activeRating !== level.code }" transform="rotate(-90 60 60)" /></svg><div aria-live="polite"><strong>{{ selectedRating?.count ?? total }}</strong><span>{{ selectedRating ? `rated ${selectedRating.code} of ${total}` : 'assessed sub-strands' }}</span></div></div>
        <ul class="distribution-legend">
          <li v-for="level in distribution" :key="level.code">
            <button type="button" class="rating-filter" :aria-pressed="activeRating === level.code" @click="activeRating = activeRating === level.code ? null : level.code">
              <span class="legend-swatch" :style="{ background: level.color }" aria-hidden="true" />
              <span><strong>{{ level.code }}</strong> · {{ level.label }}</span>
              <strong>{{ level.count }}</strong>
            </button>
          </li>
        </ul>
      </section>
      <section class="dashboard-panel subject-comparison" aria-labelledby="subject-comparison-title">
        <div class="panel-title">
          <h3 id="subject-comparison-title">Compare subjects</h3>
          <label v-if="orderedSubjects.length" class="dashboard-control">Sort by
            <select v-model="order">
              <option value="lowest">Lowest average</option>
              <option value="highest">Highest average</option>
              <option value="name">Subject name</option>
            </select>
          </label>
        </div>
        <div v-if="orderedSubjects.length" class="subject-bar-list">
          <NuxtLink v-for="subject in orderedSubjects" :key="subject.id" class="subject-bar-row" :to="`/parent/report?section=subjects#subject-${subject.id}`">
            <span class="subject-bar-name">{{ subject.name }}</span><span class="subject-bar-track" aria-hidden="true"><span :style="{ width: `${Math.max(0, Math.min(4, subject.average_score!)) / 4 * 100}%` }" /></span><strong>{{ subject.average_score?.toFixed(2) }}<small> / 4</small></strong><HubIcon name="arrow" />
          </NuxtLink>
        </div>
        <p v-else>No subject averages are available yet.</p>
        <p class="dashboard-chart-note">These are descriptive averages of included sub-strand ratings, not percentages or official overall grades. A shorter bar alone does not determine a support need.</p>
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
.parent-dashboard { --dashboard-ink: #2e2143; --dashboard-accent: #7038ad; color: var(--dashboard-ink); }
.dashboard-intro { padding-top: 12px; }
.dashboard-intro h2 { letter-spacing: -.035em; }
.parent-stat { border-radius: 16px; border-top: 3px solid #d6c0ed; box-shadow: 0 8px 20px #39205009; }
.parent-stat:nth-child(3) { border-top-color: #4b8ad1; }
.parent-stat:last-child { border-top-color: #995dcc; }
.parent-stat > strong { font-variant-numeric: tabular-nums; }
.dashboard-panel { box-shadow: 0 8px 28px #39205009; }
.dashboard-visual-grid { grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr); align-items: start; }
.trend-panel { background: linear-gradient(155deg, #fbf7ff, #fff 60%); }
.subject-comparison { grid-column: 1 / -1; }
.dashboard-control { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; color: #59466c; font-size: 1rem; }
.dashboard-control select { width: auto; max-width: 100%; min-width: 0; min-height: 44px; margin: 0; font-size: inherit; }
.trend-panel > .dashboard-control { margin: 12px 0 18px; }
.term-score-list { list-style: none; margin: 12px 0; padding: 0; display: flex; flex-wrap: wrap; gap: 8px; }
.term-score-list li { flex: 1 1 95px; padding: 10px 12px; border-radius: 10px; background: #f1e8fb; }
.term-score-list span, .term-score-list strong { display: block; line-height: 1.5; }
.term-score-list strong { color: #633393; }
.rating-distribution { display: block; }
.rating-ring { width: min(210px, 100%); margin: 12px auto; }
.rating-ring > div { padding: 24%; }
.rating-ring circle { transition: opacity 180ms ease; }
.rating-ring .muted { opacity: .16; }
.distribution-legend li { display: block; margin: 4px 0; }
.parent-dashboard .rating-filter {
  display: grid; grid-template-columns: 12px 1fr auto; align-items: start; gap: 10px;
  width: 100%; min-height: 44px; margin: 0; padding: 8px 10px; border: 1px solid transparent;
  border-radius: 10px; color: var(--dashboard-ink); background: transparent; text-align: left; font-size: 1rem;
}
.parent-dashboard .rating-filter:hover, .parent-dashboard .rating-filter[aria-pressed="true"] {
  color: var(--dashboard-ink); background: #f4ebff; border-color: #e3d1f5;
}
.subject-bar-row { padding-inline: 8px; transition: background-color 180ms ease; }
.subject-bar-row:hover, .subject-bar-row:focus-visible { background: #f6effd; }
.subject-bar-row > strong { font-variant-numeric: tabular-nums; }
@media(max-width:1050px) { .dashboard-visual-grid { grid-template-columns: minmax(0, 1fr); } }
@media(max-width:600px) { .dashboard-control { width: 100%; } .dashboard-control select { width: 100%; } }
@media(prefers-reduced-motion:reduce) { .rating-ring circle, .subject-bar-row { transition: none; } }
@media print{.parent-dashboard{display:none!important}}
</style>
