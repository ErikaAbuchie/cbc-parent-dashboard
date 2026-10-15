<script setup lang="ts">
useHead({ title: 'Teacher dashboard | CBC Parent Insight' })
const { data: teacher, error } = await useFetch('/api/auth/session')
if (error.value?.statusCode === 401) await navigateTo('/teacher/login', { replace: true })
const pending = ref(false)
const errorMessage = ref('')
async function logout() {
  pending.value = true
  errorMessage.value = ''
  try {
    await $fetch('/api/auth/logout', { method: 'POST' })
    teacher.value = null
    await navigateTo('/teacher/login', { replace: true })
  } catch (error: any) {
    errorMessage.value = error.data?.statusMessage || 'Unable to sign out. Please try again.'
  } finally { pending.value = false }
}
</script>

<template>
  <main>
    <span class="eyebrow">CBC Parent Insight</span>
    <template v-if="teacher">
      <h1>Welcome, {{ teacher.name }}</h1>
      <p>You are signed in as a teacher.</p>
      <p v-if="!teacher.school_id">Your account is awaiting a school assignment. Contact your school administrator before recording assessments.</p>
      <template v-else>
        <p>Your school account is connected. Record and review your learners' competency ratings.</p>
        <NuxtLink class="assessment-link" to="/teacher/assessments">Enter assessments</NuxtLink>
        <NuxtLink class="assessment-link" to="/teacher/students">Manage students</NuxtLink>
      </template>
      <p v-if="errorMessage" class="error" role="alert">{{ errorMessage }}</p>
      <button :disabled="pending" @click="logout">{{ pending ? 'Signing out…' : 'Sign out' }}</button>
    </template>
    <template v-else>
      <h1>Unable to load your dashboard</h1>
      <p class="error" role="alert">{{ error?.data?.statusMessage || 'Please try again.' }}</p>
      <NuxtLink to="/teacher/login">Return to login</NuxtLink>
    </template>
  </main>
</template>

<style scoped>
.assessment-link { display: block; color: #176349; font-weight: bold; margin: 24px 0 0; }
</style>
