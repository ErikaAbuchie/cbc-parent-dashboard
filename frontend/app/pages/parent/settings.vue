<script setup lang="ts">
useHead({ title: 'Display settings | Parent portal' })
const { data: report, error, refresh } = await useFetch('/api/parent/report')
if (error.value?.statusCode === 401) await navigateTo('/parent/login', { replace: true })
</script>
<template><main><PortalDisplaySettings v-if="report" /><template v-else><h1>Unable to load settings</h1><p class="error" role="alert">{{ error?.data?.statusMessage || 'Please try again.' }}</p><button @click="refresh()">Retry</button></template></main></template>
