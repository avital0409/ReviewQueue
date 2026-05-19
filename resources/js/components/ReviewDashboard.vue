<template>
  <div
    class="flex h-screen w-screen flex-col overflow-hidden bg-slate-50 font-sans text-slate-800 antialiased"
  >
    <!-- Header -->
    <header
      class="flex h-16 shrink-0 items-center justify-between border-b border-slate-200 bg-white px-6 shadow-sm z-10"
    >
      <div class="flex items-center gap-3">
        <div
          class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 shadow-md"
        >
          <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2.5"
              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
            />
          </svg>
        </div>
        <div>
          <h1 class="text-lg font-bold tracking-tight text-slate-900 flex items-center gap-2">
            ModHub
          </h1>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="flex items-center gap-6 text-sm h-full font-bold">
        <button
          :class="[
            'h-full px-2 border-b-2 transition-all duration-200',
            activeTab === 'queue'
              ? 'text-blue-600 border-blue-600'
              : 'text-slate-500 hover:text-slate-800 border-transparent',
          ]"
          @click="activeTab = 'queue'"
        >
          Moderation Queue
        </button>
        <button
          :class="[
            'h-full px-2 border-b-2 transition-all duration-200',
            activeTab === 'users'
              ? 'text-blue-600 border-blue-600'
              : 'text-slate-500 hover:text-slate-800 border-transparent',
          ]"
          @click="
            activeTab = 'users';
            fetchUsers();
          "
        >
          User Directory
        </button>
      </div>

      <div class="flex items-center gap-4">
        <button
          class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/10 hover:from-blue-500 hover:to-indigo-500 transition-all focus:outline-none"
          @click="isModalOpen = true"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2.5"
              d="M12 4v16m8-8H4"
            />
          </svg>
          Submit Content
        </button>
      </div>
    </header>

    <!-- TAB 1: Moderation Queue -->
    <div v-if="activeTab === 'queue'" class="flex min-h-0 flex-1 flex-row">
      <!-- Left Pane: Sidebar Queue -->
      <aside class="flex w-[600px] shrink-0 flex-col border-r border-slate-200 bg-white">
        <!-- Search, Sort & Status Filters -->
        <div class="border-b border-slate-100 p-4 space-y-4">
          <!-- Search input -->
          <div class="relative">
            <div
              class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                />
              </svg>
            </div>
            <input
              v-model="filters.search"
              type="text"
              placeholder="Search content, author, flags..."
              class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-4 text-sm text-slate-800 placeholder-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none transition-all shadow-sm"
              @input="fetchItems"
            />
          </div>

          <!-- Status Filters Row -->
          <div class="flex items-center gap-1 bg-slate-50 p-1 rounded-xl border border-slate-100">
            <button
              v-for="statusOpt in ['all', 'pending', 'approved', 'rejected', 'blocked']"
              :key="statusOpt"
              :class="[
                'flex-1 py-1.5 px-2 rounded-lg text-xs font-semibold uppercase tracking-wider transition-all flex items-center justify-center gap-1.5',
                filters.status === statusOpt
                  ? 'bg-white text-slate-800 shadow-sm border border-slate-200'
                  : 'text-slate-500 hover:text-slate-800',
              ]"
              @click="setStatusFilter(statusOpt)"
            >
              {{ statusOpt }}
              <span
                class="rounded-full bg-slate-100/80 px-1.5 py-0.5 text-[10px] font-bold text-slate-500 border border-slate-200"
              >
                {{ counts[statusOpt] }}
              </span>
            </button>
          </div>

          <!-- Sort Selector -->
          <div class="flex items-center justify-between text-xs">
            <span class="font-medium text-slate-400">Sort by</span>
            <select
              v-model="filters.sort"
              class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-slate-700 focus:border-blue-500 focus:outline-none transition-all shadow-sm"
              @change="fetchItems"
            >
              <option value="newest">Newest First</option>
              <option value="risk_desc">Risk: High to Low</option>
              <option value="risk_asc">Risk: Low to High</option>
            </select>
          </div>
        </div>

        <!-- Scrollable Cards Queue -->
        <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
          <!-- Error state -->
          <div
            v-if="itemsError"
            class="flex flex-col items-center justify-center py-16 px-6 text-center gap-3.5"
          >
            <div
              class="h-12 w-12 rounded-full bg-red-50 flex items-center justify-center text-red-500 border border-red-100"
            >
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                />
              </svg>
            </div>
            <div class="space-y-1">
              <span class="text-sm font-bold text-slate-700">Failed to Load Queue</span>
              <p class="text-xs text-slate-400 max-w-[240px] leading-relaxed">{{ itemsError }}</p>
            </div>
            <button
              class="rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-600 transition-all shadow-sm"
              @click="fetchItems"
            >
              Retry Connection
            </button>
          </div>

          <!-- Loading state -->
          <div
            v-else-if="loading && filteredItems.length === 0"
            class="flex flex-col items-center justify-center py-16 text-slate-400 gap-3"
          >
            <svg class="animate-spin h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24">
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
            <span class="text-xs font-semibold tracking-wider uppercase">Loading queue...</span>
          </div>

          <!-- Empty Queue state -->
          <div
            v-else-if="filteredItems.length === 0"
            class="flex flex-col items-center justify-center py-16 px-4 text-center text-slate-400 gap-2"
          >
            <svg
              class="h-10 w-10 text-slate-300"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
              />
            </svg>
            <span class="text-sm font-bold text-slate-500">Queue Empty</span>
            <span class="text-xs">No items matching your criteria were found.</span>
          </div>

          <!-- Cards list -->
          <ModerationItemCard
            v-for="item in filteredItems"
            :key="item.id"
            :item="item"
            :is-active="activeItemId === item.id"
            :format-relative-time="formatRelativeTime"
            @select="selectItem"
          />
        </div>
      </aside>

      <!-- Right Pane: Active Item Detail & Actions -->
      <main class="flex flex-1 flex-col overflow-hidden bg-slate-50 relative">
        <div v-if="activeItem" class="flex h-full flex-col">
          <!-- Detail Header -->
          <div
            class="border-b border-slate-200 bg-white p-6 flex items-center justify-between shrink-0 shadow-sm z-10"
          >
            <div class="space-y-1">
              <div class="flex items-center gap-3">
                <h2 class="text-lg font-bold text-slate-900">Item #{{ activeItem.id }}</h2>
                <StatusBadge type="status" :value="activeItem.status" class="text-xs px-2.5 py-0.5" />
                <!-- Header Ban Signifier -->
                <span
                  v-if="activeItem.author_is_banned"
                  class="rounded-full bg-red-600 px-3 py-0.5 text-xs font-extrabold text-white uppercase border border-red-700 shadow-md"
                  >Banned Submitter</span
                >
              </div>
              <p class="text-xs text-slate-500">
                Submitted by
                <span class="font-semibold text-slate-700">{{ activeItem.author_email }}</span> •
                {{ formatRelativeTime(activeItem.created_at) }} ({{
                  formatDate(activeItem.created_at)
                }})
              </p>
            </div>
          </div>

          <!-- Detail Body Scrollable -->
          <div class="flex-1 overflow-y-auto p-6 space-y-6">
            <div
              v-if="activeItem.author_rejections_count >= 2 && activeItem.status === 'pending'"
              class="p-4 rounded-2xl border flex items-center gap-3.5 shadow-sm transform transition-all duration-300 animate-pulse"
              :class="
                isBanEscalated
                  ? 'bg-red-50 border-red-200 text-red-800'
                  : 'bg-amber-50 border-amber-200 text-amber-800'
              "
            >
              <div class="text-2xl">⚠️</div>
              <div class="space-y-0.5 flex-1">
                <div class="text-sm font-bold">
                  {{
                    isBanEscalated
                      ? 'Strike Escalation: Permanent Ban Recommended!'
                      : 'Repeat Offender Alert: User is at strike limit!'
                  }}
                </div>
                <div class="text-xs">
                  Submitter has
                  <strong>{{ activeItem.author_rejections_count }} prior rejections</strong>.
                  {{
                    isBanEscalated
                      ? 'This post triggers rejection recommendations. Rejecting this content will ban this user from submissions.'
                      : 'Exercise caution. Further rejections will result in a permanent ban.'
                  }}
                </div>
              </div>
            </div>

            <!-- 1. Submission Content Box -->
            <div class="space-y-2.5">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                Submission Content
              </h3>
              <div
                class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-100/50 relative overflow-hidden group"
              >
                <div
                  class="absolute inset-0 bg-gradient-to-b from-blue-500/2 to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none duration-500"
                ></div>
                <p class="text-slate-800 text-sm leading-relaxed whitespace-pre-wrap font-sans">
                  {{ activeItem.content }}
                </p>
              </div>
            </div>

            <!-- 2. Heuristic Analysis Dashboard card -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
              <!-- Score dial gauge card -->
              <div
                class="rounded-2xl border border-slate-200 bg-white p-5 flex flex-col items-center justify-center text-center relative overflow-hidden group shadow-sm shadow-slate-100/50"
              >
                <span
                  class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-3 block"
                  >Heuristic Risk Score</span
                >

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
                      :stroke="
                        activeItem.risk_score >= 75
                          ? '#ef4444'
                          : activeItem.risk_score >= 25
                            ? '#f59e0b'
                            : '#10b981'
                      "
                      stroke-width="8"
                      fill="transparent"
                      stroke-dasharray="251.2"
                      :stroke-dashoffset="251.2 - (251.2 * activeItem.risk_score) / 100"
                      stroke-linecap="round"
                      class="transition-all duration-1000 ease-out"
                    />
                  </svg>

                  <div class="absolute flex flex-col items-center justify-center z-10">
                    <span class="text-3xl font-extrabold tracking-tight text-slate-800">{{
                      activeItem.risk_score
                    }}</span>
                    <span class="text-[10px] uppercase font-bold text-slate-450">Risk</span>
                  </div>
                </div>

                <!-- Rating badge text -->
                <span
                  :class="[
                    'mt-3.5 rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider border',
                    activeItem.risk_score >= 75 ? 'bg-red-50 text-red-700 border-red-200' : '',
                    activeItem.risk_score >= 25 && activeItem.risk_score < 75
                      ? 'bg-amber-50 text-amber-700 border-amber-200'
                      : '',
                    activeItem.risk_score < 25 ? 'bg-green-50 text-green-700 border-green-200' : '',
                  ]"
                >
                  {{
                    activeItem.risk_score >= 75
                      ? 'CRITICAL RISK'
                      : activeItem.risk_score >= 25
                        ? 'MEDIUM RISK'
                        : 'LOW RISK'
                  }}
                </span>
              </div>

              <!-- Recommendation badge card -->
              <div
                class="rounded-2xl border border-slate-200 bg-white p-5 flex flex-col justify-between relative overflow-hidden group shadow-sm shadow-slate-100/50"
              >
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block"
                  >Heuristic Auto-Action</span
                >

                <div class="my-4 flex items-center gap-3">
                  <div
                    :class="[
                      'h-12 w-12 rounded-xl flex items-center justify-center border',
                      isBanEscalated ? 'bg-red-50 text-red-600 border-red-200 animate-pulse' : '',
                      !isBanEscalated && activeItem.auto_suggestion === 'reject'
                        ? 'bg-red-50 text-red-600 border-red-100'
                        : '',
                      activeItem.auto_suggestion === 'approve'
                        ? 'bg-emerald-50 text-emerald-600 border-emerald-100'
                        : '',
                      activeItem.auto_suggestion === 'none'
                        ? 'bg-slate-50 text-slate-500 border-slate-200'
                        : '',
                    ]"
                  >
                    <svg
                      :class="['h-6 w-6', isBanEscalated ? 'text-red-600' : '']"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        v-if="isBanEscalated"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2.5"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                      />
                      <path
                        v-else-if="activeItem.auto_suggestion === 'reject'"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
                      />
                      <path
                        v-else-if="activeItem.auto_suggestion === 'approve'"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                      />
                      <path
                        v-else
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                      />
                    </svg>
                  </div>

                  <div>
                    <span class="text-xs text-slate-400 block font-medium">Auto-Suggestion</span>
                    <span
                      :class="[
                        'text-lg font-bold leading-none uppercase',
                        isBanEscalated ? 'text-red-600' : '',
                        !isBanEscalated && activeItem.auto_suggestion === 'reject'
                          ? 'text-red-600'
                          : '',
                        activeItem.auto_suggestion === 'approve' ? 'text-emerald-600' : '',
                        activeItem.auto_suggestion === 'none' ? 'text-slate-700' : '',
                      ]"
                    >
                      {{
                        isBanEscalated
                          ? 'BAN USER'
                          : activeItem.auto_suggestion === 'none'
                            ? 'MANUAL REVIEW'
                            : activeItem.auto_suggestion
                      }}
                    </span>
                  </div>
                </div>

                <p class="text-[10px] text-slate-455 leading-normal">
                  {{
                    isBanEscalated
                      ? 'Automated Escalation: Submitter has 3+ past rejections and this post is suggested for rejection. Permanent suspension is recommended.'
                      : activeItem.auto_suggestion === 'reject'
                        ? 'Heuristics triggered high risk spam metrics. Rejection is highly recommended.'
                        : activeItem.auto_suggestion === 'approve'
                          ? 'Clean data, cleared automated filters. Approval recommended.'
                          : 'Mixed intent. Requires reviewer manually reading context details.'
                  }}
                </p>
              </div>

              <!-- Flagged indicators card -->
              <div
                class="rounded-2xl border border-slate-200 bg-white p-5 flex flex-col justify-between relative overflow-hidden group shadow-sm shadow-slate-100/50"
              >
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block"
                  >Triggered Flags</span
                >

                <div class="my-3 space-y-1.5 flex-1 flex flex-col justify-center">
                  <div
                    v-if="!activeItem.heuristic_flags || activeItem.heuristic_flags.length === 0"
                    class="text-xs text-slate-400 italic"
                  >
                    No automated risk triggers detected.
                  </div>
                  <div
                    v-for="flag in activeItem.heuristic_flags"
                    :key="flag"
                    :class="[
                      'inline-flex items-center gap-2 rounded-xl py-1.5 px-3 text-xs font-semibold w-full border',
                      flag === 'financial_keywords'
                        ? 'bg-amber-50 text-amber-700 border-amber-200/60'
                        : '',
                      flag === 'external_links'
                        ? 'bg-blue-50 text-blue-700 border-blue-200/60'
                        : '',
                      flag === 'urgent_language'
                        ? 'bg-violet-50 text-violet-700 border-violet-200/60'
                        : '',
                      flag === 'banned_author' ? 'bg-red-50 text-red-700 border-red-200/60' : '',
                    ]"
                  >
                    <span v-if="flag === 'financial_keywords'">💳</span>
                    <span v-else-if="flag === 'external_links'">🔗</span>
                    <span v-else-if="flag === 'urgent_language'">⚠️</span>
                    <span v-else-if="flag === 'banned_author'">🚫</span>
                    {{ formatFlagName(flag) }}
                  </div>
                </div>

                <span class="text-[10px] text-slate-400 block">
                  Scanned synchronously at entry point.
                </span>
              </div>
            </div>

            <!-- 3. Reviewer Action Note / Display -->
            <div class="space-y-2.5 pt-4 border-t border-slate-200">
              <h3
                class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5"
              >
                <svg
                  class="h-4 w-4 text-slate-400"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                  />
                </svg>
                Reviewer Resolution Notes
              </h3>

              <div
                v-if="activeItem.status !== 'pending'"
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-100/50 space-y-2"
              >
                <div class="flex items-center justify-between text-xs text-slate-455">
                  <span class="font-bold uppercase tracking-wider text-blue-600"
                    >Resolution Status: {{ activeItem.status }}</span
                  >
                  <span class="font-medium">{{ formatRelativeTime(activeItem.reviewed_at) }}</span>
                </div>
                <p class="text-sm text-slate-700 leading-relaxed italic whitespace-pre-wrap">
                  {{ activeItem.reviewer_note || 'No resolution notes provided by reviewer.' }}
                </p>
              </div>

              <textarea
                v-else
                v-model="reviewNote"
                rows="3"
                placeholder="Provide reasoning, notes, or categorization justification (optional)..."
                class="w-full rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-800 placeholder-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none resize-none transition-all shadow-sm"
              ></textarea>
            </div>
          </div>

          <!-- Detail Actions Bar (Approve / Reject / Ban) -->
          <div
            v-if="activeItem.status === 'pending'"
            class="border-t border-slate-200 bg-white p-6 flex items-center justify-end gap-3.5 shrink-0 shadow-sm z-10"
          >
            <!-- Split Reject/Ban Button Container -->
            <div
              class="relative inline-flex items-stretch rounded-xl shadow-lg shadow-red-500/10 reject-split-btn-container"
            >
              <!-- Primary Reject & Email button -->
              <button
                :disabled="actioning"
                class="inline-flex items-center gap-2 rounded-l-xl bg-gradient-to-r px-5 py-3.5 text-sm font-semibold text-white focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed transition-all border-r border-red-700/30"
                :class="
                  isBanEscalated
                    ? 'from-red-700 to-rose-700 hover:from-red-600 hover:to-rose-600'
                    : 'from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500'
                "
                @click="openRejectionEmailModal"
              >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2.5"
                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
                {{ isBanEscalated ? 'Reject & Ban Submitter' : 'Reject Content' }}
              </button>

              <!-- Dropdown trigger button -->
              <button
                :disabled="actioning"
                class="inline-flex items-center px-3 rounded-r-xl text-white focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                :class="
                  isBanEscalated
                    ? 'bg-gradient-to-r from-rose-700 to-rose-700 hover:from-rose-600 hover:to-rose-600'
                    : 'bg-gradient-to-r from-rose-600 to-rose-600 hover:from-rose-500 hover:to-rose-500'
                "
                @click="isRejectDropdownOpen = !isRejectDropdownOpen"
              >
                <svg
                  class="h-3 w-3 transform transition-transform duration-250"
                  :class="{ 'rotate-180': isRejectDropdownOpen }"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2.5"
                    d="M19 9l-7 7-7-7"
                  />
                </svg>
              </button>

              <!-- Floating Dropdown list -->
              <div
                v-if="isRejectDropdownOpen"
                class="absolute bottom-full right-0 mb-1 w-56 rounded-2xl border border-slate-200 bg-white/95 backdrop-blur-md p-1.5 shadow-xl shadow-slate-250/50 z-20 animate-in fade-in slide-in-from-bottom-2 duration-150"
              >
                <button
                  class="w-full flex items-center gap-2.5 rounded-xl px-4 py-3 text-left text-xs font-bold text-rose-600 hover:bg-rose-50/80 transition-colors"
                  @click="submitReviewSilently"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
                    />
                  </svg>
                  Reject Silently (No Email)
                </button>
                <!-- Manual Ban trigger in dropdown if not escalated -->
                <button
                  v-if="!isBanEscalated"
                  class="w-full flex items-center gap-2.5 rounded-xl px-4 py-3 text-left text-xs font-bold text-red-700 hover:bg-red-50 transition-colors"
                  @click="openBanEmailModalDirect"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                    />
                  </svg>
                  Reject & Ban User
                </button>
              </div>
            </div>

            <!-- Approve Button -->
            <button
              :disabled="actioning"
              class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-green-500/10 hover:from-green-500 hover:to-emerald-500 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed transition-all"
              @click="submitReview('approved')"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2.5"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              Approve Content
            </button>
          </div>
        </div>

        <!-- Right Pane: Empty State -->
        <div
          v-else
          class="flex h-full flex-col items-center justify-center text-center p-8 bg-slate-50 select-none"
        >
          <div class="relative flex items-center justify-center mb-6">
            <div
              class="absolute h-36 w-36 rounded-full bg-blue-500/5 animate-ping duration-1000"
            ></div>
            <div class="absolute h-24 w-24 rounded-full bg-blue-500/5 blur-xl"></div>
            <div
              class="relative flex h-20 w-20 items-center justify-center rounded-2xl border border-slate-200 bg-white shadow-md"
            >
              <svg
                class="h-10 w-10 text-blue-500"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.5"
                  d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3m0 18h3a2 2 0 002-2v-3M3 16v3a2 2 0 002 2h3m8-9a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>
            </div>
          </div>
          <h2 class="text-xl font-bold text-slate-800 mb-2">Select an item from the queue</h2>
          <p class="text-sm text-slate-400 max-w-sm leading-relaxed">
            Click on any submitted post, ticket, or report in the left sidebar to analyze its
            content, review heuristic scans, and make a decision.
          </p>
        </div>
      </main>
    </div>

    <!-- TAB 2: User Reputation Directory -->
    <div v-else class="flex min-h-0 flex-1 flex-row">
      <!-- Left Pane: Submitter Directory Grid -->
      <aside class="flex w-[600px] shrink-0 flex-col border-r border-slate-200 bg-white">
        <!-- Search and Sorting -->
        <div class="border-b border-slate-100 p-4 space-y-4">
          <div class="relative">
            <div
              class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                />
              </svg>
            </div>
            <input
              v-model="userFilters.search"
              type="text"
              placeholder="Search submitters by email..."
              class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-4 text-sm text-slate-800 placeholder-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none transition-all shadow-sm"
              @input="fetchUsers"
            />
          </div>

          <div class="flex items-center justify-between text-xs">
            <span class="font-medium text-slate-400">Sort submitters by</span>
            <select
              v-model="userFilters.sort"
              class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-slate-700 focus:border-blue-500 focus:outline-none transition-all shadow-sm"
              @change="fetchUsers"
            >
              <option value="violations">Strikes / Violations</option>
              <option value="total">Total Submissions</option>
              <option value="email">Email Alphabetical</option>
            </select>
          </div>
        </div>

        <!-- Users reputation items list -->
        <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
          <!-- Error state -->
          <div
            v-if="usersError"
            class="flex flex-col items-center justify-center py-16 px-6 text-center gap-3.5"
          >
            <div
              class="h-12 w-12 rounded-full bg-red-50 flex items-center justify-center text-red-500 border border-red-100"
            >
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                />
              </svg>
            </div>
            <div class="space-y-1">
              <span class="text-sm font-bold text-slate-700">Failed to Load Directory</span>
              <p class="text-xs text-slate-400 max-w-[240px] leading-relaxed">{{ usersError }}</p>
            </div>
            <button
              class="rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-600 transition-all shadow-sm"
              @click="fetchUsers"
            >
              Retry Connection
            </button>
          </div>

          <div
            v-else-if="usersLoading"
            class="flex flex-col items-center justify-center py-16 text-slate-400 gap-3"
          >
            <svg class="animate-spin h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24">
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
            <span class="text-xs font-semibold tracking-wider uppercase">Loading Directory...</span>
          </div>

          <div
            v-else-if="users.length === 0"
            class="flex flex-col items-center justify-center py-16 px-4 text-center text-slate-400 gap-2"
          >
            <span class="text-sm font-bold text-slate-500">No Submitters Found</span>
            <span class="text-xs">Try adapting your search filter.</span>
          </div>

          <UserListItem
            v-for="user in users"
            :key="user.author_email"
            :user="user"
            :is-active="activeUserEmail === user.author_email"
            @select="selectUser"
          />
        </div>
      </aside>

      <!-- Right Pane: Submitter Submissions Audit Timeline -->
      <main class="flex flex-1 flex-col overflow-hidden bg-slate-50 relative">
        <div v-if="activeUserEmail" class="flex h-full flex-col">
          <!-- Submitter details header -->
          <div
            class="border-b border-slate-200 bg-white p-6 shrink-0 shadow-sm z-10 flex items-center justify-between"
          >
            <div class="space-y-1">
              <h2 class="text-lg font-bold text-slate-900 truncate max-w-lg">
                {{ activeUserEmail }}
              </h2>
              <p class="text-xs text-slate-500">
                User reputation analysis & submission audit history.
              </p>
            </div>

            <button
              class="inline-flex items-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold border transition-all"
              :class="
                userHistoryDetails?.is_banned
                  ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100 shadow-sm'
                  : 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100 shadow-sm'
              "
              @click="toggleUserBan"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path
                  v-if="userHistoryDetails?.is_banned"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.5"
                  d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"
                />
                <path
                  v-else
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.5"
                  d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"
                />
              </svg>
              {{ userHistoryDetails?.is_banned ? 'Lift Ban' : 'Ban User' }}
            </button>
          </div>

          <!-- Timeline body -->
          <div class="flex-1 overflow-y-auto p-6 space-y-6">
            <!-- Ban Details Alert Box if Banned -->
            <div
              v-if="userHistoryDetails?.is_banned"
              class="p-5 rounded-2xl border border-red-200 bg-red-50/50 text-red-800 shadow-inner flex flex-col gap-1.5"
            >
              <div class="text-sm font-extrabold flex items-center gap-2">
                <span>🚫</span> USER BANNED
              </div>
              <p class="text-xs text-red-700">
                Suspended at <strong>{{ formatDate(userHistoryDetails.banned_at) }}</strong
                >.
              </p>
              <p
                class="text-xs leading-relaxed italic bg-white p-3 rounded-xl border border-red-100 text-red-900 mt-1"
              >
                <strong>Official Ban Reason:</strong> "{{
                  userHistoryDetails.ban_reason || 'Repeated violations of guidelines.'
                }}"
              </p>
            </div>

            <!-- History Summary Stats Card -->
            <div class="grid grid-cols-4 gap-4">
              <div class="bg-white border border-slate-200 rounded-2xl p-4 text-center shadow-sm">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider"
                  >Total Posts</span
                >
                <div class="text-2xl font-black text-slate-800 mt-1">
                  {{ userHistoryDetails?.history?.length || 0 }}
                </div>
              </div>
              <div class="bg-white border border-slate-200 rounded-2xl p-4 text-center shadow-sm">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider"
                  >Approved</span
                >
                <div class="text-2xl font-black text-emerald-600 mt-1">
                  {{
                    userHistoryDetails?.history?.filter((h) => h.status === 'approved').length || 0
                  }}
                </div>
              </div>
              <div class="bg-white border border-slate-200 rounded-2xl p-4 text-center shadow-sm">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider"
                  >Rejections (Strikes)</span
                >
                <div class="text-2xl font-black text-red-600 mt-1">
                  {{
                    userHistoryDetails?.history?.filter((h) => h.status === 'rejected').length || 0
                  }}
                </div>
              </div>
              <div class="bg-white border border-slate-200 rounded-2xl p-4 text-center shadow-sm">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider"
                  >Blocked</span
                >
                <div class="text-2xl font-black text-slate-750 mt-1">
                  {{
                    userHistoryDetails?.history?.filter((h) => h.status === 'blocked').length || 0
                  }}
                </div>
              </div>
            </div>

            <!-- Submission Chronological timeline list -->
            <div class="space-y-4">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                Submission Audit Trails
              </h3>

              <div
                v-if="userHistoryError"
                class="bg-red-50 border border-red-100 rounded-2xl p-5 text-center text-xs text-red-650 space-y-2 max-w-md mx-auto my-6"
              >
                <p><strong>Error:</strong> {{ userHistoryError }}</p>
                <button
                  class="rounded-xl border border-red-200 bg-white hover:bg-red-100/50 px-3 py-1.5 text-[11px] font-semibold text-red-700 transition-all shadow-sm mx-auto block"
                  @click="selectUser(activeUserEmail)"
                >
                  Retry Loading
                </button>
              </div>
              <div
                v-else-if="userHistoryLoading"
                class="text-center py-8 text-xs text-slate-400 italic"
              >
                Loading history timeline...
              </div>
              <div
                v-else-if="!userHistoryDetails?.history || userHistoryDetails.history.length === 0"
                class="text-center py-8 text-xs text-slate-400 italic"
              >
                No submissions recorded for this email address.
              </div>

              <div
                v-for="audit in userHistoryDetails.history"
                v-else
                :key="audit.id"
                class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-3 relative overflow-hidden group hover:border-slate-300 transition-all"
              >
                <!-- Audit Item Header -->
                <div class="flex items-center justify-between text-xs">
                  <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-800">#{{ audit.id }}</span>
                    <StatusBadge type="status" :value="audit.status" />
                  </div>
                  <span class="text-slate-400 font-medium">{{ formatDate(audit.created_at) }}</span>
                </div>

                <!-- Submission Snippet -->
                <p class="text-slate-700 text-sm whitespace-pre-wrap leading-relaxed">
                  {{ audit.content }}
                </p>

                <!-- Reviewer Notes if resolved -->
                <div
                  v-if="audit.reviewer_note"
                  class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-xs italic text-slate-650"
                >
                  <strong>Moderator Notes:</strong> "{{ audit.reviewer_note }}"
                </div>
              </div>
            </div>
          </div>
        </div>
        <div
          v-else
          class="flex h-full flex-col items-center justify-center text-center p-8 bg-slate-50 select-none"
        >
          <div class="relative flex items-center justify-center mb-6">
            <div
              class="absolute h-36 w-36 rounded-full bg-blue-500/5 animate-ping duration-1000"
            ></div>
            <div
              class="relative flex h-20 w-20 items-center justify-center rounded-2xl border border-slate-200 bg-white shadow-md"
            >
              <svg
                class="h-10 w-10 text-blue-500"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.5"
                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                />
              </svg>
            </div>
          </div>
          <h2 class="text-xl font-bold text-slate-800 mb-2">
            Select a submitter from the directory
          </h2>
          <p class="text-sm text-slate-400 max-w-sm leading-relaxed">
            Click on any submitter profile card in the left list view to audit their complete
            history, view approval metrics, and trigger ban states.
          </p>
        </div>
      </main>
    </div>

    <!-- Submit modal component -->
    <SubmitItemModal
      :is-open="isModalOpen"
      @close="isModalOpen = false"
      @submitted="handleItemSubmitted"
    />

    <!-- Rejection & Ban Email Modal component -->
    <RejectionEmailModal
      :is-open="isRejectionEmailModalOpen"
      :item="activeItem"
      :reviewer-note="reviewNote"
      :prefetched-draft="activeItem ? prefetchedDrafts[activeItem.id] : null"
      :is-ban="isBanModalEscalated"
      @close="isRejectionEmailModalOpen = false"
      @confirm="handleRejectionConfirmed"
    />

    <!-- Toast Notification Stack -->
    <ToastNotification
      :notifications="notifications"
      @dismiss="(id) => notifications = notifications.filter((n) => n.id !== id)"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import SubmitItemModal from './SubmitItemModal.vue';
import RejectionEmailModal from './RejectionEmailModal.vue';
import StatusBadge from './StatusBadge.vue';
import ToastNotification from './ToastNotification.vue';
import UserListItem from './UserListItem.vue';
import ModerationItemCard from './ModerationItemCard.vue';

// Tab controls
const activeTab = ref('queue'); // Tabs: 'queue' or 'users'

// Local Queue State
const items = ref([]);
const counts = ref({ all: 0, pending: 0, approved: 0, rejected: 0, blocked: 0 });
const activeItemId = ref(null);
const loading = ref(false);
const actioning = ref(false);
const reviewNote = ref('');
const isModalOpen = ref(false);
const isRejectionEmailModalOpen = ref(false);
const isRejectDropdownOpen = ref(false);
const isManualBanForced = ref(false); // Flags if a ban modal was forced from dropdown manual ban
const prefetchedDrafts = ref({});

// Premium Toast Notifications State
const notifications = ref([]);
const showToast = (message, type = 'success') => {
  const id = Date.now() + Math.random().toString(36).substr(2, 9);
  notifications.value.push({ id, message, type });
  setTimeout(() => {
    notifications.value = notifications.value.filter((n) => n.id !== id);
  }, 4500);
};

// User Directory Directory State
const users = ref([]);
const usersLoading = ref(false);
const activeUserEmail = ref(null);
const userHistoryDetails = ref(null);
const userHistoryLoading = ref(false);

const itemsError = ref(null);
const usersError = ref(null);
const userHistoryError = ref(null);

const filters = reactive({
  search: '',
  status: 'pending',
  sort: 'newest',
});

const userFilters = reactive({
  search: '',
  sort: 'violations',
});

// Strike auto-suggestion escalation checks
const isBanEscalated = computed(() => {
  return (
    activeItem.value &&
    activeItem.value.status === 'pending' &&
    activeItem.value.author_rejections_count >= 2 &&
    activeItem.value.auto_suggestion !== 'approve'
  );
});

// Decides if modal operates in suspension or rejection notice layout
const isBanModalEscalated = computed(() => {
  return isBanEscalated.value || isManualBanForced.value;
});

// Computed Active Queue Item detail
const activeItem = computed(() => {
  return items.value.find((item) => item.id === activeItemId.value) || null;
});

// Computed list of filtered items matching the current status tab filter
const filteredItems = computed(() => {
  if (filters.status === 'all') return items.value;
  return items.value.filter((item) => item.status === filters.status);
});

// Set Status Tab filter
const setStatusFilter = (status) => {
  filters.status = status;
  fetchItems();
};

// Fetch items from queue API
const fetchItems = async () => {
  console.log('[ReviewDashboard] fetchItems called with active filters:', JSON.stringify(filters));
  loading.value = true;
  itemsError.value = null;
  try {
    const response = await axios.get('/api/items', { params: filters });
    items.value = response.data.items;
    counts.value = response.data.counts;

    if (activeItemId.value && !items.value.some((item) => item.id === activeItemId.value)) {
      activeItemId.value = null;
    }

    if (!activeItemId.value && items.value.length > 0) {
      activeItemId.value = items.value[0].id;
    }
    console.log(`[ReviewDashboard] fetchItems succeeded, retrieved ${items.value.length} items.`);
  } catch (err) {
    console.error('[ReviewDashboard] Error fetching review items:', err);
    itemsError.value =
      'Failed to load review items queue. Please check your connection and try again.';
  } finally {
    loading.value = false;
  }
};

// Fetch users directory list
const fetchUsers = async () => {
  console.log('[ReviewDashboard] fetchUsers called with filters:', JSON.stringify(userFilters));
  usersLoading.value = true;
  usersError.value = null;
  try {
    const response = await axios.get('/api/users', { params: userFilters });
    users.value = response.data.users;

    if (
      activeUserEmail.value &&
      !users.value.some((u) => u.author_email === activeUserEmail.value)
    ) {
      activeUserEmail.value = null;
    }
    console.log(`[ReviewDashboard] fetchUsers succeeded, retrieved ${users.value.length} users.`);
  } catch (err) {
    console.error('[ReviewDashboard] Error fetching user directory:', err);
    usersError.value = 'Failed to load user directory. Please try again.';
  } finally {
    usersLoading.value = false;
  }
};

// Select user in Directory list
const selectUser = async (email) => {
  console.log('[ReviewDashboard] selectUser loading history for email:', email);
  activeUserEmail.value = email;
  userHistoryLoading.value = true;
  userHistoryError.value = null;
  try {
    const response = await axios.get(`/api/users/${email}/history`);
    userHistoryDetails.value = response.data;
    console.log(
      '[ReviewDashboard] selectUser loaded reputation details successfully:',
      response.data
    );
  } catch (err) {
    console.error('[ReviewDashboard] Failed to load user history:', err);
    userHistoryError.value = 'Failed to load user reputation history details.';
  } finally {
    userHistoryLoading.value = false;
  }
};

// Toggle ban state on Directory timeline card
const toggleUserBan = async () => {
  if (!activeUserEmail.value) return;
  const isCurrentlyBanned = userHistoryDetails.value?.is_banned;
  const nextAction = isCurrentlyBanned ? 'unban' : 'ban';
  console.log(
    `[ReviewDashboard] toggleUserBan action triggered. Email: ${activeUserEmail.value}, action: ${nextAction}`
  );

  try {
    const res = await axios.post('/api/users/ban', {
      email: activeUserEmail.value,
      action: nextAction,
      reason: 'Banned manually via User reputation directory.',
    });

    // Refresh states
    await selectUser(activeUserEmail.value);
    await fetchUsers();
    await fetchItemsSilent();

    if (nextAction === 'ban') {
      let msg = 'User permanently suspended!';
      if (res.data && res.data.blocked_count > 0) {
        msg += ` ${res.data.blocked_count} pending submissions automatically blocked.`;
      }
      showToast(msg, 'warning');
    } else {
      showToast('Suspension lifted successfully!', 'success');
    }
  } catch (err) {
    console.error('Failed to toggle ban state:', err);
    showToast('Failed to change suspension state.', 'error');
  }
};

// Triggers rejection modal opening
const openRejectionEmailModal = () => {
  isManualBanForced.value = false;
  isRejectionEmailModalOpen.value = true;
};

// Triggers manual ban modal opening via split button dropdown
const openBanEmailModalDirect = () => {
  isRejectDropdownOpen.value = false;
  isManualBanForced.value = true;
  isRejectionEmailModalOpen.value = true;
};

// Proactively prefetch rejection draft
const prefetchRejectionDraft = async (item) => {
  if (!item || item.status !== 'pending') return;

  const currentNote = reviewNote.value;
  if (prefetchedDrafts.value[item.id] && prefetchedDrafts.value[item.id].note === currentNote) {
    return;
  }

  prefetchedDrafts.value[item.id] = { loading: true, draft: '', note: currentNote };

  try {
    const response = await axios.post(`/api/items/${item.id}/rejection-draft`, {
      reviewer_note: currentNote,
    });
    prefetchedDrafts.value[item.id] = {
      loading: false,
      draft: response.data.draft || '',
      note: currentNote,
    };
  } catch (err) {
    console.error('Failed to prefetch rejection email draft:', err);
    delete prefetchedDrafts.value[item.id];
  }
};

// Proactively trigger draft prefetching when selection changes
watch(
  activeItemId,
  (newId) => {
    if (newId) {
      const item = items.value.find((i) => i.id === newId);
      if (item && item.status === 'pending') {
        prefetchRejectionDraft(item);
      }
    }
  },
  { immediate: true }
);

// Select card item in left sidebar queue
const selectItem = (id) => {
  activeItemId.value = id;
  reviewNote.value = '';
};

// Formatter functions
const formatFlagName = (flag) => {
  if (flag === 'financial_keywords') return 'Financial Keywords';
  if (flag === 'external_links') return 'External Links';
  if (flag === 'urgent_language') return 'Urgent Intent';
  if (flag === 'banned_author') return 'Banned Author';
  return flag;
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
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

// Review submission resolutions
const submitReview = async (status) => {
  if (!activeItemId.value) return;

  actioning.value = true;
  const currentId = activeItemId.value;
  console.log('[ReviewDashboard] submitReview resolution triggered:', {
    itemId: currentId,
    status,
    note: reviewNote.value,
  });

  // Optimistic UI updates
  const currentItemIndex = items.value.findIndex((item) => item.id === currentId);
  if (currentItemIndex !== -1) {
    items.value[currentItemIndex].status = status;
    items.value[currentItemIndex].reviewer_note = reviewNote.value;
    items.value[currentItemIndex].reviewed_at = new Date().toISOString();
  }

  activeItemId.value = null;

  const payload = {
    status: status,
    reviewer_note: reviewNote.value,
  };
  reviewNote.value = '';

  try {
    await axios.patch(`/api/items/${currentId}/review`, payload);
    console.log(`[ReviewDashboard] submitReview PATCH resolved successfully for #${currentId}`);
    showToast(`Submission #${currentId} approved successfully!`, 'success');
    await fetchItemsSilent();
  } catch (err) {
    console.error('[ReviewDashboard] Failed to submit review resolution:', err);
    showToast('Failed to approve submission.', 'error');
    fetchItems();
  } finally {
    actioning.value = false;
  }
};

// Handle modal-confirmed rejection/ban actions
const handleRejectionConfirmed = async ({ sendEmail, emailBody, banUser, banReason }) => {
  isRejectionEmailModalOpen.value = false;
  if (!activeItemId.value) return;

  actioning.value = true;
  const currentId = activeItemId.value;
  console.log('[ReviewDashboard] handleRejectionConfirmed triggered:', {
    itemId: currentId,
    sendEmail,
    banUser,
    banReason,
  });

  // Optimistic UI updates
  const currentItemIndex = items.value.findIndex((item) => item.id === currentId);
  if (currentItemIndex !== -1) {
    items.value[currentItemIndex].status = 'rejected';
    items.value[currentItemIndex].reviewer_note = reviewNote.value;
    items.value[currentItemIndex].reviewed_at = new Date().toISOString();
    if (banUser) {
      items.value[currentItemIndex].author_is_banned = 1;
    }
  }

  activeItemId.value = null;

  const payload = {
    status: 'rejected',
    reviewer_note: reviewNote.value,
    send_email: sendEmail,
    email_body: emailBody,
    ban_user: banUser,
    ban_reason: banReason,
  };
  reviewNote.value = '';

  try {
    const res = await axios.patch(`/api/items/${currentId}/review`, payload);
    console.log(
      `[ReviewDashboard] handleRejectionConfirmed PATCH resolved successfully for #${currentId}. Blocked count:`,
      res.data?.blocked_count
    );

    if (banUser) {
      let msg = `Submission #${currentId} rejected & user permanently suspended!`;
      if (res.data && res.data.blocked_count > 0) {
        msg += ` ${res.data.blocked_count} pending items automatically blocked.`;
      }
      showToast(msg, 'warning');
    } else {
      showToast(`Submission #${currentId} rejected successfully.`, 'success');
    }

    await fetchItemsSilent();
  } catch (err) {
    console.error('[ReviewDashboard] Failed to submit review rejection:', err);
    showToast('Failed to reject submission.', 'error');
    fetchItems();
  } finally {
    actioning.value = false;
    isManualBanForced.value = false;
  }
};

// Silent immediate content rejection
const submitReviewSilently = async () => {
  isRejectDropdownOpen.value = false;
  if (!activeItemId.value) return;

  actioning.value = true;
  const currentId = activeItemId.value;
  console.log('[ReviewDashboard] submitReviewSilently triggered:', {
    itemId: currentId,
    note: reviewNote.value,
  });

  // Optimistic UI updates
  const currentItemIndex = items.value.findIndex((item) => item.id === currentId);
  if (currentItemIndex !== -1) {
    items.value[currentItemIndex].status = 'rejected';
    items.value[currentItemIndex].reviewer_note = reviewNote.value;
    items.value[currentItemIndex].reviewed_at = new Date().toISOString();
  }

  activeItemId.value = null;

  const payload = {
    status: 'rejected',
    reviewer_note: reviewNote.value,
    send_email: false,
  };
  reviewNote.value = '';

  try {
    await axios.patch(`/api/items/${currentId}/review`, payload);
    console.log(
      `[ReviewDashboard] submitReviewSilently PATCH resolved successfully for #${currentId}`
    );
    showToast(`Submission #${currentId} silently rejected.`, 'success');
    await fetchItemsSilent();
  } catch (err) {
    console.error('[ReviewDashboard] Failed to submit silent rejection:', err);
    showToast('Failed to reject submission silently.', 'error');
    fetchItems();
  } finally {
    actioning.value = false;
  }
};

const getNextPendingItemAfter = (currentItemId) => {
  const currentIdx = items.value.findIndex((item) => item.id === currentItemId);
  if (currentIdx === -1) return null;

  for (let i = currentIdx + 1; i < items.value.length; i++) {
    if (items.value[i].status === 'pending') {
      return items.value[i];
    }
  }

  for (let i = currentIdx - 1; i >= 0; i--) {
    if (items.value[i].status === 'pending') {
      return items.value[i];
    }
  }
  return null;
};

// Silent background sync
const fetchItemsSilent = async () => {
  try {
    const response = await axios.get('/api/items', { params: filters });
    items.value = response.data.items;
    counts.value = response.data.counts;

    if (activeItemId.value && !items.value.some((item) => item.id === activeItemId.value)) {
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

const handleItemSubmitted = (newItem) => {
  if (filters.status === 'all' || filters.status === 'pending') {
    items.value.unshift(newItem);
    activeItemId.value = newItem.id;
  }
  fetchItemsSilent();
};

const closeRejectDropdown = (e) => {
  if (!e.target.closest('.reject-split-btn-container')) {
    isRejectDropdownOpen.value = false;
  }
};

onMounted(() => {
  fetchItems();
  window.addEventListener('click', closeRejectDropdown);
});

onUnmounted(() => {
  window.removeEventListener('click', closeRejectDropdown);
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
