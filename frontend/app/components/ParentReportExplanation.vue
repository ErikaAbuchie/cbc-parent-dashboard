<script setup lang="ts">
import { homeActivity } from '../utils/homeActivities'

type Topic = { id: number; name: string; competency_level: string }
type Strand = { id: number; name: string; sub_strands: Topic[] }
type Subject = { id: number; name: string; strands: Strand[] }
type Level = 'EE' | 'ME' | 'AE' | 'BE'
type GuidanceMode = 'summary' | 'home_tips'

const props = defineProps<{ subjects: Subject[]; reportId?: number }>()
const language = ref<'en' | 'sw'>('en')
const showSummary = ref(false)
const showTips = ref(false)
const subjectId = ref('')
const strandId = ref('')
const levels: Level[] = ['EE', 'ME', 'AE', 'BE']
const introductions = reactive({ summary: '', home_tips: '' })
const generatedActivity = ref('')
const pending = reactive({ summary: false, home_tips: false })
const requests: Partial<Record<GuidanceMode, AbortController>> = {}

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
    context: {
      EE: 'This activity gives your child a small challenge to build on what they already do well.',
      ME: 'This activity helps your child use what they learned in class at home.',
      AE: 'Start this activity together so your child can build confidence with the skill.',
      BE: 'Show your child how to start this activity, then guide them as they try it.',
    },
    tips: 'Help at home', subject: 'Subject', strand: 'Strand', showTips: 'Get home tips',
    empty: 'There are no recorded ratings for this selection.',
    source: 'Guidance based on recorded teacher ratings',
    summaryIntro: 'The report shows how your child is doing in each subject and which topics could use more practice.',
    noSupportIntro: 'The report shows how your child is doing in each subject. The assessed topics meet or exceed expectations.',
    preparing: 'Preparing an AI introduction…',
    modelIntro: 'Introduction written with AI',
    modelActivity: 'Activity returned by AI',
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
    context: {
      EE: 'Zoezi hili litampa mtoto wako changamoto ndogo ili aendeleze stadi anayoimudu vizuri.',
      ME: 'Zoezi hili litamsaidia mtoto wako kutumia nyumbani alichojifunza darasani.',
      AE: 'Anzeni zoezi hili pamoja ili mtoto wako ajenge kujiamini katika stadi hii.',
      BE: 'Mwonyeshe mtoto wako jinsi ya kuanza zoezi hili, kisha mwongoze anapolijaribu.',
    },
    tips: 'Mazoezi ya nyumbani', subject: 'Somo', strand: 'Mada', showTips: 'Pata mapendekezo ya mazoezi',
    empty: 'Hakuna matokeo yaliyorekodiwa kwa chaguo hili.',
    source: 'Mwongozo unatokana na viwango vilivyorekodiwa na mwalimu',
    summaryIntro: 'Ripoti inaonyesha jinsi mtoto wako anavyofanya katika kila somo na mada ambazo anahitaji kuzifanyia mazoezi zaidi.',
    noSupportIntro: 'Ripoti inaonyesha jinsi mtoto wako anavyofanya katika kila somo. Katika mada zilizotathminiwa, mtoto wako amefikia au amezidi matarajio.',
    preparing: 'Utangulizi wa AI unaandaliwa…',
    modelIntro: 'Utangulizi umeandikwa kwa msaada wa AI',
    modelActivity: 'Zoezi limetolewa na AI',
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
const specificActivity = computed(() => {
  const topic = focus.value
  if (!topic) return ''
  const subject = subjects.value.find(item => String(item.id) === subjectId.value)
  return homeActivity(subject?.name ?? '', topic.name, language.value)
})
const activity = computed(() => {
  const topic = focus.value
  if (!topic) return ''
  if (specificActivity.value) return specificActivity.value
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
function clearIntroduction(mode: GuidanceMode) {
  requests[mode]?.abort()
  delete requests[mode]
  introductions[mode] = ''
  if (mode === 'home_tips') generatedActivity.value = ''
  pending[mode] = false
}

async function requestIntroduction(mode: GuidanceMode) {
  clearIntroduction(mode)
  const home = mode === 'home_tips'
  if (home ? !focus.value : !subjects.value.length) return
  const reference = home ? specificActivity.value : undefined
  const request = new AbortController()
  requests[mode] = request
  pending[mode] = true
  try {
    const result = await $fetch<{ source: string; introduction: string | null; activity?: string | null }>('/api/parent/explanation', {
      method: 'POST',
      signal: request.signal,
      body: {
        report_id: props.reportId,
        mode,
        language: language.value,
        ...(home ? { subject_id: Number(subjectId.value), strand_id: Number(strandId.value), activity_reference: reference || undefined } : {}),
      },
    })
    if (requests[mode] === request && result.source === 'model') {
      introductions[mode] = result.introduction ?? ''
      if (reference && result.activity === reference) generatedActivity.value = result.activity
    }
  } catch {
    // The written introduction is already visible.
  } finally {
    if (requests[mode] === request) {
      delete requests[mode]
      pending[mode] = false
    }
  }
}

function openGuidance(mode: GuidanceMode) {
  if (mode === 'summary') showSummary.value = true
  else showTips.value = true
  void requestIntroduction(mode)
}

watch([subjectId, strandId, subjects], () => {
  showTips.value = false
  clearIntroduction('home_tips')
})
watch(subjects, () => clearIntroduction('summary'))
watch(language, () => {
  if (showSummary.value) void requestIntroduction('summary')
  if (showTips.value) void requestIntroduction('home_tips')
})
onBeforeUnmount(() => {
  clearIntroduction('summary')
  clearIntroduction('home_tips')
})
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
        <button class="controls" type="button" :disabled="pending.summary" @click="openGuidance('summary')">{{ t.showSummary }}</button>
        <div v-if="showSummary" aria-live="polite">
          <h4>{{ t.guide }}</h4>
          <ul><li v-for="level in levels" :key="level"><strong>{{ level }}</strong> — {{ t.meanings[level] }}</li></ul>
          <template v-if="subjects.length">
            <p>{{ introductions.summary || (supportStrands.length ? t.summaryIntro : t.noSupportIntro) }}</p>
            <p v-if="pending.summary || introductions.summary" class="note">{{ pending.summary ? t.preparing : t.modelIntro }}</p>
          </template>
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
          <button type="button" :disabled="!focus || pending.home_tips" @click="openGuidance('home_tips')">{{ t.showTips }}</button>
        </div>
        <p v-if="!focus">{{ t.empty }}</p>
        <div v-if="showTips && focus" aria-live="polite">
          <p class="source">{{ t.source }}</p>
          <h4>{{ focus.name }}</h4>
          <p><strong>{{ focus.competency_level }}</strong> — {{ t.meanings[focus.competency_level] }}</p>
          <p>{{ introductions.home_tips || t.context[focus.competency_level] }}</p>
          <p v-if="pending.home_tips || introductions.home_tips" class="note">{{ pending.home_tips ? t.preparing : t.modelIntro }}</p>
          <p>{{ generatedActivity || activity }}</p>
          <p v-if="generatedActivity" class="note">{{ t.modelActivity }}</p>
        </div>
      </section>
    </div>
  </section>
</template>

<style scoped>
.explanation{margin:24px 0;padding:24px;border:1px solid #e2d8ea;border-radius:16px;background:#f7f4f9}h2{margin-top:10px}.ai-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.ai-card{min-width:0;background:white;border:1px solid #e6dded;border-radius:12px;padding:22px}.ai-card h3{margin-top:0}.ai-card p,li{line-height:1.7;font-size:14px}li{margin-bottom:10px}ul{padding-left:20px}.generated-text{white-space:pre-wrap;overflow-wrap:anywhere}.source,.count{font-size:12px;color:#643f82}.source{margin-top:22px;font-weight:650}.note{font-size:12px;line-height:1.6;margin-bottom:0}.ai-card button{margin-top:12px}.ai-card select{margin-bottom:14px}@media(max-width:850px){.ai-grid{grid-template-columns:1fr}}@media(max-width:480px){.explanation{padding:18px 12px}.ai-card{padding:18px 14px}.ai-card button{width:100%}}@media print{.controls{display:none!important}.ai-grid{display:block}.ai-card{break-inside:avoid;margin-bottom:14px}.explanation{background:white;border-color:#999}h2,h3,h4{break-after:avoid}}
</style>

<style scoped>
.subject-guidance{border-bottom:1px solid #e0d6e9;margin:16px 0;padding:4px 0 12px}.subject-guidance h5{font-size:16px;margin:8px 0}.strand-summary{margin:12px 0;color:#4c2e65}.support-subjects{background:#f2eef5;border:1px solid #dbcee5;border-radius:10px;padding:16px;margin:20px 0}.support-subjects h4{margin-top:0}.ai-grid{align-items:start}@media print{.subject-guidance{break-inside:avoid}.support-subjects{background:white}}
</style>
