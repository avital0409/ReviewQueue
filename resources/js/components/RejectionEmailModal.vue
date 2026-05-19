<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
  >
    <div
      class="w-full max-w-2xl bg-white rounded-3xl border border-slate-200 shadow-2xl shadow-slate-350/80 overflow-hidden flex flex-col max-h-[90vh] transform transition-all duration-300 animate-in fade-in zoom-in-95"
    >
      <!-- Modal Header -->
      <div
        class="border-b border-slate-100 bg-slate-50/50 p-6 flex items-center justify-between shrink-0"
      >
        <div class="flex items-center gap-3">
          <div
            class="h-10 w-10 rounded-xl flex items-center justify-center border transition-all duration-300"
            :class="
              isBan
                ? 'bg-red-500/10 border-red-500/20 text-red-600 animate-pulse'
                : 'bg-rose-50 border-rose-100 text-rose-500'
            "
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M3 19v-8.93a2 2 0 01.89-1.664l8-4.8a2 2 0 012.22 0l8 4.8A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-2.25-1.5a2 2 0 00-2.22 0l-2.25 1.5"
              />
            </svg>
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900">
              {{ isBan ? 'Account Suspension & Ban Notice' : 'Rejection Notification Email' }}
            </h3>
            <p class="text-xs text-slate-500">
              {{
                isBan
                  ? 'Draft a formal permanent suspension notice due to repeated violations'
                  : 'Draft a constructive rejection notice for the submitter'
              }}
            </p>
          </div>
        </div>
        <button
          class="rounded-full p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors"
          @click="$emit('close')"
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

      <!-- Modal Body -->
      <div class="flex-1 overflow-y-auto p-6 space-y-5">
        <!-- Recipient & Subject Info fields -->
        <div class="space-y-3 bg-slate-50 p-4 rounded-2xl border border-slate-150 text-xs">
          <div class="grid grid-cols-[60px_1fr] items-center gap-2">
            <span class="font-bold text-slate-400 uppercase tracking-wider text-right pr-2"
              >To:</span
            >
            <span class="font-semibold text-slate-700 font-mono select-all">{{
              item.author_email
            }}</span>
          </div>
          <div class="h-px bg-slate-200"></div>
          <div class="grid grid-cols-[60px_1fr] items-center gap-2">
            <span class="font-bold text-slate-400 uppercase tracking-wider text-right pr-2"
              >Subject:</span
            >
            <span class="font-semibold text-slate-700">
              {{
                isBan
                  ? 'Account Suspension & Permanent Ban Notice - ReviewQueue'
                  : 'Submission Rejection Notice - ReviewQueue'
              }}
            </span>
          </div>
        </div>

        <!-- AI Draft Loading State -->
        <div
          v-if="loadingDraft"
          class="flex flex-col items-center justify-center py-12 text-center space-y-4"
        >
          <!-- Pulse loading circles -->
          <div class="relative flex items-center justify-center">
            <div class="absolute h-12 w-12 rounded-full bg-blue-500/10 animate-ping"></div>
            <div
              class="h-10 w-10 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-500"
            >
              ✨
            </div>
          </div>
          <div class="space-y-1">
            <p class="text-sm font-semibold text-slate-800">
              {{
                isBan ? 'AI is drafting account ban notice...' : 'AI is drafting rejection email...'
              }}
            </p>
            <p class="text-xs text-slate-400 max-w-xs">
              {{
                isBan
                  ? 'Generating a formal account suspension letter detailing policy threshold breach.'
                  : 'Generating a polite, context-aware notification using local Ollama model.'
              }}
            </p>
          </div>
        </div>

        <!-- Rich Email Editor View -->
        <div v-else class="space-y-3">
          <div class="flex items-center justify-between">
            <label class="text-xs font-bold uppercase tracking-wider text-slate-400"
              >Email Body Draft</label
            >
            <button
              class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700 transition-colors"
              @click="generateDraft"
            >
              <span>✨</span> Regenerate AI Draft
            </button>
          </div>

          <textarea
            v-model="emailBody"
            rows="10"
            class="w-full rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none resize-y transition-all shadow-inner font-sans"
            placeholder="Write customized rejection email details here..."
          ></textarea>
        </div>
      </div>

      <!-- Modal Footer -->
      <div
        class="border-t border-slate-100 bg-slate-50/50 p-6 flex items-center justify-end gap-3 shrink-0"
      >
        <button
          class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50 focus:outline-none transition-all"
          @click="$emit('close')"
        >
          Cancel
        </button>
        <button
          :disabled="submitting"
          class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-red-500/10 hover:from-red-500 hover:to-rose-500 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed transition-all"
          @click="confirmRejection"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2.5"
              d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          {{
            isBan
              ? sendEmail
                ? 'Send Suspension & Ban User'
                : 'Confirm Ban Only'
              : sendEmail
                ? 'Send Notification & Reject'
                : 'Confirm Rejection Only'
          }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  isOpen: Boolean,
  item: Object,
  reviewerNote: String,
  prefetchedDraft: Object,
  isBan: Boolean,
});

const emit = defineEmits(['close', 'confirm']);

const emailBody = ref('');
const sendEmail = ref(true);
const loadingDraft = ref(false);
const submitting = ref(false);

const generateDraft = async () => {
  if (!props.item) return;
  console.log('[RejectionEmailModal] generateDraft initiated:', {
    itemId: props.item.id,
    isBan: props.isBan,
    reviewerNote: props.reviewerNote,
  });

  // Use prefetched draft if available and NOT in ban mode (re-fetch to format suspension rules)
  if (props.prefetchedDraft && !props.isBan && props.prefetchedDraft.note === props.reviewerNote) {
    if (props.prefetchedDraft.loading) {
      console.log('[RejectionEmailModal] Re-using loading pre-fetched draft stream...');
      loadingDraft.value = true;
      const unwatch = watch(
        () => props.prefetchedDraft?.loading,
        (loading) => {
          if (!loading) {
            emailBody.value = props.prefetchedDraft?.draft || '';
            loadingDraft.value = false;
            unwatch();
          }
        }
      );
      return;
    } else if (props.prefetchedDraft.draft) {
      console.log(
        '[RejectionEmailModal] Re-using completed pre-fetched draft:',
        props.prefetchedDraft.draft
      );
      emailBody.value = props.prefetchedDraft.draft;
      loadingDraft.value = false;
      return;
    }
  }

  loadingDraft.value = true;
  try {
    console.log('[RejectionEmailModal] Sending POST request for rejection draft...');
    const response = await axios.post(`/api/items/${props.item.id}/rejection-draft`, {
      reviewer_note: props.reviewerNote,
      is_ban: props.isBan,
    });
    emailBody.value = response.data.draft || '';
    console.log('[RejectionEmailModal] Received rejection draft successfully:', emailBody.value);
  } catch (err) {
    console.error('[RejectionEmailModal] Failed to generate AI draft:', err);
    if (props.isBan) {
      emailBody.value = `Dear Submitter,\n\nThis is a formal notice that your email address (${props.item.author_email}) has been permanently suspended from submitting content to ReviewQueue.\n\nOur Trust & Safety system identified repeated policy violations associated with your submissions, exceeding our allowed Strike 3 threshold.\n\nReason for Suspension:\n- ${props.reviewerNote || 'Repeated violations of content guidelines.'}\n\nWarm regards,\nReviewQueue Moderation & Safety Hub`;
    } else {
      emailBody.value = `Dear Submitter,\n\nThank you for your submission to our platform. After careful review of your content, our moderation team has decided to reject this post.\n\nReason for Rejection:\n- ${props.reviewerNote || 'Content did not comply with our standard guidelines.'}\n\nWarm regards,\nReviewQueue Moderation Hub`;
    }
  } finally {
    loadingDraft.value = false;
  }
};

// Whenever modal opens, generate or bind pre-fetched draft
watch(
  () => props.isOpen,
  (newVal) => {
    if (newVal) {
      sendEmail.value = true;
      generateDraft();
    }
  }
);

const confirmRejection = () => {
  console.log('[RejectionEmailModal] confirmRejection clicked with parameters:', {
    sendEmail: sendEmail.value,
    isBan: props.isBan,
  });
  submitting.value = true;
  emit('confirm', {
    sendEmail: sendEmail.value,
    emailBody: emailBody.value,
    banUser: props.isBan,
    banReason: props.reviewerNote || 'Repeated policy violations (Strike 3 exceeded).',
  });
  submitting.value = false;
};
</script>
