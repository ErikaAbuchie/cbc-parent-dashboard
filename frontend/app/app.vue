<template>
  <NuxtPage />
</template>

<style>
* { box-sizing: border-box; }
body { margin: 0; font-family: Arial, sans-serif; background: #f2f6f5; color: #18342d; }
main { max-width: 560px; margin: 8vh auto; padding: 32px; background: white; border: 1px solid #dce6e2; border-radius: 16px; }
h1 { margin-top: 12px; font-size: 30px; }
p { line-height: 1.6; }
label { display: block; font-weight: 600; margin: 20px 0 8px; }
input { width: 100%; padding: 12px; border: 1px solid #9cafa7; border-radius: 6px; font: inherit; }
button { margin-top: 24px; padding: 12px 20px; border: 0; border-radius: 6px; background: #176349; color: white; font: inherit; cursor: pointer; }
button:disabled { opacity: .65; cursor: wait; }
input:focus-visible, button:focus-visible, a:focus-visible { outline: 3px solid #e7b64d; outline-offset: 3px; }
.eyebrow { color: #176349; font-size: 14px; font-weight: bold; }
.error { color: #a32121; background: #fff1f1; padding: 12px; border-radius: 6px; }
@media (max-width: 600px) { main { margin: 24px 16px; padding: 24px; } }
</style>
