<script setup lang="ts">
import { Chart as ChartJS, RadialLinearScale, CategoryScale, LinearScale, PointElement, LineElement, Filler, Tooltip, Legend } from 'chart.js'
import type { ChartData, ChartOptions } from 'chart.js'
import { Radar, Line } from 'vue-chartjs'
ChartJS.register(RadialLinearScale, CategoryScale, LinearScale, PointElement, LineElement, Filler, Tooltip, Legend)
const props = defineProps<{ kind: 'radar' | 'line'; labels: string[]; values: (number | null)[]; title: string }>()
const radarData = computed<ChartData<'radar'>>(() => ({ labels: props.labels.filter((_, i) => props.values[i] !== null), datasets: [{ label: props.title, data: props.values.filter((v): v is number => v !== null),
  borderColor: '#159a76', backgroundColor: 'rgba(21, 154, 118, .16)', pointBackgroundColor: '#159a76', pointRadius: 4, borderWidth: 2 }] }))
const lineData = computed<ChartData<'line'>>(() => ({ labels: props.labels, datasets: [{ label: props.title, data: props.values,
  borderColor: '#159a76', backgroundColor: 'rgba(21, 154, 118, .10)', pointBackgroundColor: '#159a76', pointRadius: 5,
  pointHoverRadius: 7, borderWidth: 2.5, tension: 0, spanGaps: false, fill: false }] }))
const radarOptions: ChartOptions<'radar'> = { responsive: true, maintainAspectRatio: false, animation: false,
  plugins: { legend: { display: false }, tooltip: { callbacks: { label: context => `${context.dataset.label}: ${Number(context.raw).toFixed(2)} / 4` } } },
  scales: { r: { min: 1, max: 4, ticks: { stepSize: 1, backdropColor: 'transparent', color: '#688076' },
    grid: { color: '#deebe4' }, angleLines: { color: '#deebe4' }, pointLabels: { color: '#2c5d4c', font: { size: 11 } } } } }
const lineOptions: ChartOptions<'line'> = { responsive: true, maintainAspectRatio: false, animation: false,
  plugins: { legend: { display: false }, tooltip: { callbacks: { label: context => `${Number(context.raw).toFixed(2)} / 4` } } },
  scales: { y: { min: 1, max: 4, ticks: { stepSize: 1, color: '#688076' }, title: { display: true, text: 'Average rubric score / 4' }, grid: { color: '#e3eee7' } },
    x: { grid: { display: false }, ticks: { color: '#688076' } } } }
</script>

<template><div class="chart-canvas"><Radar v-if="kind === 'radar'" :data="radarData" :options="radarOptions" role="img" :aria-label="title" /><Line v-else :data="lineData" :options="lineOptions" role="img" :aria-label="title" /></div></template>
<style scoped>.chart-canvas { position: relative; height: 320px; width: 100%; min-width: 0; }@media(max-width:480px) { .chart-canvas { height: 290px; } }@media print { .chart-canvas { height: 260px; } }</style>
