<script setup lang="ts">
defineProps<{ name: string }>()
const paths: Record<string, string> = {
  home: 'm3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z',
  learners: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.87M16 3a4 4 0 0 1 0 8M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z',
  assessment: 'M9 4H5v17h14V4h-4M9 3h6v4H9Zm-1 9h8m-8 4h6',
  report: 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Zm0 0v6h6M8 13h8m-8 4h5',
  practice: 'm9 11 3 3 8-9M20 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9',
  menu: 'M4 6h16M4 12h16M4 18h16', close: 'm6 6 12 12M6 18 18 6',
  arrow: 'M5 12h14m-6-6 6 6-6 6', lock: 'M7 10V7a5 5 0 0 1 10 0v3M5 10h14v11H5Zm7 5v2',
  leaf: 'M20 4C8 2 2 10 6 16s16 2 14-12ZM5 21l11-11',
}
</script>
<template><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path :d="paths[name] || paths.leaf" /></svg></template>
