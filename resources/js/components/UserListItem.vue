<template>
  <div
    :class="[
      'group p-4 flex flex-col gap-2.5 cursor-pointer relative overflow-hidden transition-all border-l-2',
      isActive
        ? 'bg-blue-50/50 border-l-blue-600'
        : 'hover:bg-slate-50/50 border-l-transparent'
    ]"
    @click="$emit('select', user.author_email)"
  >
    <div class="flex items-center justify-between">
      <span class="font-bold text-sm text-slate-700 truncate max-w-[200px]">
        {{ user.author_email }}
      </span>
      <StatusBadge type="account" :value="user.is_banned" />
    </div>

    <div class="flex items-center justify-between text-xs">
      <div class="flex items-center gap-4 text-slate-500 font-medium">
        <span>Total: <strong class="text-slate-700">{{ user.total_count }}</strong></span>
        <span>Approved: <strong class="text-emerald-600">{{ user.approved_count }}</strong></span>
        <span>Rejected: <strong class="text-rose-600">{{ user.rejected_count }}</strong></span>
        <span>Blocked: <strong class="text-slate-700">{{ user.blocked_count }}</strong></span>
      </div>

      <!-- Strike bubbles scorecard -->
      <StrikeScorecard :rejected-count="user.rejected_count" :is-banned="user.is_banned" />
    </div>
  </div>
</template>

<script setup>
import StatusBadge from './StatusBadge.vue';
import StrikeScorecard from './StrikeScorecard.vue';

defineProps({
  user: {
    type: Object,
    required: true,
  },
  isActive: {
    type: Boolean,
    default: false,
  }
});

defineEmits(['select']);
</script>
