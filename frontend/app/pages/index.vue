<script setup lang="ts">
useHead({ title: 'CBC Parent Insight' })

const portals = [
  { to: '/parent/login', title: 'I’m a parent', description: 'View your learner’s report with your SMS access code.', icon: 'learners' },
  { to: '/teacher/login', title: 'I’m a teacher', description: 'Record assessments and share progress with families.', icon: 'assessment' },
]
</script>

<template>
  <main class="welcome-page">
    <div class="landing-feature">
      <div class="landing-copy">
        <h1><span>See how your</span><span>learner is growing.</span></h1>
        <p>Follow progress at school and find ways to support learning at home.</p>
      </div>
      <div class="landing-art">
        <img src="/images/colour-pencils.png" alt="" fetchpriority="high">
      </div>
    </div>
    <div class="portal-choices">
      <NuxtLink v-for="portal in portals" :key="portal.to" :to="portal.to">
        <span class="portal-icon"><HubIcon :name="portal.icon" /></span>
        <span><strong>{{ portal.title }}</strong><small>{{ portal.description }}</small></span>
        <HubIcon name="arrow" />
      </NuxtLink>
    </div>
  </main>
</template>

<style scoped>
.public-shell .welcome-page {
  max-width: 72rem;
  padding: 0;
  overflow: hidden;
}

.landing-feature {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  background: #f4ebfb;
}

.landing-copy { padding: clamp(1.5rem, 5vw, 4.5rem); }
.public-shell .landing-copy h1 {
  margin: 0;
  color: #31174a;
  font-size: clamp(2rem, 4.8vw, 4.4rem);
  line-height: 1.04;
  letter-spacing: -.055em;
}
.landing-copy h1 span { display: block; animation: headline-arrival 460ms var(--portal-ease) both; }
.landing-copy h1 span + span { color: #713ca5; animation-delay: 75ms; }
.landing-copy p { max-width: 35ch; margin: 1.25rem 0 0; color: #584467; font-size: clamp(1rem, 1.4vw, 1.2rem); }

.landing-art { position: relative; min-height: 13rem; overflow: hidden; background: #b194df; }
.landing-art img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: center bottom; }

.portal-choices { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
.portal-choices a {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: clamp(1.15rem, 3vw, 2rem);
  color: #573174;
  text-decoration: none;
}
.portal-choices a + a { border-inline-start: 1px solid #e8dbf2; }
.portal-choices a > svg { width: 1.25rem; margin-inline-start: auto; flex-shrink: 0; }
.portal-choices strong { display: block; font-size: 1.15rem; }
.portal-choices small { display: block; margin-top: .35rem; color: #604e6f; font-size: .9375rem; line-height: 1.45; }
.portal-icon { display: grid; place-items: center; width: 2.75rem; aspect-ratio: 1; flex-shrink: 0; border-radius: 12px; background: #eee1fa; }
.portal-icon svg { width: 1.4rem; }

@keyframes headline-arrival {
  from { opacity: 0; transform: translateY(.45em); }
  to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 680px) {
  .landing-feature, .portal-choices { grid-template-columns: 1fr; }
  .landing-art { min-height: clamp(7rem, 30vw, 11rem); }
  .portal-choices a + a { border-inline-start: 0; border-top: 1px solid #e8dbf2; }
}
</style>
