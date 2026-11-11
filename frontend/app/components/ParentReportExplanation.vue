<script setup lang="ts">
import { homeActivity } from '../utils/homeActivities'

type Topic = { id: number; name: string; competency_level: string }
type Strand = { id: number; name: string; sub_strands: Topic[] }
type Subject = { id: number; name: string; strands: Strand[] }
type Level = 'EE' | 'ME' | 'AE' | 'BE'

const props = defineProps<{ subjects: Subject[] }>()
const language = ref<'en' | 'sw'>('en')
const showSummary = ref(false)
const showTips = ref(false)
const subjectId = ref('')
const strandId = ref('')
const levels: Level[] = ['EE', 'ME', 'AE', 'BE']

const copy = {
  en: {
    title: 'Understand and support your learner', language: 'Guidance language',
    summary: 'Understand the results', showSummary: 'Explain these results',
    guide: 'What the ratings mean', results: 'Recorded results by subject and strand',
    details: 'See individual ratings', supportHeading: 'Strands that could use more practice',
    noSupport: 'All assessed skills meet or exceed expectations.',
    approaching: (skills: string) => `Your child is close to the expected level in ${skills}. Practise these skills to build confidence.`,
    support: (skills: string) => `Start with ${skills}. Your child is below the expected level in these skills and may benefit from a worked example and guided practice.`,
    ratingCount: (count: number) => `Assessed skills (${count})`,
    context: { EE: 'Your child went beyond what was expected on this skill. Try a small challenge to build on that strength.', ME: 'Your child showed what was expected on this skill. Try a new example to check they can use it in a different situation.', AE: 'Your child is close to what was expected on this skill. Practise one example together, then let them try one on their own.', BE: 'Your child needs more help with this skill. Show one simple example first, then guide them through another.' },
    tips: 'Help at home', subject: 'Subject', strand: 'Strand', showTips: 'Get home tips',
    empty: 'There are no recorded ratings for this selection.',
    source: 'Guidance based on recorded teacher ratings',
    note: 'Subject and skill names appear as recorded in the report.',
    meanings: { EE: 'Exceeding expectations', ME: 'Meeting expectations', AE: 'Approaching expectations', BE: 'Below expectations' },
    practice: (skill: string) => `Find a worked example of “${skill}” in your child’s schoolbook. Go through it together and ask your child to explain one step. Let them try a similar example, then check it together. If you cannot find an example, ask the teacher to suggest one.`,
    extend: (skill: string) => `Ask your child to show you an example of “${skill}” from school. Ask them to make a new example and explain how they know it is correct.`,
  },
  sw: {
    title: 'Elewa matokeo na umsaidie mtoto wako', language: 'Lugha ya mwongozo',
    summary: 'Elewa matokeo', showSummary: 'Eleza matokeo haya',
    guide: 'Maana ya viwango', results: 'Matokeo yaliyorekodiwa kwa kila somo na mada',
    details: 'Angalia kiwango cha kila stadi', supportHeading: 'Mada zinazohitaji mazoezi zaidi',
    noSupport: 'Katika stadi zote zilizotathminiwa, mtoto wako amefikia au amezidi kiwango kinachotarajiwa.',
    approaching: (skills: string) => `Mtoto wako anakaribia kiwango kinachotarajiwa katika stadi hizi: ${skills}. Mazoezi zaidi yanaweza kumsaidia.`,
    support: (skills: string) => `Anzeni na stadi hizi: ${skills}. Mtoto wako bado hajafikia kiwango kinachotarajiwa. Mwonyeshe mfano, kisha msaidie kujaribu mwingine.`,
    ratingCount: (count: number) => `Stadi zilizotathminiwa (${count})`,
    context: { EE: 'Mtoto wako amezidi kiwango kilichotarajiwa katika stadi hii. Mpe zoezi lenye changamoto kidogo.', ME: 'Mtoto wako amefikia kiwango kilichotarajiwa katika stadi hii. Mpe mfano mpya ili uone kama anaweza kutumia stadi hiyo katika hali tofauti.', AE: 'Mtoto wako anakaribia kiwango kinachotarajiwa katika stadi hii. Fanyeni mfano mmoja pamoja, kisha mwache ajaribu mwingine peke yake.', BE: 'Mtoto wako anahitaji msaada zaidi katika stadi hii. Mwonyeshe mfano mmoja rahisi kwanza, kisha mwongoze katika mfano mwingine.' },
    tips: 'Mazoezi ya nyumbani', subject: 'Somo', strand: 'Mada', showTips: 'Pata mapendekezo ya mazoezi',
    empty: 'Hakuna matokeo yaliyorekodiwa kwa chaguo hili.',
    source: 'Mwongozo unatokana na viwango vilivyorekodiwa na mwalimu',
    note: 'Majina ya masomo na stadi yameandikwa kama yalivyo kwenye ripoti.',
    meanings: { EE: 'Amezidi kiwango kinachotarajiwa', ME: 'Amefikia kiwango kinachotarajiwa', AE: 'Anakaribia kiwango kinachotarajiwa', BE: 'Bado hajafikia kiwango kinachotarajiwa' },
    practice: (skill: string) => `Tafuta mfano uliofanyiwa kazi wa “${skill}” katika kitabu cha mtoto wako. Pitieni mfano huo pamoja na umwombe aeleze hatua moja. Mwache ajaribu mfano mwingine unaofanana, kisha kagueni jibu pamoja. Usipopata mfano, mwombe mwalimu akupendekezee mmoja.`,
    extend: (skill: string) => `Mwombe mtoto wako akuonyeshe mfano wa “${skill}” kutoka shuleni. Mwombe atoe mfano mwingine na aeleze jinsi anavyojua kuwa ni sahihi.`,
  },
}
const t = computed(() => copy[language.value])
function assessed(topic: Topic): topic is Topic & { competency_level: Level } {
  return levels.some(level => level === topic.competency_level)
}
const subjects = computed(() => props.subjects.map(subject => ({
  ...subject,
  strands: subject.strands.map(strand => ({
    ...strand, sub_strands: strand.sub_strands.filter(assessed),
  })).filter(strand => strand.sub_strands.length),
})).filter(subject => subject.strands.length))
function ratingBreakdown(topics: Topic[]) {
  return levels.map(level => `${level} ${topics.filter(topic => topic.competency_level === level).length}`).join(' · ')
}
function supportReason(topics: Topic[]) {
  const format = new Intl.ListFormat(language.value, { type: 'conjunction' })
  const names = (level: Level) => format.format(topics.filter(topic => topic.competency_level === level).map(topic => topic.name))
  const below = names('BE')
  const approaching = names('AE')
  return [below && t.value.support(below), approaching && t.value.approaching(approaching)].filter(Boolean).join(' ')
}
const supportStrands = computed(() => subjects.value.flatMap(subject => subject.strands
  .map(strand => ({ id: strand.id, subject: subject.name, strand: strand.name, reason: supportReason(strand.sub_strands) }))
  .filter(strand => strand.reason)))
const strands = computed(() => subjects.value.find(subject => String(subject.id) === subjectId.value)?.strands ?? [])
const selectedStrand = computed(() => strands.value.find(strand => String(strand.id) === strandId.value))
const focus = computed(() => {
  const topics = selectedStrand.value?.sub_strands ?? []
  for (const level of ['BE', 'AE', 'ME', 'EE']) {
    const topic = topics.find(item => item.competency_level === level)
    if (topic) return topic
  }
  return undefined
})
const activity = computed(() => {
  const topic = focus.value
  if (!topic) return ''
  const subject = subjects.value.find(item => String(item.id) === subjectId.value)
  const specificActivity = homeActivity(subject?.name ?? '', topic.name, language.value)
  if (specificActivity) return specificActivity
  if (topic.competency_level === 'EE' || topic.competency_level === 'ME') return t.value.extend(topic.name)
  return t.value.practice(topic.name)
})
watch(subjects, values => {
  if (!values.some(subject => String(subject.id) === subjectId.value)) {
    subjectId.value = String(values[0]?.id ?? '')
  }
}, { immediate: true })
watch(strands, values => {
  if (!values.some(strand => String(strand.id) === strandId.value)) {
    strandId.value = String(values[0]?.id ?? '')
  }
  showTips.value = false
}, { immediate: true })
watch(strandId, () => { showTips.value = false })
</script>

<template>
  <section class="explanation" :lang="language" aria-labelledby="explanation-heading">
    <h2 id="explanation-heading">{{ t.title }}</h2>
    <div class="controls">
      <label for="guidance-language">{{ t.language }}</label>
      <select id="guidance-language" v-model="language">
        <option value="en" lang="en">English</option>
        <option value="sw" lang="sw">Kiswahili</option>
      </select>
    </div>
    <p class="note">{{ t.note }}</p>
    <div class="ai-grid">
      <section class="ai-card" aria-labelledby="summary-heading">
        <h3 id="summary-heading">{{ t.summary }}</h3>
        <button class="controls" type="button" @click="showSummary = true">{{ t.showSummary }}</button>
        <div v-if="showSummary" aria-live="polite">
          <h4>{{ t.guide }}</h4>
          <ul><li v-for="level in levels" :key="level"><strong>{{ level }}</strong> — {{ t.meanings[level] }}</li></ul>
          <h4>{{ t.results }}</h4>
          <p v-if="!subjects.length">{{ t.empty }}</p>
          <section v-for="subject in subjects" :key="subject.id" class="subject-guidance">
            <h5>{{ subject.name }}</h5>
            <p>{{ t.ratingCount(subject.strands.flatMap(strand => strand.sub_strands).length) }} · {{ ratingBreakdown(subject.strands.flatMap(strand => strand.sub_strands)) }}</p>
            <details v-for="strand in subject.strands" :key="strand.id" class="strand-summary">
              <summary>{{ strand.name }} · {{ ratingBreakdown(strand.sub_strands) }}</summary>
              <p>{{ t.details }}</p>
              <ul>
                <li v-for="topic in strand.sub_strands" :key="topic.id">
                  {{ topic.name }} · <strong>{{ topic.competency_level }}</strong> — {{ t.meanings[topic.competency_level] }}
                </li>
              </ul>
            </details>
          </section>
          <section v-if="subjects.length" class="support-subjects">
            <h4>{{ t.supportHeading }}</h4>
            <p v-if="!supportStrands.length">{{ t.noSupport }}</p>
            <ul v-else>
              <li v-for="strand in supportStrands" :key="strand.id">
                <strong>{{ strand.subject }} · {{ strand.strand }}</strong>
                <p>{{ strand.reason }}</p>
              </li>
            </ul>
          </section>
        </div>
      </section>
      <section class="ai-card" aria-labelledby="tips-heading">
        <h3 id="tips-heading">{{ t.tips }}</h3>
        <div class="controls">
          <label for="tips-subject">{{ t.subject }}</label>
          <select id="tips-subject" v-model="subjectId" :disabled="!subjects.length">
            <option v-for="subject in subjects" :key="subject.id" :value="String(subject.id)">{{ subject.name }}</option>
          </select>
          <label for="tips-strand">{{ t.strand }}</label>
          <select id="tips-strand" v-model="strandId" :disabled="!strands.length">
            <option v-for="strand in strands" :key="strand.id" :value="String(strand.id)">{{ strand.name }}</option>
          </select>
          <button type="button" :disabled="!focus" @click="showTips = true">{{ t.showTips }}</button>
        </div>
        <p v-if="!focus">{{ t.empty }}</p>
        <div v-if="showTips && focus" aria-live="polite">
          <p class="source">{{ t.source }}</p>
          <h4>{{ focus.name }}</h4>
          <p><strong>{{ focus.competency_level }}</strong> — {{ t.meanings[focus.competency_level] }}</p>
          <p>{{ t.context[focus.competency_level] }}</p>
          <p>{{ activity }}</p>
        </div>
      </section>
    </div>
  </section>
</template>

<style scoped>
.explanation{margin:24px 0;padding:24px;border:1px solid #dce6e2;border-radius:16px;background:#f4f9f5}h2{margin-top:10px}.ai-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.ai-card{min-width:0;background:white;border:1px solid #dfebe3;border-radius:12px;padding:22px}.ai-card h3{margin-top:0}.ai-card p,li{line-height:1.7;font-size:14px}li{margin-bottom:10px}ul{padding-left:20px}.generated-text{white-space:pre-wrap;overflow-wrap:anywhere}.source,.count{font-size:12px;color:#536e60}.source{margin-top:22px;font-weight:650}.note{font-size:12px;line-height:1.6;margin-bottom:0}.ai-card button{margin-top:12px}.ai-card select{margin-bottom:14px}@media(max-width:850px){.ai-grid{grid-template-columns:1fr}}@media(max-width:480px){.explanation{padding:18px 12px}.ai-card{padding:18px 14px}.ai-card button{width:100%}}@media print{.controls{display:none!important}.ai-grid{display:block}.ai-card{break-inside:avoid;margin-bottom:14px}.explanation{background:white;border-color:#999}h2,h3,h4{break-after:avoid}}
</style>

<style scoped>
.subject-guidance{border-bottom:1px solid #d9e6dd;margin:16px 0;padding:4px 0 12px}.subject-guidance h5{font-size:16px;margin:8px 0}.strand-summary{margin:12px 0;color:#385b49}.support-subjects{background:#f0f6ed;border:1px solid #d5e4cf;border-radius:10px;padding:16px;margin:20px 0}.support-subjects h4{margin-top:0}.ai-grid{align-items:start}@media print{.subject-guidance{break-inside:avoid}.support-subjects{background:white}}
</style>
