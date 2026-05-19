<template>
  <div
    class="rounded-2xl border border-slate-200 bg-white p-5 flex flex-col items-center justify-center text-center relative overflow-hidden group shadow-sm shadow-slate-100/50 w-full"
  >
    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-3 block">
      Heuristic Risk Score
    </span>

    <div class="relative flex items-center justify-center h-28 w-28">
      <!-- SVG Circular Progress Ring -->
      <svg class="h-full w-full -rotate-90" viewBox="0 0 100 100">
        <circle
          cx="50"
          cy="50"
          r="40"
          stroke="#f1f5f9"
          stroke-width="8"
          fill="transparent"
        />
        <circle
          cx="50"
          cy="50"
          r="40"
          :stroke="strokeColor"
          stroke-width="8"
          fill="transparent"
          stroke-dasharray="251.2"
          :stroke-dashoffset="dashOffset"
          stroke-linecap="round"
          class="transition-all duration-1000 ease-out"
        />
      </svg>

      <div class="absolute flex flex-col items-center justify-center z-10">
        <span class="text-3xl font-extrabold tracking-tight text-slate-800">{{ score }}</span>
        <span class="text-[10px] uppercase font-bold text-slate-450">Risk</span>
      </div>
    </div>

    <!-- Rating badge text -->
    <span
      :class="[
        'mt-3.5 rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider border',
        score >= 75 ? 'bg-red-50 text-red-700 border-red-200' : '',
        score >= 25 && score < 75 ? 'bg-amber-50 text-amber-700 border-amber-200' : '',
        score < 25 ? 'bg-green-50 text-green-700 border-green-200' : '',
      ]"
    >
      {{ ratingText }}
    </span>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  score: {
    type: Number,
    required: true,
  }
});

const strokeColor = computed(() => {
  if (props.score >= 75) return '#ef4444';
  if (props.score >= 25) return '#f59e0b';
  return '#10b981';
});

const dashOffset = computed(() => {
  return 251.2 - (251.2 * props.score) / 100;
});

const ratingText = computed(() => {
  if (props.score >= 75) return 'CRITICAL RISK';
  if (props.score >= 25) return 'MEDIUM RISK';
  return 'LOW RISK';
});
</script>
