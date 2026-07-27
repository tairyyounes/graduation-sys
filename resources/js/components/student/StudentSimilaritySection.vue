<template>
  <section class="space-y-6">
    <div class="mb-8">
      <h2 class="text-2xl font-bold text-slate-900">Similarity Report</h2>
      <p class="mt-1 text-slate-500 text-sm">AI-based semantic comparison for the active proposal.</p>
    </div>

    <!-- No active proposal -->
    <div v-if="!activeProposal" class="rounded-xl border border-slate-200 bg-white p-12 shadow-sm text-center">
      <p class="text-slate-500">You must confirm a proposal to view its similarity report.</p>
      <button @click="$emit('navigate', 'Project Workspace')" class="mt-4 text-teal-600 font-medium hover:text-teal-700">Go to Workspace</button>
    </div>

    <template v-else>

      <!-- ── AI Status Banner ─────────────────────────────────────────── -->
      <div v-if="aiStatus === 'pending'" class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4">
        <svg class="h-5 w-5 animate-spin text-amber-500" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
        </svg>
        <p class="text-sm font-medium text-amber-800">AI similarity analysis is running… results will appear shortly.</p>
      </div>
      <div v-else-if="aiStatus === 'failed'" class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-4">
        <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        <p class="text-sm font-medium text-red-800">AI analysis failed. The proposal was still saved. Retry by re-submitting the proposal.</p>
      </div>
      <div v-else-if="aiStatus === 'no_comparisons'" class="flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 px-5 py-4">
        <svg class="h-5 w-5 text-blue-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm font-medium text-blue-800">
          No previous proposals available for comparison. This proposal will be used as a baseline for future similarity checks.
        </p>
      </div>

      <!-- ── Current-Year Confirmed Proposal Warning Banner ────────────────── -->
      <div v-if="hasHiddenMatch" class="flex items-start gap-4 rounded-xl border border-red-200 bg-red-50/50 p-6 shadow-sm border-l-4 border-l-red-500 mb-6">
        <div class="mt-0.5 text-red-500 shrink-0">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
          </svg>
        </div>
        <div>
          <h4 class="text-sm font-bold text-red-900 mb-1">Warning</h4>
          <p class="text-sm text-red-700 font-medium">
            High similarity detected with an approved project from the current academic year. The project details are hidden for privacy reasons. Please consider adjusting your project scope or selecting a different direction.
          </p>
        </div>
      </div>

      <!-- ── Top Cards ───────────────────────────────────────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        <!-- Active Proposal Summary Card -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
          <div class="flex justify-between items-start mb-4">
            <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider">
              {{ activeProposal.submission_status === 'draft' ? 'Draft Proposal Summary' : 'Active Proposal Summary' }}
            </p>
            <!-- Verdict badge (from AI) -->
            <span v-if="summary?.verdict" :class="verdictBadgeClass(summary.verdict)"
              class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold border shadow-sm">
              {{ summary.verdict }}
            </span>
            <!-- Fallback risk badge based on score -->
            <span v-else-if="overallScore !== null" :class="[
              overallScore < 30 ? 'bg-teal-50 text-teal-700 border-teal-200'
              : overallScore < 60 ? 'bg-amber-50 text-amber-700 border-amber-200'
              : 'bg-red-50 text-red-700 border-red-200',
              'inline-flex items-center rounded-full px-3 py-1 text-xs font-bold border shadow-sm'
            ]">
              {{ overallScore < 30 ? 'Low Risk' : overallScore < 60 ? 'Medium Risk' : 'High Risk' }}
            </span>
          </div>
          <h3 class="text-xl font-bold text-slate-900 mb-2">{{ activeProposal.title }}</h3>
          <div class="flex flex-wrap gap-x-6 gap-y-2 mt-4 text-sm text-slate-600">
            <div><span class="font-medium text-slate-900">Domain:</span> {{ activeProposal.domain }}</div>
            <div><span class="font-medium text-slate-900">Status:</span> {{ activeProposal.status }}</div>
            <div>
              <span class="font-medium text-slate-900">AI:</span>
              <span :class="aiStatusClass" class="ml-1 font-semibold capitalize">{{ aiStatus }}</span>
            </div>
          </div>
        </div>

        <!-- Main Similarity Score Card -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col items-center justify-center text-center relative overflow-hidden">
          <div class="relative z-10">
            <p class="text-sm font-semibold text-slate-500 mb-2">Overall Similarity</p>
            <div v-if="overallScore !== null"
              class="text-5xl font-black mb-2"
              :class="overallScore < 30 ? 'text-teal-600' : overallScore < 60 ? 'text-amber-500' : 'text-red-500'">
              {{ overallScore }}<span class="text-3xl text-slate-400">%</span>
            </div>
            <div v-else class="text-2xl font-bold text-slate-400 mb-2">—</div>
            <p class="text-xs text-slate-500 mt-2 px-4">
              {{ overallScore !== null
                ? 'Final weighted score from the AI engine combining all similarity dimensions.'
                : 'Waiting for AI analysis to complete.' }}
            </p>
          </div>
        </div>
      </div>

      <!-- ── AI Explanation Card ────────────────────────────────────── -->
      <div v-if="summary?.explanation"
        class="mb-6 rounded-xl border border-slate-200 bg-gradient-to-r from-slate-50 to-white p-6 shadow-sm border-l-4"
        :class="overallScore !== null && overallScore < 30 ? 'border-l-teal-500' : overallScore !== null && overallScore < 60 ? 'border-l-amber-500' : 'border-l-red-500'">
        <div class="flex items-start gap-4">
          <div class="mt-1" :class="overallScore !== null && overallScore < 30 ? 'text-teal-500' : overallScore !== null && overallScore < 60 ? 'text-amber-500' : 'text-red-500'">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div>
            <h4 class="text-sm font-bold text-slate-900 mb-1">AI Explanation</h4>
            <p class="text-sm text-slate-700">{{ summary.explanation }}</p>
          </div>
        </div>
      </div>

      <!-- ── Similarity Breakdown (real AI dimensions) ─────────────── -->
      <h3 class="text-lg font-semibold text-slate-900 mb-4 mt-8">Similarity Breakdown</h3>
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">

        <div v-for="dim in breakdownDimensions" :key="dim.key" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium text-slate-500 mb-1">{{ dim.label }}</p>
          <p class="text-lg font-bold"
            :class="dim.value === null ? 'text-slate-300'
                  : dim.value < 30 ? 'text-teal-600'
                  : dim.value < 60 ? 'text-amber-500'
                  : 'text-red-500'">
            {{ dim.value !== null ? dim.value + '%' : '—' }}
          </p>
          <!-- Mini bar -->
          <div class="mt-2 h-1.5 rounded-full bg-slate-100">
            <div class="h-1.5 rounded-full transition-all"
              :class="dim.value === null ? 'bg-slate-200'
                    : dim.value < 30 ? 'bg-teal-400'
                    : dim.value < 60 ? 'bg-amber-400'
                    : 'bg-red-400'"
              :style="{ width: (dim.value ?? 0) + '%' }">
            </div>
          </div>
        </div>

      </div>

      <!-- ── Top Similar Projects ───────────────────────────────────── -->
      <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
          <h3 class="text-base font-semibold text-slate-900">Top Similar Projects in Database</h3>
          <button @click="$emit('recheck')" class="text-xs font-medium text-teal-600 hover:text-teal-800 bg-teal-50 px-3 py-1.5 rounded-md transition-colors">
            Recheck Similarity
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-white">
              <tr>
                <th class="py-3.5 pl-6 pr-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Rank</th>
                <th class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Project Title</th>
                <th class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Domain</th>
                <th class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Final Score</th>
                <th class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Verdict</th>
                <th class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider hidden lg:table-cell">Semantic</th>
                <th class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider hidden lg:table-cell">Functions</th>
                <th class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider hidden xl:table-cell">Objectives</th>
                <th class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider hidden xl:table-cell">Tags</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
              <!-- No results yet -->
              <tr v-if="!topMatches || topMatches.length === 0">
                <td colspan="9" class="py-10 text-center text-sm text-slate-400">
                  <template v-if="aiStatus === 'pending'">⏳ Analysis is running…</template>
                  <template v-else-if="aiStatus === 'failed'">❌ Analysis failed. No matches available.</template>
                  <template v-else-if="aiStatus === 'no_comparisons'">ℹ️ No previous proposals available for comparison.</template>
                  <template v-else>No similar proposals found in the database.</template>
                </td>
              </tr>
              <tr v-for="(match, index) in topMatches" :key="index" class="hover:bg-slate-50/50 transition-colors">
                <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-slate-400">#{{ index + 1 }}</td>
                <td class="px-3 py-4 text-sm font-medium text-slate-900 max-w-xs truncate">{{ match.title }}</td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600">{{ match.domain ?? 'N/A' }}</td>
                <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold"
                  :class="(match.final_score ?? 0) < 30 ? 'text-teal-600' : (match.final_score ?? 0) < 60 ? 'text-amber-600' : 'text-red-600'">
                  {{ match.score }}
                </td>
                <td class="whitespace-nowrap px-3 py-4">
                  <span v-if="match.verdict" :class="verdictBadgeClass(match.verdict)"
                    class="inline-block rounded-full px-2.5 py-1 text-xs font-semibold border">
                    {{ match.verdict }}
                  </span>
                  <span v-else class="text-slate-400 text-xs">—</span>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600 hidden lg:table-cell">
                  {{ match.semantic_similarity !== null ? match.semantic_similarity + '%' : '—' }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600 hidden lg:table-cell">
                  {{ match.functions_similarity !== null ? match.functions_similarity + '%' : '—' }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600 hidden xl:table-cell">
                  {{ match.objectives_similarity !== null ? match.objectives_similarity + '%' : '—' }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600 hidden xl:table-cell">
                  {{ match.tags_similarity !== null ? match.tags_similarity + '%' : '—' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ── Explanation column for top match ──────────────────────── -->
      <div v-if="topMatches && topMatches[0]?.explanation"
        class="mt-4 rounded-lg border border-slate-100 bg-slate-50 px-5 py-4 text-sm text-slate-600">
        <span class="font-semibold text-slate-800">Top match note: </span>{{ topMatches[0].explanation }}
      </div>

      <!-- ── AI Recommendations ──────────────────────────────────────── -->
      <div v-if="recommendations && recommendations.length > 0" class="mt-8 rounded-xl border border-teal-200 bg-teal-50/20 p-6 shadow-sm text-left">
        <div class="flex items-start gap-4 mb-4">
          <div class="text-teal-600 shrink-0">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
          </div>
          <div>
            <h4 class="text-sm font-bold text-teal-900 mb-1">AI Project Recommendations</h4>
            <p class="text-xs text-teal-700">The AI suggests these alternative directions in the same domain that are unique:</p>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div v-for="rec in recommendations" :key="rec.title" class="bg-white p-4 rounded-xl border border-teal-100 shadow-sm flex flex-col justify-between">
            <div>
              <h5 class="text-sm font-semibold text-slate-900 leading-snug">{{ rec.title }}</h5>
              <p class="text-[10px] font-bold text-slate-400 mt-1">Domain: {{ rec.domain }}</p>
              <p class="text-xs text-slate-600 mt-2 leading-normal line-clamp-3">{{ rec.explanation }}</p>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-50 flex justify-between items-center text-xs">
              <span class="text-slate-500 font-medium">Relevance: {{ rec.relevance }}</span>
              <span class="text-teal-600 font-semibold">Unique Option</span>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-8 text-center">
        <button @click="$emit('navigate', 'Project Workspace')" class="inline-flex items-center text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
          <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
          Back to Workspace
        </button>
      </div>

    </template>
  </section>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  activeProposal: {
    type: Object,
    default: null,
  },
  topMatches: {
    type: Array,
    default: () => [],
  },
  /** Full breakdown summary from the AI (for the top card) */
  summary: {
    type: Object,
    default: null,
  },
  /** 'pending' | 'success' | 'failed' | 'none' */
  aiStatus: {
    type: String,
    default: 'none',
  },
  recommendations: {
    type: Array,
    default: () => [],
  },
})

defineEmits(['navigate', 'recheck'])

// ── Computed ────────────────────────────────────────────────────────────────

/** Overall score as a number (0–100), or null if not available */
const overallScore = computed(() => {
  if (props.summary?.final_score != null) return props.summary.final_score
  return null
})

const hasHiddenMatch = computed(() => {
  return props.topMatches.some(m => m.details_hidden);
})

/** CSS class for the AI status text label */
const aiStatusClass = computed(() => {
  if (props.aiStatus === 'success')        return 'text-teal-600'
  if (props.aiStatus === 'pending')        return 'text-amber-500'
  if (props.aiStatus === 'failed')         return 'text-red-500'
  if (props.aiStatus === 'no_comparisons') return 'text-blue-500'
  return 'text-slate-400'
})

/** The 5 AI breakdown dimensions */
const breakdownDimensions = computed(() => [
  { key: 'semantic',      label: 'Semantic',      value: props.summary?.semantic_similarity     ?? null },
  { key: 'functions',     label: 'Functions',     value: props.summary?.functions_similarity    ?? null },
  { key: 'objectives',    label: 'Objectives',    value: props.summary?.objectives_similarity   ?? null },
  { key: 'tags',          label: 'Tags',          value: props.summary?.tags_similarity         ?? null },
  { key: 'technologies',  label: 'Technologies',  value: props.summary?.technologies_similarity ?? null },
])

// ── Helpers ──────────────────────────────────────────────────────────────────

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
