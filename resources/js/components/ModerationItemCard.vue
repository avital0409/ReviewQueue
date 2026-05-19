<template>
  <div
    :class="[
      'group p-4 flex flex-col gap-2.5 cursor-pointer relative overflow-hidden transition-all border-l-2',
      isActive
        ? 'bg-blue-50/50 border-l-2 border-l-blue-600'
        : 'hover:bg-slate-50/50 border-l-2 border-l-transparent',
    ]"
    @click="$emit('select', item.id)"
  >
    <!-- Header on card -->
    <div class="flex items-center justify-between">
      <span class="text-xs font-semibold text-slate-400 flex items-center gap-1.5">
        #{{ item.id }}
        <span
          v-if="item.author_is_banned"
          class="rounded bg-red-600 px-1 py-0.2 text-[8px] font-bold text-white uppercase"
        >Banned</span>
      </span>
      <div class="flex items-center gap-1.5">
        <!-- Status Badge -->
        <StatusBadge type="status" :value="item.status" />

        <!-- Risk Badge -->
        <span
          :class="[
            'rounded-full px-2 py-0.5 text-[9px] font-bold border',
            item.risk_score >= 75
              ? 'bg-red-50 text-red-700 border-red-200 shadow-sm shadow-red-500/5'
              : '',
            item.risk_score >= 25 && item.risk_score < 75
              ? 'bg-amber-50 text-amber-700 border-amber-200'
              : '',
            item.risk_score < 25 ? 'bg-green-50 text-green-700 border-green-200' : '',
          ]"
        >
          Risk: {{ item.risk_score }}
        </span>
      </div>
    </div>

    <!-- Email & Time -->
    <div class="flex items-center justify-between text-xs">
      <span
        class="font-semibold text-slate-700 group-hover:text-slate-900 transition-colors truncate max-w-[170px]"
      >{{ item.author_email }}</span>
      <div class="flex items-center gap-1.5 shrink-0">
        <!-- Striking Indicator Badge in Sidebar -->
        <span
          v-if="item.author_rejections_count > 0"
          class="text-[9px] font-bold bg-rose-50 text-rose-600 px-1.5 py-0.5 rounded border border-rose-100 flex items-center gap-0.5"
        >
          ⚠️ Strike {{ item.author_rejections_count }}
        </span>
        <span class="text-slate-400">{{ formatRelativeTime(item.created_at) }}</span>
      </div>
    </div>

    <!-- Truncated Content text -->
    <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
      {{ item.content }}
    </p>

    <!-- Flags preview -->
    <HeuristicFlagList :flags="item.heuristic_flags" />
  </div>
</template>

<script setup>
import StatusBadge from './StatusBadge.vue';
import HeuristicFlagList from './HeuristicFlagList.vue';

defineProps({
  item: {
    type: Object,
    required: true,
  },
  isActive: {
    type: Boolean,
    default: false,
  },
  formatRelativeTime: {
    type: Function,
    required: true,
  }
});

defineEmits(['select']);
</script>
