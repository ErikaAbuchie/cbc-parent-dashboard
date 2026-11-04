<script setup lang="ts">
useHead({ title: 'Display settings | Teacher portal' })
const { data: teacher, error, refresh } = await useFetch('/api/auth/session')
if (error.value?.statusCode === 401) await navigateTo('/teacher/login', { replace: true })
</script>
<template><main><PortalDisplaySettings v-if="teacher" /><template v-else><h1>Unable to load settings</h1><p class="error" role="alert">{{ error?.data?.statusMessage || 'Please try again.' }}</p><button @click="refresh()">Retry</button></template></main></template>
