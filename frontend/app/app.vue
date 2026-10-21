<script setup lang="ts">
import './assets/css/main.css'
const route = useRoute()
const workspace = computed(() => /^\/(teacher|parent)\//.test(route.path) && !route.path.endsWith('/login'))
</script>
<template><WorkspaceShell v-if="workspace"><NuxtPage /></WorkspaceShell><div v-else class="public-shell"><NuxtLink to="/" class="public-brand"><HubBrand /></NuxtLink><NuxtPage /><footer class="public-footer">A little insight. A stronger learning journey.</footer></div></template>

<style>html { min-height: 100%; }</style>
