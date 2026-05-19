<template>
  <div
    v-if="flags && flags.length"
    class="flex flex-wrap gap-1 mt-1"
  >
    <span
      v-for="flag in flags"
      :key="flag"
      class="rounded bg-slate-50 px-1.5 py-0.5 text-[9px] font-semibold text-slate-500 border border-slate-200"
    >
      {{ formatFlagName(flag) }}
    </span>
  </div>
</template>

<script setup>
defineProps({
  flags: {
    type: Array,
    required: true,
  }
});

const formatFlagName = (flag) => {
  if (flag === 'financial_keywords') return 'Financial Keywords';
  if (flag === 'external_links') return 'External Links';
  if (flag === 'urgent_language') return 'Urgent Intent';
  if (flag === 'banned_author') return 'Banned Author';
  return flag;
};
</script>
