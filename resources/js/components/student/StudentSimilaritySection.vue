<template>
  <section class="space-y-6">

    <!-- ── Header ───────────────────────────────────────────────────── -->
    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 pb-4">
      <div class="min-w-0">
        <h2 class="text-xl font-bold text-slate-900">{{ $t('nav.similarity_report') }}</h2>
        <div v-if="activeProposal" class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500">
          <span class="font-medium text-slate-800">{{ activeProposal.title }}</span>
          <span v-if="activeProposal.domain" class="text-slate-300">·</span>
          <span v-if="activeProposal.domain">{{ activeProposal.domain }}</span>
          <span v-if="activeProposal.submission_status === 'draft'"
            class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-600">{{ $t('student.simreport.draft_badge') }}</span>
        </div>
        <p v-else class="mt-1 text-sm text-slate-500">{{ $t('student.simreport.subtitle') }}</p>
      </div>

      <div v-if="activeProposal && aiStatus === 'success'" class="flex items-center gap-3">
        <span v-if="formattedAnalyzedAt" class="hidden text-xs text-slate-400 sm:inline-block">
          {{ $t('student.simreport.last_analyzed', { date: formattedAnalyzedAt }) }}
        </span>
        <button
          @click="$emit('recheck')"
          :disabled="isChecking"
          class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
        >
          <svg class="h-3.5 w-3.5 text-slate-500" :class="{ 'animate-spin': isChecking }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
          {{ isChecking ? $t('student.simreport.checking') : $t('student.simreport.recheck') }}
        </button>
      </div>
    </div>

    <!-- ══ STATE: No active proposal ══════════════════════════════════ -->
    <div v-if="!activeProposal" class="rounded-xl border border-slate-200 bg-white p-12 text-center shadow-sm">
      <p class="text-slate-500 text-sm">{{ $t('student.simreport.need_confirm') }}</p>
      <button @click="$emit('navigate', 'Project Workspace')" class="mt-3 text-sm font-medium text-teal-600 hover:text-teal-700">
        {{ $t('student.overview.go_to_workspace') }} <span class="rtl:rotate-180 inline-block">→</span>
      </button>
    </div>

    <template v-else>

      <!-- ══ STATE: analysis running ══════════════════════════════════ -->
      <div v-if="aiStatus === 'pending'" class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50/60 px-5 py-4 text-amber-900 shadow-2xs">
        <svg class="h-5 w-5 animate-spin text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
        </svg>
        <p class="flex-1 text-sm font-medium">{{ $t('student.simreport.running') }}</p>
        <!-- Fail-safe: a check that gets interrupted (server restart, lost
             connection) can be left showing this state with nothing to move
             it forward. The backend auto-recovers a stuck pending after 3
             minutes, but this button means the student is never stuck
             waiting on that — they can always force it themselves. -->
        <button @click="$emit('recheck')" :disabled="isChecking" class="shrink-0 text-xs font-semibold text-amber-800 underline decoration-dotted underline-offset-2 hover:text-amber-900 disabled:cursor-not-allowed disabled:opacity-50">
          {{ $t('student.simreport.recheck') }}
        </button>
      </div>

      <!-- ══ STATE: analysis unavailable / failed ═════════════════════ -->
      <div v-else-if="aiStatus === 'failed'" class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm space-y-3">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <h3 class="text-base font-semibold text-slate-900">{{ $t('student.simreport.unavailable_title') }}</h3>
        <p class="mx-auto max-w-md text-sm text-slate-500">{{ $t('student.simreport.unavailable_desc') }}</p>
        <button @click="$emit('recheck')" :disabled="isChecking" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50">
          {{ isChecking ? $t('student.simreport.checking') : $t('student.simreport.try_again') }}
        </button>
      </div>

      <!-- ══ STATE: never analyzed ════════════════════════════════════ -->
      <div v-else-if="aiStatus === 'none'" class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm space-y-3">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-teal-50 text-teal-600">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
        </div>
        <h3 class="text-base font-semibold text-slate-900">{{ $t('student.simreport.none_title') }}</h3>
        <p class="mx-auto max-w-md text-sm text-slate-500">{{ $t('student.simreport.none_desc') }}</p>
        <button @click="$emit('recheck')" :disabled="isChecking" class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-2xs transition hover:bg-teal-700 disabled:cursor-not-allowed disabled:opacity-50">
          {{ isChecking ? $t('student.simreport.checking') : $t('student.simreport.run_check') }}
        </button>
      </div>

      <!-- ══ STATE: analyzed, no similar projects ═════════════════════ -->
      <div v-else-if="!hasMatches" class="flex items-start gap-4 rounded-xl border border-teal-200 bg-teal-50/60 p-5 shadow-2xs">
        <svg class="h-6 w-6 shrink-0 text-teal-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
          <h3 class="text-base font-bold text-teal-900">{{ $t('student.simreport.no_matches_title') }}</h3>
          <p class="mt-1 text-sm text-teal-800 leading-relaxed">
            {{ aiStatus === 'no_comparisons'
              ? $t('student.simreport.no_comparisons')
              : $t('student.simreport.distinct') }}
          </p>
        </div>
      </div>

      <!-- ══ STATE: matches found ═════════════════════════════════════ -->
      <template v-else>

        <!-- A. Executive Score Summary Card -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
          <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $t('student.simreport.overall_title') }}</p>
          
          <div class="flex flex-wrap items-baseline gap-x-4 gap-y-2">
            <span class="text-5xl font-extrabold tracking-tight leading-none" :class="tone(closestMatch.verdict).text">
              {{ closestMatch.final_score }}%
            </span>
            <span v-if="closestMatch.verdict" class="rounded-full border px-3 py-1 text-xs font-semibold" :class="tone(closestMatch.verdict).badge">
              {{ verdictLabel(closestMatch.verdict) }}
            </span>
          </div>

          <p class="text-sm text-slate-600 leading-relaxed">{{ overallDescription }}</p>

          <div v-if="closestMatch.explanation" class="rounded-lg border border-slate-100 bg-slate-50 p-3.5 text-xs text-slate-600">
            <span class="font-semibold text-slate-700">{{ $t('student.simreport.why_flagged') }}:</span> 
            <span class="ms-1">{{ closestMatch.explanation }}</span>
          </div>
        </div>

        <!-- B. Similarity Breakdown (6 Indicators) -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $t('student.simreport.breakdown_title') }}</h3>
            <span v-if="highestOverlapDim" class="text-xs text-slate-500 font-medium">
              {{ $t('student.simreport.highest_overlap_tag', { name: $t(highestOverlapDim.labelKey), value: highestOverlapDim.value }) }}
            </span>
          </div>

          <div class="space-y-3">
            <div v-for="dim in breakdownDims" :key="dim.key" class="space-y-1">
              <div class="flex items-center justify-between text-xs">
                <span class="font-medium text-slate-700">
                  {{ $t(dim.labelKey) }}
                  <span class="text-[10px] text-slate-400 font-normal ms-1">· {{ $t(dim.methodKey) }}</span>
                </span>
                <span class="font-semibold" :class="dim.value !== null ? tone(null, dim.value).text : 'text-slate-400 italic'">
                  {{ dim.value !== null ? dim.value + '%' : $t('student.simreport.not_evaluated') }}
                </span>
              </div>
              <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100 rtl:[transform:scaleX(-1)]">
                <div 
                  v-if="dim.value !== null" 
                  class="h-full rounded-full transition-all duration-500" 
                  :class="tone(null, dim.value).bar" 
                  :style="{ width: dim.value + '%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- C. Matched Projects Explorer -->
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
          
          <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-3.5 sm:flex sm:items-center sm:justify-between sm:gap-4 space-y-3 sm:space-y-0">
            <h3 class="text-sm font-bold text-slate-900">{{ $t('student.simreport.top_matches_title') }}</h3>

            <div class="flex flex-wrap items-center gap-2">
              <!-- Search Box -->
              <input 
                v-model="searchQuery" 
                type="text" 
                :placeholder="$t('student.simreport.search_placeholder')"
                class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:border-teal-500 focus:outline-none"
              />

              <!-- Filter Tabs -->
              <div class="flex items-center rounded-lg bg-slate-200/60 p-0.5 text-[11px] font-medium text-slate-600">
                <button 
                  v-for="filter in ['all', 'high', 'moderate', 'low']" 
                  :key="filter"
                  @click="activeFilter = filter"
                  class="rounded-md px-2.5 py-1 transition"
                  :class="activeFilter === filter ? 'bg-white text-slate-900 shadow-2xs font-semibold' : 'hover:text-slate-900'"
                >
                  {{ $t(`student.simreport.filter_${filter}`) }}
                </button>
              </div>
            </div>
          </div>

          <div v-if="filteredMatches.length === 0" class="p-8 text-center text-sm text-slate-400">
            No matching projects found.
          </div>

          <ul v-else class="divide-y divide-slate-100">
            <li 
              v-for="(m, i) in filteredMatches" 
              :key="i" 
              class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-3.5 transition hover:bg-slate-50/50"
              :class="i === 0 && activeFilter === 'all' && !searchQuery ? 'bg-teal-50/20' : ''"
            >
              <div class="flex items-start gap-3 min-w-0 flex-1">
                <span class="mt-0.5 text-xs font-bold w-5 shrink-0" :class="i === 0 ? 'text-teal-700' : 'text-slate-400'">
                  #{{ i + 1 }}
                </span>

                <div class="min-w-0 flex-1 space-y-0.5">
                  <div class="flex flex-wrap items-center gap-2">
                    <p class="truncate text-sm font-semibold text-slate-900">
                      <span v-if="m.details_hidden" class="italic text-slate-500 font-normal">🔒 {{ $t('student.simreport.hidden_privacy') }}</span>
                      <template v-else>{{ m.title }}</template>
                    </p>
                    <span v-if="i === 0 && activeFilter === 'all' && !searchQuery" class="rounded-full bg-teal-100 px-2 py-0.5 text-[10px] font-bold text-teal-800">
                      {{ $t('student.simreport.closest_match') }}
                    </span>
                  </div>

                  <p class="truncate text-xs text-slate-400">
                    {{ m.details_hidden ? $t('student.simreport.privacy_notice_desc') : (m.domain ?? $t('common.not_available')) }}
                  </p>
                </div>
              </div>

              <!-- Actions & Score -->
              <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0">
                <span v-if="m.verdict" class="rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="tone(m.verdict).badge">
                  {{ verdictLabel(m.verdict) }}
                </span>

                <span class="text-sm font-bold w-12 text-end" :class="tone(m.verdict).text">
                  {{ m.final_score }}%
                </span>

                <!-- Side by Side Compare Action Button -->
                <button 
                  v-if="!m.details_hidden" 
                  @click="openSideBySideModal(m)"
                  class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 shadow-2xs hover:bg-slate-50 transition"
                >
                  {{ $t('student.simreport.compare_side_by_side') }}
                </button>
                <button 
                  v-else 
                  @click="showPrivacyModal = true"
                  class="rounded-lg border border-slate-100 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-400 cursor-not-allowed"
                >
                  {{ $t('student.simreport.privacy_protected') }}
                </button>
              </div>
            </li>
          </ul>
        </div>

        <!-- D. Suggested Alternative Directions -->
        <div v-if="recommendations && recommendations.length > 0" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
          <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $t('student.simreport.suggestions_title') }}</h3>
            <p class="mt-1 text-xs text-slate-500">{{ $t('student.simreport.suggestions_desc') }}</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div 
              v-for="rec in recommendations" 
              :key="rec.title" 
              class="flex flex-col justify-between rounded-lg border border-slate-200 p-4 bg-slate-50/50 hover:bg-white transition shadow-2xs"
            >
              <div class="space-y-2">
                <div class="flex items-start justify-between gap-2">
                  <h5 class="text-sm font-semibold text-slate-900 leading-snug">{{ rec.title }}</h5>
                  <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600">
                    {{ rec.relevance || 'High' }}
                  </span>
                </div>

                <p class="text-[11px] text-slate-400 font-medium">
                  {{ $t('fields.domain') }}: {{ rec.domain }}
                </p>

                <p class="text-xs leading-relaxed text-slate-600 line-clamp-3">
                  {{ rec.explanation }}
                </p>
              </div>

              <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                <button 
                  @click="copyRecommendation(rec)"
                  class="text-xs font-medium text-teal-600 hover:text-teal-700 transition"
                >
                  {{ $t('student.simreport.copy_suggestion') }}
                </button>
              </div>
            </div>
          </div>
        </div>

      </template>

      <!-- Footer Navigation -->
      <div class="pt-2 text-center">
        <button 
          @click="$emit('navigate', 'Project Workspace')" 
          class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-slate-900 transition"
        >
          <svg class="h-4 w-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
          {{ $t('student.simreport.back_workspace') }}
        </button>
      </div>

    </template>

    <!-- ══ MODAL: Side-by-Side Comparison ═════════════════════════════ -->
    <div v-if="selectedCompareMatch" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
      <div class="relative w-full max-w-4xl rounded-2xl bg-white shadow-xl overflow-hidden flex flex-col max-h-[85vh] border border-slate-200">
        
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 bg-slate-50">
          <div>
            <h3 class="text-base font-bold text-slate-900">{{ $t('student.simreport.modal_compare_title') }}</h3>
            <p class="text-xs text-slate-500">Comparing active proposal content against historical project.</p>
          </div>
          <button @click="selectedCompareMatch = null" class="rounded-lg p-1 text-slate-400 hover:bg-slate-200 hover:text-slate-700 transition">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- Modal Body (Scrollable Side-by-Side Columns) -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Left Column: Student's Proposal -->
            <div class="rounded-xl border border-teal-200 bg-white p-5 space-y-4">
              <div class="border-b border-teal-100 pb-3">
                <span class="inline-block rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-semibold text-teal-700 border border-teal-200">
                  {{ $t('student.simreport.modal_your_proposal') }}
                </span>
                <h4 class="mt-2 text-base font-bold text-slate-900">{{ activeProposal?.title }}</h4>
                <p class="text-xs text-slate-500">{{ activeProposal?.domain || 'General' }}</p>
              </div>

              <div>
                <h5 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">{{ $t('fields.problem_statement') }}</h5>
                <p class="text-xs leading-relaxed text-slate-700 bg-slate-50 p-3 rounded-lg border border-slate-100">{{ activeProposal?.problem || 'Not specified' }}</p>
              </div>

              <div>
                <h5 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">{{ $t('fields.proposed_solution') }}</h5>
                <p class="text-xs leading-relaxed text-slate-700 bg-slate-50 p-3 rounded-lg border border-slate-100">{{ activeProposal?.solution || 'Not specified' }}</p>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <h5 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">{{ $t('fields.tags') }}</h5>
                  <p class="text-xs text-slate-600 bg-slate-50 p-2 rounded-lg border border-slate-100">{{ activeProposal?.tags || 'None' }}</p>
                </div>
                <div>
                  <h5 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">{{ $t('fields.technologies') }}</h5>
                  <p class="text-xs text-slate-600 bg-slate-50 p-2 rounded-lg border border-slate-100">{{ activeProposal?.tech || 'None' }}</p>
                </div>
              </div>
            </div>

            <!-- Right Column: Historical Match -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
              <div class="border-b border-slate-200 pb-3 flex items-start justify-between">
                <div>
                  <span class="inline-block rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                    {{ $t('student.simreport.modal_matched_proposal', { year: selectedCompareMatch.year || 'Historical' }) }}
                  </span>
                  <h4 class="mt-2 text-base font-bold text-slate-900">{{ selectedCompareMatch.title }}</h4>
                  <p class="text-xs text-slate-500">{{ selectedCompareMatch.domain || 'Repository' }}</p>
                </div>
                <span class="text-base font-bold" :class="tone(selectedCompareMatch.verdict).text">
                  {{ selectedCompareMatch.final_score }}%
                </span>
              </div>

              <div>
                <h5 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">{{ $t('fields.problem_statement') }}</h5>
                <p class="text-xs leading-relaxed text-slate-700 bg-slate-50 p-3 rounded-lg border border-slate-100">{{ selectedCompareMatch.problem || selectedCompareMatch.description || 'Details unavailable' }}</p>
              </div>

              <div>
                <h5 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">{{ $t('fields.proposed_solution') }}</h5>
                <p class="text-xs leading-relaxed text-slate-700 bg-slate-50 p-3 rounded-lg border border-slate-100">{{ selectedCompareMatch.solution || 'Details unavailable' }}</p>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <h5 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">{{ $t('fields.tags') }}</h5>
                  <p class="text-xs text-slate-600 bg-slate-50 p-2 rounded-lg border border-slate-100">{{ selectedCompareMatch.tags || 'None' }}</p>
                </div>
                <div>
                  <h5 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">{{ $t('fields.technologies') }}</h5>
                  <p class="text-xs text-slate-600 bg-slate-50 p-2 rounded-lg border border-slate-100">{{ selectedCompareMatch.tech || selectedCompareMatch.technologies || 'None' }}</p>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- Modal Footer -->
        <div class="border-t border-slate-200 px-6 py-3 bg-slate-50 flex justify-end">
          <button @click="selectedCompareMatch = null" class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800 transition">
            {{ $t('student.simreport.close') }}
          </button>
        </div>
      </div>
    </div>

    <!-- ══ MODAL: Privacy Protection Notice ══════════════════════════ -->
    <div v-if="showPrivacyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
      <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-slate-200 space-y-4">
        <h4 class="text-base font-bold text-slate-900">{{ $t('student.simreport.privacy_notice_title') }}</h4>
        <p class="text-xs leading-relaxed text-slate-600">
          {{ $t('student.simreport.privacy_notice_desc') }}
        </p>
        <div class="pt-2 flex justify-end">
          <button @click="showPrivacyModal = false" class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800 transition">
            {{ $t('student.simreport.close') }}
          </button>
        </div>
      </div>
    </div>

  </section>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vue-toastification'

const { t } = useI18n()
const toast = useToast()

const props = defineProps({
  activeProposal: { type: Object, default: null },
  topMatches: { type: Array, default: () => [] },
  summary: { type: Object, default: null },
  /** 'pending' | 'success' | 'failed' | 'no_comparisons' | 'none' */
  aiStatus: { type: String, default: 'none' },
  recommendations: { type: Array, default: () => [] },
  analyzedAt: { type: String, default: null },
  /** True while a fetch (initial check or recheck) is in flight — disables the recheck/try-again/run-check buttons. */
  isChecking: { type: Boolean, default: false },
})

defineEmits(['navigate', 'recheck'])

// Interactive States
const searchQuery = ref('')
const activeFilter = ref('all') // 'all' | 'high' | 'moderate' | 'low'
const selectedCompareMatch = ref(null)
const showPrivacyModal = ref(false)

const hasMatches = computed(() => (props.topMatches?.length ?? 0) > 0)
const closestMatch = computed(() => props.topMatches?.[0] ?? {})

const formattedAnalyzedAt = computed(() => {
  if (!props.analyzedAt) return null
  const d = new Date(props.analyzedAt)
  if (isNaN(d.getTime())) return null
  return d.toLocaleString(undefined, {
    day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
  })
})

const breakdownDims = computed(() => {
  const m = closestMatch.value ?? {}
  return [
    { key: 'problem',      labelKey: 'student.simreport.dim_problem',      methodKey: 'student.simreport.method_semantic',     value: m.problem_similarity ?? null },
    { key: 'solution',     labelKey: 'student.simreport.dim_solution',     methodKey: 'student.simreport.method_semantic',     value: m.solution_similarity ?? null },
    { key: 'objectives',   labelKey: 'student.simreport.dim_objectives',   methodKey: 'student.simreport.method_semantic',     value: m.objectives_similarity ?? null },
    { key: 'functions',    labelKey: 'student.simreport.dim_functions',    methodKey: 'student.simreport.method_semantic_set', value: m.functions_similarity ?? null },
    { key: 'tags',         labelKey: 'student.simreport.dim_tags',         methodKey: 'student.simreport.method_lexical',      value: m.tags_similarity ?? null },
    { key: 'tech',         labelKey: 'student.simreport.dim_tech',         methodKey: 'student.simreport.method_lexical',      value: m.technologies_similarity ?? null },
  ]
})

const highestOverlapDim = computed(() => {
  const valid = breakdownDims.value.filter(d => d.value !== null && d.value > 0)
  if (valid.length === 0) return null
  return valid.reduce((max, d) => (d.value > max.value ? d : max), valid[0])
})

const filteredMatches = computed(() => {
  let list = props.topMatches || []

  // Apply Risk Tier Filter — m.score is a formatted "63%" string (not
  // numeric), which silently broke every comparison below (NaN >= 60 is
  // always false). m.final_score is the real number; use that.
  if (activeFilter.value === 'high') {
    list = list.filter(m => (m.final_score ?? 0) >= 60)
  } else if (activeFilter.value === 'moderate') {
    list = list.filter(m => {
      const s = m.final_score ?? 0
      return s >= 30 && s < 60
    })
  } else if (activeFilter.value === 'low') {
    list = list.filter(m => (m.final_score ?? 0) < 30)
  }

  // Apply Search Query
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim()
    list = list.filter(m => {
      const title = (m.title || '').toLowerCase()
      const domain = (m.domain || '').toLowerCase()
      return title.includes(q) || domain.includes(q)
    })
  }

  return list
})

// The backend sends the verdict as a fixed English string (see
// server_dense.py's compute_verdict()); translate the known set for
// display while keeping the raw string for tone()/description matching.
function verdictLabel(verdict) {
  const v = (verdict || '').toLowerCase()
  if (v.includes('very high')) return t('student.simreport.verdict_very_high')
  if (v.includes('high'))      return t('student.simreport.verdict_high')
  if (v.includes('moderate'))  return t('student.simreport.verdict_moderate')
  if (v.includes('low'))       return t('student.simreport.verdict_low')
  if (v.includes('no matches')) return t('student.simreport.verdict_no_matches')
  return verdict
}

const overallDescription = computed(() => {
  const v = (closestMatch.value?.verdict || '').toLowerCase()
  if (v.includes('very high')) return t('student.simreport.overall_desc_very_high')
  if (v.includes('high'))      return t('student.simreport.overall_desc_high')
  if (v.includes('moderate'))  return t('student.simreport.overall_desc_moderate')
  return t('student.simreport.overall_desc_low')
})

function tone(verdict, fallbackPercent = null) {
  const v = (verdict || '').toLowerCase()
  if (v.includes('very high')) return { text: 'text-red-600',   badge: 'bg-red-50 text-red-700 border-red-200',       bar: 'bg-red-500' }
  if (v.includes('high'))      return { text: 'text-orange-600',badge: 'bg-orange-50 text-orange-700 border-orange-200', bar: 'bg-orange-400' }
  if (v.includes('moderate'))  return { text: 'text-amber-600', badge: 'bg-amber-50 text-amber-700 border-amber-200',   bar: 'bg-amber-400' }
  if (v.includes('low'))       return { text: 'text-teal-600',  badge: 'bg-teal-50 text-teal-700 border-teal-200',     bar: 'bg-teal-500' }

  const n = fallbackPercent ?? 0
  if (n >= 85) return { text: 'text-red-600', bar: 'bg-red-500' }
  if (n >= 70) return { text: 'text-orange-600', bar: 'bg-orange-400' }
  if (n >= 55) return { text: 'text-amber-600', bar: 'bg-amber-400' }
  return { text: 'text-teal-600', bar: 'bg-teal-500' }
}

function openSideBySideModal(match) {
  selectedCompareMatch.value = match
}

function copyRecommendation(rec) {
  const text = `${rec.title}\nDomain: ${rec.domain}\n${rec.explanation}`
  navigator.clipboard.writeText(text).then(() => {
    toast.success(t('student.simreport.copied_toast'))
  }).catch(() => {
    toast.info(`${rec.title} - ${rec.explanation}`)
  })
}
</script>
