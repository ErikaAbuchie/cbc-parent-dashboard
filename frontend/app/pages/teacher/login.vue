<script setup lang="ts">
useHead({ title: 'Teacher login | CBC Parent Insight' })
const email = ref('')
const password = ref('')
const pending = ref(false)
const errorMessage = ref('')
async function login() {
  if (pending.value) return
  pending.value = true
  errorMessage.value = ''
  try {
    await $fetch('/api/auth/login', { method: 'POST', body: { email: email.value.trim(), password: password.value } })
    password.value = ''
    await navigateTo('/teacher/dashboard')
  } catch (error: any) {
    errorMessage.value = error.data?.statusMessage || 'Unable to sign in. Please try again.'
  } finally { pending.value = false }
}
</script>

<template>
  <main>
    <span class="eyebrow">CBC Parent Insight</span>
    <h1>Teacher login</h1>
    <p>Sign in with the teacher account provided by your school.</p>
    <form @submit.prevent="login">
      <label for="email">Email address</label>
      <input id="email" v-model="email" type="email" autocomplete="username" maxlength="255" required :disabled="pending">
      <label for="password">Password</label>
      <input id="password" v-model="password" type="password" autocomplete="current-password" maxlength="255" required :disabled="pending">
      <p v-if="errorMessage" class="error" role="alert">{{ errorMessage }}</p>
      <button type="submit" :disabled="pending">{{ pending ? 'Signing in…' : 'Sign in' }}</button>
    </form>
  </main>
</template>
