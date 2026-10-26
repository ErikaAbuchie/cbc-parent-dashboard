<script setup lang="ts">
import { Chart as ChartJS, RadialLinearScale, CategoryScale, LinearScale, PointElement, LineElement, Filler, Tooltip, Legend } from 'chart.js'
import type { ChartData, ChartOptions } from 'chart.js'
import { Radar, Line } from 'vue-chartjs'
ChartJS.register(RadialLinearScale, CategoryScale, LinearScale, PointElement, LineElement, Filler, Tooltip, Legend)
const props = defineProps<{ kind: 'radar' | 'line'; labels: string[]; values: (number | null)[]; title: string }>()
const radarData = computed<ChartData<'radar'>>(() => ({ labels: props.labels.filter((_, i) => props.values[i] !== null), datasets: [{ label: props.title, data: props.values.filter((v): v is number => v !== null),
  borderColor: '#9155c9', backgroundColor: 'rgba(145, 85, 201, .16)', pointBackgroundColor: '#9155c9', pointRadius: 4, borderWidth: 2 }] }))
const lineData = computed<ChartData<'line'>>(() => ({ labels: props.labels, datasets: [{ label: props.title, data: props.values,
  borderColor: '#356ba8', backgroundColor: 'rgba(53, 107, 168, .10)', pointBackgroundColor: '#356ba8', pointRadius: 5,
  pointHoverRadius: 7, borderWidth: 2.5, tension: 0, spanGaps: false, fill: false }] }))
const radarOptions: ChartOptions<'radar'> = { responsive: true, maintainAspectRatio: false, animation: false,
  plugins: { legend: { display: false }, tooltip: { titleFont: { size: 15 }, bodyFont: { size: 14 }, callbacks: { label: context => `${context.dataset.label}: ${Number(context.raw).toFixed(2)} / 4` } } },
  scales: { r: { min: 1, max: 4, ticks: { stepSize: 1, backdropColor: 'transparent', color: '#60516e', font: { size: 14 } },
    grid: { color: '#e9dff2' }, angleLines: { color: '#e9dff2' }, pointLabels: { color: '#604475', font: { size: 14 } } } } }
const lineOptions: ChartOptions<'line'> = { responsive: true, maintainAspectRatio: false, animation: false,
  plugins: { legend: { display: false }, tooltip: { titleFont: { size: 15 }, bodyFont: { size: 14 }, callbacks: { label: context => `${Number(context.raw).toFixed(2)} / 4` } } },
  scales: { y: { min: 1, max: 4, ticks: { stepSize: 1, color: '#60516e', font: { size: 14 } }, title: { display: true, text: 'Average rubric score / 4', color: '#60516e', font: { size: 14 } }, grid: { color: '#eee6f4' } },
    x: { grid: { display: false }, ticks: { color: '#60516e', font: { size: 14 } } } } }
</script>

<template><div class="chart-canvas"><Radar v-if="kind === 'radar'" :data="radarData" :options="radarOptions" role="img" :aria-label="title" /><Line v-else :data="lineData" :options="lineOptions" role="img" :aria-label="title" /></div></template>
<style scoped>.chart-canvas { position: relative; height: 320px; width: 100%; min-width: 0; }@media(max-width:480px) { .chart-canvas { height: 290px; } }@media print { .chart-canvas { height: 260px; } }</style>
