<template>
  <div class="flex items-center gap-1">
    <span class="text-[10px] font-bold text-slate-400 mr-1 uppercase">Strikes:</span>
    <span
      v-for="strike in 3"
      :key="strike"
      class="h-2.5 w-2.5 rounded-full border border-slate-300 transition-colors"
      :class="[
        rejectedCount >= strike ? 'bg-red-500 border-red-600' : 'bg-slate-100',
        isBanned ? 'bg-red-600 border-red-700' : '',
      ]"
    ></span>
    <span v-if="rejectedCount > 3" class="text-xs font-bold text-red-600 ml-1">
      +{{ rejectedCount - 3 }}
    </span>
  </div>
</template>

<script setup>
defineProps({
  rejectedCount: {
    type: Number,
    required: true,
  },
  isBanned: {
    type: Boolean,
    default: false,
  }
});
</script>
