<script setup lang="ts">
defineProps<{ learnerName: string }>()
</script>

<template>
  <section class="parent-welcome" aria-labelledby="parent-welcome-heading">
    <HubLandscape class="welcome-landscape" />
    <div class="welcome-copy">
      <span class="eyebrow">GROWING TOGETHER, ONE STEP AT A TIME</span>
      <h2 id="parent-welcome-heading">Welcome back.</h2>
      <p>Here to support <strong>{{ learnerName }}</strong>.<br>Celebrate progress and discover the next step together.</p>
      <a class="welcome-link" href="#report-overview">Explore the report <HubIcon name="arrow" /></a>
    </div>
  </section>
</template>

<style scoped>
.parent-welcome { position: relative; isolation: isolate; overflow: hidden; min-height: 270px; margin-bottom: 22px; border: 1px solid #d9e9df; border-radius: 20px; background: linear-gradient(110deg,#edf8ee,#e0f2ed); }
.welcome-landscape { position: absolute; z-index: -2; right: 0; bottom: 0; width: 76%; height: 100%; opacity: .85; animation: landscape-settle 4.5s ease-out both; }
.parent-welcome::after { content: ''; position: absolute; z-index: -1; inset: 0; pointer-events: none; background: linear-gradient(90deg,#edf8eef7,#edf8eed9 36%,#edf8ee20 75%); }
.welcome-copy { position: relative; padding: 32px; max-width: 640px; animation: welcome-arrive .8s ease-out both; }
.eyebrow { display: block; font-size: 11px; letter-spacing: 1.6px; line-height: 1.7; }
.welcome-copy h2 { margin: 17px 0; font-size: clamp(29px,3.4vw,42px); line-height: 1.15; letter-spacing: -1px; color: #123c37; }
.welcome-copy p { max-width: 440px; color: #567b6c; font-size: 14px; line-height: 1.8; overflow-wrap: anywhere; }.welcome-copy strong { color: #315d4c; font-weight: 600; }
.welcome-link { display: inline-flex; align-items: center; gap: 14px; margin-top: 10px; color: #007c62; font-size: 13px; font-weight: 700; text-decoration: none; }.welcome-link svg { width: 18px; height: 18px; transition: transform .25s ease; }.welcome-link:hover svg { transform: translateX(3px); }.welcome-link:focus-visible { outline: 2px solid #16785b; outline-offset: 6px; border-radius: 3px; }
.welcome-landscape :deep(circle) { transform-box: fill-box; transform-origin: center; animation: sunshine 4.5s ease-in-out both; }
.welcome-landscape :deep(ellipse) { transform-box: fill-box; transform-origin: center bottom; animation: gentle-sway 4.5s ease-in-out both; }
@keyframes welcome-arrive { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
@keyframes landscape-settle { from { transform: translateX(7px); } to { transform: translateX(0); } }
@keyframes sunshine { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .8; transform: scale(1.045); } }
@keyframes gentle-sway { 0%,100% { transform: rotate(0); } 35% { transform: rotate(1.5deg); } 70% { transform: rotate(-1deg); } }
@media(max-width:600px) { .parent-welcome { min-height: 280px; border-radius: 16px; }.welcome-copy { padding: 26px 20px; }.eyebrow { font-size: 9px; letter-spacing: 1.1px; }.welcome-copy h2 { font-size: 31px; }.welcome-copy p { max-width: 300px; font-size: 13px; }.welcome-landscape { width: 100%; opacity: .5; }.parent-welcome::after { background: linear-gradient(90deg,#edf8eef5,#edf8eec9 55%,#edf8ee55); } }
@media(prefers-reduced-motion:reduce) { .welcome-copy,.welcome-landscape,.welcome-landscape :deep(circle),.welcome-landscape :deep(ellipse) { animation: none; }.welcome-link svg { transition: none; }.welcome-link:hover svg { transform: none; } }
@media print { .parent-welcome { display: none; } }
</style>
