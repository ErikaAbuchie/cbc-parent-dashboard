<script setup lang="ts">
useHead({ title: 'Parent report access | CBC Parent Insight' })
const admission = ref('')
const code = ref('')
const busy = ref(false)
const errorMessage = ref('')
async function verify() {
  if (busy.value) return
  busy.value = true; errorMessage.value = ''
  try {
    await $fetch('/api/parent/verify', { method: 'POST', body: { admission_number: admission.value.trim(), code: code.value.trim() } })
    code.value = ''
    await navigateTo('/parent/report')
  } catch (error: any) { errorMessage.value = error.data?.statusMessage || 'Unable to verify the code. Please try again.' }
  finally { busy.value = false }
}
</script>

<template><main><span class="eyebrow">CBC Parent Insight</span><h1>View your child's report</h1><p>Enter the learner's admission number and the six-digit code sent by SMS. No account is needed.</p><form @submit.prevent="verify"><label for="admission">Admission number</label><input id="admission" v-model="admission" maxlength="50" autocomplete="off" required :disabled="busy"><label for="access-code">Six-digit access code</label><input id="access-code" v-model="code" inputmode="numeric" type="password" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code" required :disabled="busy"><p v-if="errorMessage" class="error" role="alert">{{ errorMessage }}</p><button :disabled="busy">{{ busy ? 'Checking…' : 'View report' }}</button></form><p>Codes expire after 30 days. Ask the school for a new code if yours has expired or been replaced.</p><NuxtLink to="/">Return home</NuxtLink></main></template>
