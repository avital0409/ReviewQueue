<template>
  <div class="flex h-screen w-screen flex-col overflow-hidden bg-slate-950 font-sans text-slate-100 antialiased">
    <!-- Header -->
    <header class="flex h-16 shrink-0 items-center justify-between border-b border-slate-800 bg-slate-900/60 px-6 backdrop-blur-md">
      <div class="flex items-center gap-3">
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 shadow-md">
          <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
        </div>
        <div>
          <h1 class="text-lg font-bold tracking-tight text-white flex items-center gap-2">
            ModHub
            <span class="rounded-full bg-blue-500/10 px-2.5 py-0.5 text-xs font-semibold text-blue-400 border border-blue-500/20">Reviewer Queue v1.0</span>
          </h1>
        </div>
      </div>

      <div class="flex items-center gap-4">
        <button 
          @click="isModalOpen = true"
          class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/15 hover:from-blue-500 hover:to-indigo-500 transition-all focus:outline-none"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
          </svg>
          Submit Content
        </button>
      </div>
    </header>

    <!-- Main Content split pane -->
    <div class="flex min-h-0 flex-1 flex-row">
      <!-- Left Pane: Sidebar Queue -->
      <aside class="flex w-[600px] shrink-0 flex-col border-r border-slate-800 bg-slate-900/30">
        <!-- Search, Sort & Status Filters -->
        <div class="border-b border-slate-850 p-4 space-y-4">
          <!-- Search input -->
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input 
              v-model="filters.search"
              @input="fetchItems"
              type="text" 
              placeholder="Search content, author, flags..."
              class="w-full rounded-xl border border-slate-800 bg-slate-950/60 py-2.5 pl-9 pr-4 text-sm text-slate-200 placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none transition-all shadow-inner"
            />
          </div>

          <!-- Status Filters Row -->
          <div class="flex items-center gap-1 bg-slate-950/40 p-1 rounded-xl border border-slate-850">
            <button 
              v-for="statusOpt in ['all', 'pending', 'approved', 'rejected']"
              :key="statusOpt"
              @click="setStatusFilter(statusOpt)"
              :class="[
                'flex-1 py-1.5 px-2 rounded-lg text-xs font-semibold uppercase tracking-wider transition-all flex items-center justify-center gap-1.5',
                filters.status === statusOpt 
                  ? 'bg-slate-800 text-white shadow-sm border border-slate-700/50' 
                  : 'text-slate-400 hover:text-slate-200'
              ]"
            >
              {{ statusOpt }}
              <span class="rounded-full bg-slate-900/80 px-1.5 py-0.5 text-[10px] font-bold text-slate-500 border border-slate-800">
                {{ counts[statusOpt] }}
              </span>
            </button>
          </div>

          <!-- Sort Selector -->
          <div class="flex items-center justify-between text-xs">
            <span class="font-medium text-slate-500">Sort by</span>
            <select 
              v-model="filters.sort"
              @change="fetchItems"
              class="rounded-lg border border-slate-800 bg-slate-950 px-2 py-1 text-slate-300 focus:border-blue-500 focus:outline-none transition-all"
            >
              <option value="newest">Newest First</option>
              <option value="risk_desc">Risk: High to Low</option>
              <option value="risk_asc">Risk: Low to High</option>
            </select>
          </div>
        </div>

        <!-- Scrollable Cards Queue -->
        <div class="flex-1 overflow-y-auto divide-y divide-slate-850">
          <!-- Loading state -->
          <div v-if="loading && items.length === 0" class="flex flex-col items-center justify-center py-16 text-slate-500 gap-3">
            <svg class="animate-spin h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-xs font-semibold tracking-wider uppercase">Loading queue...</span>
          </div>

          <!-- Empty Queue state -->
          <div v-else-if="items.length === 0" class="flex flex-col items-center justify-center py-16 px-4 text-center text-slate-500 gap-2">
            <svg class="h-10 w-10 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <span class="text-sm font-bold text-slate-400">Queue Empty</span>
            <span class="text-xs">No items matching your criteria were found.</span>
          </div>

          <!-- Cards list -->
          <div 
            v-for="item in items" 
            :key="item.id"
            @click="selectItem(item.id)"
            :class="[
              'group p-4 flex flex-col gap-2.5 cursor-pointer relative overflow-hidden transition-all',
              activeItemId === item.id 
                ? 'bg-slate-800/40 border-l-2 border-l-blue-500' 
                : 'hover:bg-slate-800/15 border-l-2 border-l-transparent'
            ]"
          >
            <!-- Header on card -->
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-slate-500">#{{ item.id }}</span>
              <div class="flex items-center gap-1.5">
                <!-- Status Badge -->
                <span :class="[
                  'rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                  item.status === 'pending' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : '',
                  item.status === 'approved' ? 'bg-green-500/10 text-green-400 border border-green-500/20' : '',
                  item.status === 'rejected' ? 'bg-red-500/10 text-red-400 border border-red-500/20' : ''
                ]">
                  {{ item.status }}
                </span>

                <!-- Risk Badge -->
                <span :class="[
                  'rounded-full px-2 py-0.5 text-[10px] font-bold',
                  item.risk_score >= 75 ? 'bg-red-500/10 text-red-400 border border-red-500/20 shadow-md shadow-red-500/5' : '',
                  item.risk_score >= 25 && item.risk_score < 75 ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : '',
                  item.risk_score < 25 ? 'bg-green-500/10 text-green-400 border border-green-500/20' : ''
                ]">
                  Risk: {{ item.risk_score }}
                </span>
              </div>
            </div>

            <!-- Email & Time -->
            <div class="flex items-center justify-between text-xs">
              <span class="font-medium text-slate-300 group-hover:text-white transition-colors truncate max-w-44">{{ item.author_email }}</span>
              <span class="text-slate-500 shrink-0">{{ formatRelativeTime(item.created_at) }}</span>
            </div>

            <!-- Truncated Content text -->
            <p class="text-xs text-slate-400 line-clamp-2 leading-relaxed">
              {{ item.content }}
            </p>

            <!-- Flags preview -->
            <div v-if="item.heuristic_flags && item.heuristic_flags.length" class="flex flex-wrap gap-1 mt-1">
              <span 
                v-for="flag in item.heuristic_flags" 
                :key="flag"
                class="rounded bg-slate-900 px-1.5 py-0.5 text-[9px] font-semibold text-slate-500 border border-slate-800"
              >
                {{ formatFlagName(flag) }}
              </span>
            </div>
          </div>
        </div>
      </aside>

      <!-- Right Pane: Active Item Detail & Actions -->
      <main class="flex flex-1 flex-col overflow-hidden bg-slate-950 relative">
        <div v-if="activeItem" class="flex h-full flex-col">
          <!-- Detail Header -->
          <div class="border-b border-slate-850 bg-slate-900/10 p-6 flex items-center justify-between shrink-0">
            <div class="space-y-1">
              <div class="flex items-center gap-3">
                <h2 class="text-lg font-bold text-white">Item #{{ activeItem.id }}</h2>
                <span :class="[
                  'rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wider',
                  activeItem.status === 'pending' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : '',
                  activeItem.status === 'approved' ? 'bg-green-500/10 text-green-400 border border-green-500/20' : '',
                  activeItem.status === 'rejected' ? 'bg-red-500/10 text-red-400 border border-red-500/20' : ''
                ]">
                  {{ activeItem.status }}
                </span>
              </div>
              <p class="text-xs text-slate-400">
                Submitted by <span class="font-semibold text-slate-300">{{ activeItem.author_email }}</span>
                • {{ formatRelativeTime(activeItem.created_at) }} ({{ formatDate(activeItem.created_at) }})
              </p>
            </div>
          </div>

          <!-- Detail Body Scrollable -->
          <div class="flex-1 overflow-y-auto p-6 space-y-6">
            <!-- 1. Submission Content Box -->
            <div class="space-y-2.5">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Submission Content</h3>
              <div class="rounded-2xl border border-slate-800 bg-slate-900/35 p-6 shadow-inner relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-b from-blue-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none duration-500"></div>
                <p class="text-slate-200 text-sm leading-relaxed whitespace-pre-wrap font-sans">
                  {{ activeItem.content }}
                </p>
              </div>
            </div>

            <!-- 2. Heuristic Analysis Dashboard card -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
              <!-- Score dial gauge card -->
              <div class="rounded-2xl border border-slate-800 bg-slate-900/40 p-5 flex flex-col items-center justify-center text-center relative overflow-hidden group">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-3 block">Heuristic Risk Score</span>
                
                <div class="relative flex items-center justify-center h-28 w-28">
                  <!-- Circular track border -->
                  <div class="absolute inset-0 rounded-full border-4 border-slate-800"></div>
                  <!-- Active colored glow -->
                  <div :class="[
                    'absolute inset-0 rounded-full border-4 transition-all duration-700',
                    activeItem.risk_score >= 75 ? 'border-red-500 shadow-md shadow-red-500/20' : '',
                    activeItem.risk_score >= 25 && activeItem.risk_score < 75 ? 'border-amber-500' : '',
                    activeItem.risk_score < 25 ? 'border-green-500' : ''
                  ]" :style="{ clipPath: `polygon(50% 50%, -50% -50%, ${activeItem.risk_score >= 50 ? '150% -50%, 150% 150%' : '150% -50%'}, -50% 150%)` }"></div>

                  <div class="flex flex-col items-center z-10">
                    <span class="text-3xl font-extrabold tracking-tight text-white">{{ activeItem.risk_score }}</span>
                    <span class="text-[10px] uppercase font-bold text-slate-500">Rating</span>
                  </div>
                </div>

                <!-- Rating badge text -->
                <span :class="[
                  'mt-3.5 rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                  activeItem.risk_score >= 75 ? 'bg-red-500/10 text-red-400 border border-red-500/20' : '',
                  activeItem.risk_score >= 25 && activeItem.risk_score < 75 ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : '',
                  activeItem.risk_score < 25 ? 'bg-green-500/10 text-green-400 border border-green-500/20' : ''
                ]">
                  {{ activeItem.risk_score >= 75 ? 'CRITICAL RISK' : (activeItem.risk_score >= 25 ? 'MEDIUM RISK' : 'LOW RISK') }}
                </span>
              </div>

              <!-- Recommendation badge card -->
              <div class="rounded-2xl border border-slate-800 bg-slate-900/40 p-5 flex flex-col justify-between relative overflow-hidden group">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Heuristic Auto-Action</span>
                
                <div class="my-4 flex items-center gap-3">
                  <div :class="[
                    'h-12 w-12 rounded-xl flex items-center justify-center',
                    activeItem.auto_suggestion === 'reject' ? 'bg-red-500/10 text-red-400' : '',
                    activeItem.auto_suggestion === 'approve' ? 'bg-green-500/10 text-green-400' : '',
                    activeItem.auto_suggestion === 'none' ? 'bg-slate-800 text-slate-400' : ''
                  ]">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path v-if="activeItem.auto_suggestion === 'reject'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      <path v-else-if="activeItem.auto_suggestion === 'approve'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>

                  <div>
                    <span class="text-xs text-slate-500 block font-medium">Auto-Suggestion</span>
                    <span :class="[
                      'text-lg font-bold leading-none uppercase',
                      activeItem.auto_suggestion === 'reject' ? 'text-red-400' : '',
                      activeItem.auto_suggestion === 'approve' ? 'text-green-400' : '',
                      activeItem.auto_suggestion === 'none' ? 'text-slate-300' : ''
                    ]">
                      {{ activeItem.auto_suggestion === 'none' ? 'MANUAL REVIEW' : activeItem.auto_suggestion }}
                    </span>
                  </div>
                </div>

                <p class="text-[10px] text-slate-500 leading-normal">
                  {{ 
                    activeItem.auto_suggestion === 'reject' 
                      ? 'Heuristics triggered high risk spam metrics. Rejection is highly recommended.' 
                      : (activeItem.auto_suggestion === 'approve' 
                        ? 'Clean data, cleared automated filters. Approval recommended.' 
                        : 'Mixed intent. Requires reviewer manually reading context details.')
                  }}
                </p>
              </div>

              <!-- Flagged indicators card -->
              <div class="rounded-2xl border border-slate-800 bg-slate-900/40 p-5 flex flex-col justify-between relative overflow-hidden group">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Triggered Flags</span>

                <div class="my-3 space-y-1.5 flex-1 flex flex-col justify-center">
                  <div v-if="!activeItem.heuristic_flags || activeItem.heuristic_flags.length === 0" class="text-xs text-slate-500 italic">
                    No automated risk triggers detected.
                  </div>
                  <div 
                    v-for="flag in activeItem.heuristic_flags" 
                    :key="flag"
                    :class="[
                      'inline-flex items-center gap-2 rounded-xl py-1.5 px-3 text-xs font-semibold w-full border',
                      flag === 'financial_keywords' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : '',
                      flag === 'external_links' ? 'bg-blue-500/10 text-blue-400 border-blue-500/20' : '',
                      flag === 'urgent_language' ? 'bg-violet-500/10 text-violet-400 border-violet-500/20' : ''
                    ]"
                  >
                    <span v-if="flag === 'financial_keywords'">💳</span>
                    <span v-else-if="flag === 'external_links'">🔗</span>
                    <span v-else-if="flag === 'urgent_language'">⚠️</span>
                    {{ formatFlagName(flag) }}
                  </div>
                </div>

                <span class="text-[10px] text-slate-500 block">
                  Scanned synchronously at entry point.
                </span>
              </div>
            </div>

            <!-- 3. Reviewer Action Note -->
            <div class="space-y-2.5 pt-4 border-t border-slate-900">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Reviewer Resolution Notes
              </h3>
              <textarea 
                v-model="reviewNote"
                rows="3"
                placeholder="Provide reasoning, notes, or categorization justification (optional)..."
                class="w-full rounded-2xl border border-slate-800 bg-slate-900/20 p-4 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none resize-none transition-all shadow-inner"
              ></textarea>
            </div>
          </div>

          <!-- Detail Actions Bar (Approve / Reject) -->
          <div class="border-t border-slate-850 bg-slate-900/60 p-6 flex items-center justify-end gap-3.5 shrink-0">
            <button 
              @click="submitReview('rejected')" 
              :disabled="actioning"
              class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-red-500/10 hover:from-red-500 hover:to-rose-500 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed transition-all"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Reject Content
            </button>
            <button 
              @click="submitReview('approved')" 
              :disabled="actioning"
              class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-green-500/10 hover:from-green-500 hover:to-emerald-500 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed transition-all"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Approve Content
            </button>
          </div>
        </div>

        <!-- Right Pane: Empty State -->
        <div v-else class="flex h-full flex-col items-center justify-center text-center p-8 bg-slate-950 select-none">
          <!-- Outer circles for premium look -->
          <div class="relative flex items-center justify-center mb-6">
            <div class="absolute h-36 w-36 rounded-full bg-blue-500/5 animate-ping duration-1000"></div>
            <div class="absolute h-24 w-24 rounded-full bg-blue-500/10 blur-xl"></div>
            <div class="relative flex h-20 w-20 items-center justify-center rounded-2xl border border-slate-800 bg-slate-900 shadow-xl">
              <svg class="h-10 w-10 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3m0 18h3a2 2 0 002-2v-3M3 16v3a2 2 0 002 2h3m8-9a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
          </div>
          <h2 class="text-xl font-bold text-white mb-2">Select an item from the queue</h2>
          <p class="text-sm text-slate-500 max-w-sm leading-relaxed">
            Click on any submitted post, ticket, or report in the left sidebar to analyze its content, review heuristic scans, and make a decision.
          </p>
        </div>
      </main>
    </div>

    <!-- Submit modal component -->
    <SubmitItemModal 
      :isOpen="isModalOpen" 
      @close="isModalOpen = false" 
      @submitted="handleItemSubmitted"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import SubmitItemModal from './SubmitItemModal.vue';

// Local Reactive State
const items = ref([]);
const counts = ref({ all: 0, pending: 0, approved: 0, rejected: 0 });
const activeItemId = ref(null);
const loading = ref(false);
const actioning = ref(false);
const reviewNote = ref('');
const isModalOpen = ref(false);

const filters = reactive({
  search: '',
  status: 'pending', // Default view is pending queue
  sort: 'newest'
});

// Computed Active Item detail
const activeItem = computed(() => {
  return items.value.find(item => item.id === activeItemId.value) || null;
});

// Set Status Tab filter
const setStatusFilter = (status) => {
  filters.status = status;
  fetchItems();
};

// Fetch items from backend API
const fetchItems = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/items', { params: filters });
    items.value = response.data.items;
    counts.value = response.data.counts;

    // Reset active item if it is no longer in the fetched list
    if (activeItemId.value && !items.value.some(item => item.id === activeItemId.value)) {
      activeItemId.value = null;
    }

    // Auto-select the first pending/item if none is selected
    if (!activeItemId.value && items.value.length > 0) {
      activeItemId.value = items.value[0].id;
    }
  } catch (err) {
    console.error('Error fetching review items:', err);
  } finally {
    loading.value = false;
  }
};

// Select a specific card
const selectItem = (id) => {
  activeItemId.value = id;
  reviewNote.value = '';
};

// Formatter functions
const formatFlagName = (flag) => {
  if (flag === 'financial_keywords') return 'Financial Keywords';
  if (flag === 'external_links') return 'External Links';
  if (flag === 'urgent_language') return 'Urgent Intent';
  return flag;
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const formatRelativeTime = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  const now = new Date();
  const diffMs = now - date;
  const diffSec = Math.floor(diffMs / 1000);
  const diffMin = Math.floor(diffSec / 60);
  const diffHr = Math.floor(diffMin / 60);

  if (diffSec < 60) return 'just now';
  if (diffMin < 60) return `${diffMin}m ago`;
  if (diffHr < 24) return `${diffHr}h ago`;
  return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
};

// Submit Item review resolution (Approve / Reject) with Optimistic UI updates
const submitReview = async (status) => {
  if (!activeItemId.value) return;

  actioning.value = true;
  const currentId = activeItemId.value;
  const nextItem = getNextPendingItemAfter(currentId);

  // Optimistic UI update: instantly transition the status locally
  const currentItemIndex = items.value.findIndex(item => item.id === currentId);
  if (currentItemIndex !== -1) {
    items.value[currentItemIndex].status = status;
  }

  // Instantly slide selection to the next pending item in the queue for smooth reviewer flow
  if (nextItem) {
    activeItemId.value = nextItem.id;
  } else {
    activeItemId.value = null;
  }

  const payload = {
    status: status,
    reviewer_note: reviewNote.value
  };
  reviewNote.value = '';

  try {
    // Send PATCH review request asynchronously
    await axios.patch(`/api/items/${currentId}/review`, payload);
    
    // Refresh the actual queue status list silently in the background
    await fetchItemsSilent();
  } catch (err) {
    console.error('Failed to submit review resolution:', err);
    // Revert if error occurs (optional, just trigger full fetch)
    fetchItems();
  } finally {
    actioning.value = false;
  }
};

// Get the next item to review
const getNextPendingItemAfter = (currentItemId) => {
  const currentIdx = items.value.findIndex(item => item.id === currentItemId);
  if (currentIdx === -1) return null;

  // Search forward for the next pending item
  for (let i = currentIdx + 1; i < items.value.length; i++) {
    if (items.value[i].status === 'pending') {
      return items.value[i];
    }
  }

  // If none found forward, search backward from current index
  for (let i = currentIdx - 1; i >= 0; i--) {
    if (items.value[i].status === 'pending') {
      return items.value[i];
    }
  }
  return null;
};

// Silently refresh items counts and lists
const fetchItemsSilent = async () => {
  try {
    const response = await axios.get('/api/items', { params: filters });
    items.value = response.data.items;
    counts.value = response.data.counts;

    // Reselect the active item if it is still valid
    if (activeItemId.value && !items.value.some(item => item.id === activeItemId.value)) {
      if (items.value.length > 0) {
        activeItemId.value = items.value[0].id;
      } else {
        activeItemId.value = null;
      }
    }
  } catch (err) {
    console.error('Error in silent fetch:', err);
  }
};

// Callback when SubmitItemModal submits successfully
const handleItemSubmitted = (newItem) => {
  // Add item locally to the active queue if it matches active filters
  if (filters.status === 'all' || filters.status === 'pending') {
    // Prepend newly created item instantly to the top of the list for visual confirmation
    items.value.unshift(newItem);
    activeItemId.value = newItem.id; // Auto select the newly created item
  }
  
  // Refresh standard counts and list states silently to align with sorting configurations
  fetchItemsSilent();
};

onMounted(() => {
  fetchItems();
});
</script>

<style>
/* Custom styled premium transitions and webkit scrolls */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}
::-webkit-scrollbar-track {
  background: transparent;
}
::-webkit-scrollbar-thumb {
  background: #334155;
  border-radius: 99px;
}
::-webkit-scrollbar-thumb:hover {
  background: #475569;
}
</style>
