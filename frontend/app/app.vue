<script setup lang="ts">
import './assets/css/main.css'
import './assets/css/portal.css'
import './assets/css/interaction.css'
const route = useRoute()
const workspace = computed(() => /^\/(teacher|parent)\//.test(route.path) && !route.path.endsWith('/login'))
</script>
<template>
  <WorkspaceShell v-if="workspace">
    <NuxtPage :transition="{ name: 'page-arrival' }" />
  </WorkspaceShell>
  <div v-else class="public-shell">
    <NuxtLink to="/" class="public-brand"><HubBrand /></NuxtLink>
    <NuxtPage :transition="{ name: 'page-arrival' }" />
  </div>
</template>

<style>html { min-height: 100%; }</style>
