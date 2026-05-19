<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <!-- Backdrop with blur -->
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closeModal"></div>

    <!-- Modal Content Box -->
    <div
      class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-300/80 transition-all duration-300"
    >
      <!-- Glow decoration -->
      <div
        class="absolute -right-20 -top-20 h-40 w-40 rounded-full bg-blue-500/5 blur-3xl pointer-events-none"
      ></div>
      <div
        class="absolute -left-20 -bottom-20 h-40 w-40 rounded-full bg-violet-500/5 blur-3xl pointer-events-none"
      ></div>

      <!-- Header -->
      <div class="flex items-center justify-between border-b border-slate-100 p-6">
        <h3 class="text-xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
          <svg
            class="h-5 w-5 text-blue-500 animate-pulse"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          Submit Content for Review
        </h3>
        <button
          class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors"
          @click="closeModal"
        >
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <!-- Body & Form -->
      <form class="p-6 space-y-5" @submit.prevent="handleSubmit">
        <!-- Error Alert -->
        <div
          v-if="error"
          class="rounded-xl border border-red-200 bg-red-50 text-sm text-red-600 p-4 flex items-start gap-3"
        >
          <svg
            class="h-5 w-5 shrink-0 mt-0.5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
            />
          </svg>
          <div><span class="font-semibold">Submission failed:</span> {{ error }}</div>
        </div>

        <!-- Input: Email -->
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2"
            >Author Email</label
          >
          <div class="relative">
            <div
              class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400"
            >
              <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"
                />
              </svg>
            </div>
            <input
              v-model="form.author_email"
              type="email"
              placeholder="e.g. user@example.com"
              required
              class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-3 pl-11 pr-4 text-sm text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none transition-all shadow-sm"
            />
          </div>
        </div>

        <!-- Input: Content -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500"
              >Content Body</label
            >
            <!-- Auto fill button -->
            <button
              type="button"
              :disabled="isGenerating"
              class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              @click="autoFill"
            >
              <svg
                v-if="isGenerating"
                class="animate-spin h-3.5 w-3.5 text-blue-600 mr-0.5"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
              <span v-else class="animate-pulse">✨</span>
              {{ isGenerating ? 'AI is thinking...' : 'Auto-Fill with AI' }}
            </button>
          </div>
          <textarea
            v-model="form.content"
            rows="5"
            placeholder="Type or paste the submission content here..."
            required
            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-4 text-sm text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none resize-none transition-all shadow-sm"
          ></textarea>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 pt-2">
          <button
            type="button"
            class="rounded-xl border border-slate-200 bg-slate-50 px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100 hover:text-slate-800 transition-all"
            @click="closeModal"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="submitting"
            class="relative inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/10 hover:from-blue-500 hover:to-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-white disabled:opacity-50 disabled:cursor-not-allowed transition-all"
          >
            <svg
              v-if="submitting"
              class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              ></circle>
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              ></path>
            </svg>
            Submit Item
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';

defineProps({
  isOpen: Boolean,
});

const emit = defineEmits(['close', 'submitted']);

const form = reactive({
  author_email: '',
  content: '',
});

const submitting = ref(false);
const isGenerating = ref(false);
const error = ref(null);

const closeModal = () => {
  error.value = null;
  form.author_email = '';
  form.content = '';
  emit('close');
};

const mockPersonas = [
  {
    email: 'scammer99@crypto-yield.xyz',
    content:
      'URGENT: Multiply your earnings with our new decentralized bitcoin pool! Deposit to this wallet immediately to unlock a guaranteed 350% yield. Safe, verified, and audited!',
  },
  {
    email: 'frustrated_user@company.net',
    content:
      'I noticed an unauthorized action alert in my profile, and now my account is locked. I need assistance immediately, please check this out ASAP!',
  },
  {
    email: 'jane.developer@gmail.com',
    content:
      'Hello, I have submitted a pull request fixing the styling alignment issues in the main dashboard. Let me know if you need any adjustments.',
  },
];

let lastPersonaIndex = -1;

const getLocalFallbackPersona = () => {
  let index;
  do {
    index = Math.floor(Math.random() * mockPersonas.length);
  } while (index === lastPersonaIndex && mockPersonas.length > 1);

  lastPersonaIndex = index;
  return mockPersonas[index];
};

const autoFill = async () => {
  console.log('[SubmitItemModal] autoFill triggered. Requesting mock content...');
  isGenerating.value = true;
  error.value = null;

  let selectedEmail;
  let selectedContent;

  try {
    const response = await axios.get('/api/items/generate');

    if (response.data && response.data.email && response.data.content) {
      selectedEmail = response.data.email;
      selectedContent = response.data.content;
      console.log('[SubmitItemModal] Dynamic local AI generation resolved successfully:', {
        email: selectedEmail,
      });
    } else {
      // API is configured but returned a status/fallback array, or key bypassed
      const localPersona = getLocalFallbackPersona();
      selectedEmail = localPersona.email;
      selectedContent = localPersona.content;
      console.log('[SubmitItemModal] Local provider returned fallback. Selected static persona:', {
        email: selectedEmail,
      });
    }
  } catch (err) {
    // Graceful fallback to static personas on network/server failures
    const localPersona = getLocalFallbackPersona();
    selectedEmail = localPersona.email;
    selectedContent = localPersona.content;
    console.warn(
      '[SubmitItemModal] Local provider threw exception. Selected static fallback persona:',
      { email: selectedEmail },
      err
    );
  } finally {
    isGenerating.value = false;
  }

  // Satisfying typing effect or quick populate
  form.author_email = selectedEmail;
  form.content = '';

  let i = 0;
  const speed = 8; // Milliseconds per char
  const typeWriter = () => {
    if (i < selectedContent.length) {
      form.content += selectedContent.charAt(i);
      i++;
      setTimeout(typeWriter, speed);
    }
  };
  typeWriter();
};

const handleSubmit = async () => {
  console.log('[SubmitItemModal] handleSubmit called with payload:', JSON.stringify(form));
  submitting.value = true;
  error.value = null;

  try {
    const response = await axios.post('/api/items', form);
    console.log('[SubmitItemModal] Submission successfully accepted by gateway:', response.data);
    emit('submitted', response.data);
    closeModal();
  } catch (err) {
    console.error('[SubmitItemModal] Gateway rejected submission:', err);
    if (err.response && err.response.data && err.response.data.message) {
      error.value = err.response.data.message;
    } else {
      error.value = 'Something went wrong. Please check your network connection and try again.';
    }
  } finally {
    submitting.value = false;
  }
};
</script>
