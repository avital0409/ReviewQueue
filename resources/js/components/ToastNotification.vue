<template>
  <div
    class="fixed bottom-6 right-6 z-50 flex flex-col gap-3 max-w-sm w-full pointer-events-none"
  >
    <TransitionGroup
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-for="toast in notifications"
        :key="toast.id"
        class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-2xl border border-slate-100 bg-white/90 backdrop-blur-md p-4 shadow-xl shadow-slate-200/50 flex items-start gap-3"
      >
        <div
          :class="[
            'h-8 w-8 rounded-xl flex items-center justify-center border shrink-0',
            toast.type === 'success' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : '',
            toast.type === 'warning' ? 'bg-amber-50 text-amber-600 border-amber-100' : '',
            toast.type === 'error' ? 'bg-rose-50 text-rose-600 border-rose-100' : '',
          ]"
        >
          <svg
            v-if="toast.type === 'success'"
            class="h-4.5 w-4.5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2.5"
              d="M9 12l2 2 4-4"
            />
          </svg>
          <svg v-else class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2.5"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
            />
          </svg>
        </div>
        <div class="flex-1 space-y-0.5 pt-0.5">
          <p class="text-xs font-bold text-slate-700 leading-normal">{{ toast.message }}</p>
        </div>
        <button
          class="text-slate-400 hover:text-slate-600 transition-colors shrink-0"
          @click="$emit('dismiss', toast.id)"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
defineProps({
  notifications: {
    type: Array,
    required: true,
  }
});

defineEmits(['dismiss']);
</script>
