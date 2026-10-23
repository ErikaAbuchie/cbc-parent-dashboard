<script setup lang="ts">
import { Chart as ChartJS, RadialLinearScale, CategoryScale, LinearScale, PointElement, LineElement, Filler, Tooltip, Legend } from 'chart.js'
import type { ChartData, ChartOptions } from 'chart.js'
import { Radar, Line } from 'vue-chartjs'
ChartJS.register(RadialLinearScale, CategoryScale, LinearScale, PointElement, LineElement, Filler, Tooltip, Legend)
const props = defineProps<{ kind: 'radar' | 'line'; labels: string[]; values: (number | null)[]; title: string }>()
const radarData = computed<ChartData<'radar'>>(() => ({ labels: props.labels.filter((_, i) => props.values[i] !== null), datasets: [{ label: props.title, data: props.values.filter((v): v is number => v !== null),
  borderColor: '#9155c9', backgroundColor: 'rgba(145, 85, 201, .16)', pointBackgroundColor: '#9155c9', pointRadius: 4, borderWidth: 2 }] }))
const lineData = computed<ChartData<'line'>>(() => ({ labels: props.labels, datasets: [{ label: props.title, data: props.values,
  borderColor: '#9155c9', backgroundColor: 'rgba(145, 85, 201, .10)', pointBackgroundColor: '#9155c9', pointRadius: 5,
  pointHoverRadius: 7, borderWidth: 2.5, tension: 0, spanGaps: false, fill: false }] }))
const radarOptions: ChartOptions<'radar'> = { responsive: true, maintainAspectRatio: false, animation: false,
  plugins: { legend: { display: false }, tooltip: { callbacks: { label: context => `${context.dataset.label}: ${Number(context.raw).toFixed(2)} / 4` } } },
  scales: { r: { min: 1, max: 4, ticks: { stepSize: 1, backdropColor: 'transparent', color: '#7a6a87' },
    grid: { color: '#e9dff2' }, angleLines: { color: '#e9dff2' }, pointLabels: { color: '#604475', font: { size: 11 } } } } }
const lineOptions: ChartOptions<'line'> = { responsive: true, maintainAspectRatio: false, animation: false,
  plugins: { legend: { display: false }, tooltip: { callbacks: { label: context => `${Number(context.raw).toFixed(2)} / 4` } } },
  scales: { y: { min: 1, max: 4, ticks: { stepSize: 1, color: '#7a6a87' }, title: { display: true, text: 'Average rubric score / 4' }, grid: { color: '#eee6f4' } },
    x: { grid: { display: false }, ticks: { color: '#7a6a87' } } } }
</script>

<template><div class="chart-canvas"><Radar v-if="kind === 'radar'" :data="radarData" :options="radarOptions" role="img" :aria-label="title" /><Line v-else :data="lineData" :options="lineOptions" role="img" :aria-label="title" /></div></template>
<style scoped>.chart-canvas { position: relative; height: 320px; width: 100%; min-width: 0; }@media(max-width:480px) { .chart-canvas { height: 290px; } }@media print { .chart-canvas { height: 260px; } }</style>
