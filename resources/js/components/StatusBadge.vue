<template>
  <span
    :class="[
      'rounded-full px-2.5 py-0.5 text-[9px] font-bold uppercase tracking-wider border transition-all',
      badgeClasses
    ]"
  >
    <slot>{{ displayLabel }}</slot>
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  type: {
    type: String,
    required: true, // 'status', 'account', 'risk'
  },
  value: {
    type: [String, Number, Boolean],
    required: true,
  }
});

const normalizedValue = computed(() => {
  if (typeof props.value === 'boolean') {
    return props.value;
  }
  if (props.value === null || props.value === undefined) {
    return '';
  }
  return String(props.value).toLowerCase().trim();
});

const displayLabel = computed(() => {
  if (props.type === 'account') {
    return normalizedValue.value === true || normalizedValue.value === 'banned' || normalizedValue.value === '1'
      ? 'Banned'
      : 'Active Account';
  }
  
  if (props.type === 'risk') {
    const val = normalizedValue.value;
    if (val === 'high' || val === 'high risk') return 'High Risk';
    if (val === 'medium' || val === 'medium risk') return 'Medium Risk';
    if (val === 'low' || val === 'low risk') return 'Low Risk';
    if (val === 'ban suggested' || val === 'ban_suggested') return 'Ban Suggested';
    return props.value;
  }

  // default 'status'
  const val = normalizedValue.value;
  if (val === 'pending') return 'Pending';
  if (val === 'approved') return 'Approved';
  if (val === 'rejected') return 'Rejected';
  if (val === 'blocked') return 'Blocked';
  return props.value;
});

const badgeClasses = computed(() => {
  if (props.type === 'account') {
    const isBanned = normalizedValue.value === true || normalizedValue.value === 'banned' || normalizedValue.value === '1';
    return isBanned
      ? 'bg-red-100 text-red-700 border-red-200'
      : 'bg-slate-100 text-slate-600 border-slate-200';
  }

  if (props.type === 'risk') {
    const val = normalizedValue.value;
    if (val === 'high' || val === 'high risk') {
      return 'bg-red-50 text-red-700 border-red-200';
    }
    if (val === 'medium' || val === 'medium risk') {
      return 'bg-amber-50 text-amber-700 border-amber-200';
    }
    if (val === 'low' || val === 'low risk') {
      return 'bg-slate-100 text-slate-600 border-slate-200';
    }
    if (val === 'ban suggested' || val === 'ban_suggested') {
      return 'bg-red-100 text-red-800 border-red-300 font-extrabold animate-pulse';
    }
    return 'bg-slate-100 text-slate-600 border-slate-200';
  }

  // Default 'status'
  const val = normalizedValue.value;
  if (val === 'pending') {
    return 'bg-yellow-50 text-yellow-700 border-yellow-200/60';
  }
  if (val === 'approved') {
    return 'bg-emerald-50 text-emerald-700 border-emerald-200/60';
  }
  if (val === 'rejected') {
    return 'bg-rose-50 text-rose-700 border-rose-200/60';
  }
  if (val === 'blocked') {
    return 'bg-slate-100 text-slate-600 border-slate-200';
  }
  return 'bg-slate-100 text-slate-600 border-slate-200';
});
</script>
