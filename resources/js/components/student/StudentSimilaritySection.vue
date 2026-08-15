<template>
  <section class="space-y-6">
    <!-- ── Page header ──────────────────────────────────────────────── -->
    <div>
      <h2 class="text-2xl font-bold text-slate-900">{{ $t('nav.similarity_report') }}</h2>
      <p class="mt-1 text-slate-500 text-sm">
        {{ $t('student.simreport.subtitle') }}
      </p>
    </div>

    <!-- No active proposal -->
    <div v-if="!activeProposal" class="rounded-xl border border-slate-200 bg-white p-12 shadow-sm text-center">
      <p class="text-slate-500">{{ $t('student.simreport.need_confirm') }}</p>
      <button @click="$emit('navigate', 'Project Workspace')" class="mt-4 text-teal-600 font-medium hover:text-teal-700">{{ $t('student.overview.go_to_workspace') }}</button>
    </div>

    <template v-else>

      <!-- Proposal context -->
      <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
        <span>{{ $t('student.simreport.for') }}</span>
        <span class="font-semibold text-slate-800">{{ activeProposal.title }}</span>
        <span v-if="activeProposal.domain" class="text-slate-300">·</span>
        <span v-if="activeProposal.domain">{{ activeProposal.domain }}</span>
        <span v-if="activeProposal.submission_status === 'draft'"
          class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500">{{ $t('student.simreport.draft_badge') }}</span>
      </div>

      <!-- ══ STATE: analysis running ══════════════════════════════════ -->
      <div v-if="aiStatus === 'pending'" class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4">
        <svg class="h-5 w-5 animate-spin text-amber-500" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
        </svg>
        <p class="text-sm font-medium text-amber-800">{{ $t('student.simreport.running') }}</p>
      </div>

      <!-- ══ STATE: analysis unavailable / failed ═════════════════════ -->
      <div v-else-if="aiStatus === 'failed'" class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
          <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414"/></svg>
        </div>
        <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $t('student.simreport.unavailable_title') }}</h3>
        <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">
          {{ $t('student.simreport.unavailable_desc') }}
        </p>
        <button @click="$emit('recheck')" class="mt-5 inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-slate-800">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
          {{ $t('student.simreport.try_again') }}
        </button>
      </div>

      <!-- ══ STATE: never analyzed ════════════════════════════════════ -->
      <div v-else-if="aiStatus === 'none'" class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-teal-50">
          <svg class="h-6 w-6 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
        </div>
        <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $t('student.simreport.none_title') }}</h3>
        <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">{{ $t('student.simreport.none_desc') }}</p>
        <button @click="$emit('recheck')" class="mt-5 inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-teal-700">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          {{ $t('student.simreport.run_check') }}
        </button>
      </div>

      <!-- ══ STATE: analyzed, no similar projects ═════════════════════ -->
      <div v-else-if="!hasMatches" class="flex items-start gap-4 rounded-xl border border-teal-200 bg-teal-50 p-6 shadow-sm">
        <svg class="mt-0.5 h-6 w-6 shrink-0 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
          <h3 class="text-base font-bold text-teal-900">{{ $t('student.simreport.no_matches_title') }}</h3>
          <p class="mt-1 text-sm text-teal-700">
            {{ aiStatus === 'no_comparisons'
              ? $t('student.simreport.no_comparisons')
              : $t('student.simreport.distinct') }}
          </p>
          <button @click="$emit('recheck')" class="mt-3 text-sm font-semibold text-teal-700 hover:text-teal-800">{{ $t('student.simreport.recheck') }} <span class="rtl:hidden">→</span><span class="hidden rtl:inline">←</span></button>
        </div>
      </div>

      <!-- ══ STATE: matches found ═════════════════════════════════════ -->
      <template v-else>

        <!-- Summary line -->
        <div class="flex flex-wrap items-center justify-between gap-3">
          <i18n-t keypath="student.simreport.summary" tag="p" class="text-sm text-slate-600" scope="global">
            <template #count><span class="font-semibold text-slate-900">{{ topMatches.length }}</span></template>
          </i18n-t>
          <div class="flex items-center gap-3">
            <span v-if="formattedAnalyzedAt" class="text-[11px] text-slate-400">{{ $t('student.simreport.last_analyzed', { date: formattedAnalyzedAt }) }}</span>
            <button @click="$emit('recheck')" class="inline-flex items-center gap-1.5 rounded-md bg-teal-50 px-3 py-1.5 text-xs font-medium text-teal-600 transition-colors hover:text-teal-800">
              <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
              {{ $t('student.simreport.recheck') }}
            </button>
          </div>
        </div>

        <!-- Closest match — the star -->
        <div v-if="closestMatch" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
          <div class="flex items-center gap-2 border-b border-slate-100 bg-slate-50/60 px-6 py-3">
            <span class="rounded-full bg-slate-900 px-2 py-0.5 text-[11px] font-bold text-white">#1</span>
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('student.simreport.closest_match') }}</span>
          </div>
          <div class="p-6">
            <!-- Hidden for privacy -->
            <div v-if="closestMatch.details_hidden" class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
              <svg class="mt-0.5 h-5 w-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
              <div>
                <p class="text-sm font-semibold text-slate-900">{{ $t('student.simreport.details_hidden') }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ closestMatch.explanation }}</p>
              </div>
            </div>

            <template v-else>
              <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                  <h3 class="text-lg font-bold text-slate-900">{{ closestMatch.title }}</h3>
                  <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                    <span>{{ $t('fields.domain') }}: <span class="font-medium text-slate-700">{{ closestMatch.domain ?? $t('common.not_available') }}</span></span>
                    <span v-if="closestMatch.year">{{ $t('student.simreport.year') }}: <span class="font-medium text-slate-700">{{ closestMatch.year }}</span></span>
                  </div>
                </div>
                <div class="text-end">
                  <div class="text-2xl font-black" :class="scoreTextClass(closestMatch.final_score)">{{ closestMatch.score }}</div>
                  <div class="text-[11px] text-slate-400">{{ $t('student.simreport.similarity_label') }}</div>
                </div>
              </div>

              <div class="mt-3">
                <span v-if="closestMatch.verdict" :class="verdictBadgeClass(closestMatch.verdict)"
                  class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold">
                  {{ closestMatch.verdict }}
                </span>
              </div>

              <div v-if="closestMatch.explanation" class="mt-4 rounded-lg border border-slate-100 bg-slate-50 p-4">
                <p class="mb-1 text-xs font-bold uppercase tracking-wide text-slate-500">{{ $t('student.simreport.why_flagged') }}</p>
                <p class="text-sm text-slate-700">{{ closestMatch.explanation }}</p>
              </div>
            </template>
          </div>
        </div>

        <!-- Other matches -->
        <div v-if="otherMatches.length" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
            <h3 class="text-sm font-bold text-slate-900">
              {{ $t('student.simreport.other_projects') }} <span class="font-normal text-slate-400">({{ otherMatches.length }})</span>
            </h3>
          </div>
          <ul class="divide-y divide-slate-100">
            <li v-for="(m, i) in otherMatches" :key="i" class="flex items-center gap-4 px-6 py-3.5 transition-colors hover:bg-slate-50/50">
              <span class="w-6 shrink-0 text-xs font-bold text-slate-300">#{{ i + 2 }}</span>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-slate-800">
                  <span v-if="m.details_hidden" class="italic text-slate-500">🔒 {{ $t('student.simreport.hidden_privacy') }}</span>
                  <template v-else>{{ m.title }}</template>
                </p>
                <p class="truncate text-xs text-slate-400">{{ m.domain ?? $t('common.not_available') }}</p>
              </div>
              <span v-if="m.verdict" :class="verdictBadgeClass(m.verdict)" class="hidden shrink-0 rounded-full border px-2.5 py-0.5 text-[11px] font-semibold sm:inline-flex">
                {{ m.verdict }}
              </span>
              <span class="w-14 shrink-0 text-end text-sm font-bold" :class="scoreTextClass(m.final_score)">{{ m.score }}</span>
            </li>
          </ul>
        </div>

        <!-- Suggested alternative directions -->
        <div v-if="recommendations && recommendations.length > 0" class="rounded-xl border border-teal-200 bg-teal-50/20 p-6 shadow-sm">
          <div class="mb-4 flex items-start gap-3">
            <svg class="mt-0.5 h-6 w-6 shrink-0 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
            <div>
              <h3 class="text-sm font-bold text-teal-900">{{ $t('student.simreport.suggestions_title') }}</h3>
              <p class="text-xs text-teal-700">{{ $t('student.simreport.suggestions_desc') }}</p>
            </div>
          </div>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div v-for="rec in recommendations" :key="rec.title" class="flex flex-col justify-between rounded-xl border border-teal-100 bg-white p-4 shadow-sm">
              <div>
                <h5 class="text-sm font-semibold leading-snug text-slate-900">{{ rec.title }}</h5>
                <p class="mt-1 text-[10px] font-bold text-slate-400">{{ $t('fields.domain') }}: {{ rec.domain }}</p>
                <p class="mt-2 line-clamp-3 text-xs leading-normal text-slate-600">{{ rec.explanation }}</p>
              </div>
              <div class="mt-3 border-t border-slate-50 pt-3 text-xs text-slate-500">{{ $t('student.simreport.relevance') }}: {{ rec.relevance }}</div>
            </div>
          </div>
        </div>

      </template>

      <!-- Footer -->
      <div class="pt-2 text-center">
        <button @click="$emit('navigate', 'Project Workspace')" class="inline-flex items-center text-sm font-medium text-slate-600 transition-colors hover:text-slate-900">
          <svg class="me-1.5 h-4 w-4 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
          {{ $t('student.simreport.back_workspace') }}
        </button>
      </div>

    </template>
  </section>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  activeProposal: { type: Object, default: null },
  topMatches: { type: Array, default: () => [] },
  /** Kept for compatibility with the parent; the list drives the view */
  summary: { type: Object, default: null },
  /** 'pending' | 'success' | 'failed' | 'no_comparisons' | 'none' */
  aiStatus: { type: String, default: 'none' },
  recommendations: { type: Array, default: () => [] },
  /** ISO timestamp of when the analysis was last run */
  analyzedAt: { type: String, default: null },
})

defineEmits(['navigate', 'recheck'])

const hasMatches = computed(() => (props.topMatches?.length ?? 0) > 0)
const closestMatch = computed(() => props.topMatches?.[0] ?? null)
const otherMatches = computed(() => (props.topMatches ?? []).slice(1))

const formattedAnalyzedAt = computed(() => {
  if (!props.analyzedAt) return null
  const d = new Date(props.analyzedAt)
  if (isNaN(d.getTime())) return null
  return d.toLocaleString(undefined, {
    day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
  })
})

// Semantic-embedding scores are compressed (same-domain proposals commonly
// land at 30–40%), so only a strong match reads as high.
function scoreTextClass(v) {
  const n = parseFloat(v) || 0
  return n < 60 ? 'text-teal-600' : n < 80 ? 'text-amber-500' : 'text-red-500'
}

function verdictBadgeClass(verdict) {
  if (!verdict) return 'bg-slate-100 text-slate-600 border-slate-200'
  const v = verdict.toLowerCase()
  if (v.includes('very high')) return 'bg-red-50 text-red-700 border-red-200'
  if (v.includes('high'))      return 'bg-orange-50 text-orange-700 border-orange-200'
  if (v.includes('moderate'))  return 'bg-amber-50 text-amber-700 border-amber-200'
  if (v.includes('low'))       return 'bg-teal-50 text-teal-700 border-teal-200'
  return 'bg-slate-100 text-slate-600 border-slate-200'
}
</script>
