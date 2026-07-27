<template>
  <section class="space-y-5">
    <router-link :to="{ name: 'DepartmentQueue' }" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-teal-600 transition">
      <svg class="mr-1 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      Back to queue
    </router-link>
    <div>
      <div class="mb-2 flex items-center gap-2 text-xs">
        <span :class="statusClass(selectedProposal.status)" class="rounded-full px-2.5 py-1 font-semibold">{{ formatStatus(selectedProposal.status) }}</span>
        <span class="text-slate-500">{{ selectedProposal.department }}</span>
      </div>
      <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-4xl">{{ selectedProposal.title }}</h1>
      <p class="mt-1 text-sm text-slate-500">{{ selectedProposal.author }} · {{ selectedProposal.author_email }}</p>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
      <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:col-span-4">
        <div class="flex items-center justify-between mb-1">
          <p class="text-sm text-slate-500">Overall similarity</p>
          <!-- AI status indicator -->
          <span v-if="aiStatus === 'pending'" class="flex items-center gap-1 text-xs text-amber-600 font-medium">
            <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
            Pending
          </span>
          <span v-else-if="aiStatus === 'failed'" class="text-xs text-red-500 font-medium">Error</span>
          <span v-else-if="aiStatus === 'success'" class="text-xs text-teal-600 font-medium">✓ AI</span>
        </div>
        <p class="mt-2 text-5xl font-semibold"
          :class="overallScoreNum < 30 ? 'text-teal-700' : overallScoreNum < 60 ? 'text-amber-600' : 'text-red-600'">
          {{ selectedProposal.similarity }}
        </p>
        <div class="mt-3 h-2 rounded-full bg-slate-200">
          <div class="h-2 rounded-full transition-all"
            :class="overallScoreNum < 30 ? 'bg-teal-500' : overallScoreNum < 60 ? 'bg-amber-500' : 'bg-red-500'"
            :style="{ width: overallScoreNum + '%' }">
          </div>
        </div>
        <!-- AI Breakdown mini stats -->
        <div v-if="aiSummary" class="mt-4 space-y-1.5 border-t border-slate-100 pt-3">
          <div v-for="dim in breakdownDimensions" :key="dim.key" class="flex justify-between text-xs">
            <span class="text-slate-500">{{ dim.label }}</span>
            <span class="font-semibold text-slate-700">{{ dim.value !== null ? dim.value + '%' : '—' }}</span>
          </div>
        </div>
        <!-- Verdict badge -->
        <div v-if="aiSummary?.verdict" class="mt-3">
          <span :class="verdictBadgeClass(aiSummary.verdict)"
            class="inline-block rounded-full px-2.5 py-1 text-xs font-semibold border">
            {{ aiSummary.verdict }}
          </span>
        </div>
      </article>
      <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:col-span-8">
        <h2 class="text-lg font-semibold text-slate-900">Description</h2>
        <div class="mt-2 space-y-4">
          <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Problem</h3>
            <p class="text-sm leading-6 text-slate-600">{{ selectedProposal.problem }}</p>
          </div>
          <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Solution</h3>
            <p class="text-sm leading-6 text-slate-600">{{ selectedProposal.solution }}</p>
          </div>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
          <template v-if="selectedProposal.tags">
            <span v-for="tag in selectedProposal.tags.split(',')" :key="tag" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-600">#{{ tag.trim() }}</span>
          </template>
        </div>
      </article>
    </div>

    <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <!-- AI Explanation -->
      <div v-if="aiSummary?.explanation" class="mb-4 rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-600">
        <span class="font-semibold text-slate-800">AI Note: </span>{{ aiSummary.explanation }}
      </div>
      <h2 class="text-lg font-semibold text-slate-900">Closest matches</h2>
      <div class="mt-3 space-y-3">
        <div v-for="match in closestMatches" :key="match.title" class="flex flex-col gap-2 border-t border-slate-100 pt-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="text-sm font-medium text-slate-900">{{ match.title }}</p>
            <p class="text-xs text-slate-500">{{ match.domain ?? 'N/A' }} · {{ match.year }}</p>
          </div>
          <div class="flex items-center gap-2">
            <span :class="verdictBadgeClass(match.verdict)" class="rounded-full px-2.5 py-1 text-xs font-semibold border">{{ match.score }}</span>
            <span v-if="match.verdict" :class="verdictBadgeClass(match.verdict)" class="rounded-full px-2 py-0.5 text-xs border hidden sm:inline-block">{{ match.verdict }}</span>
            <router-link 
              v-if="match.id"
              :to="{ name: 'DepartmentCompare', params: { id: match.id } }"
              class="rounded-md border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 transition"
            >
              Compare
            </router-link>
          </div>
        </div>
        <div v-if="closestMatches.length === 0" class="py-4 text-center text-sm text-slate-400">
          <template v-if="aiStatus === 'pending'">⏳ AI analysis is running…</template>
          <template v-else-if="aiStatus === 'failed'">❌ AI analysis failed.</template>
          <template v-else>No similarity analysis has been generated yet.</template>
        </div>
      </div>
    </article>

    <!-- ── AI Recommendations ──────────────────────────────────────── -->
    <div v-if="recommendations && recommendations.length > 0" class="rounded-xl border border-teal-200 bg-teal-50/20 p-6 shadow-sm text-left">
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

    <article v-if="selectedProposal.status === 'pending' || selectedProposal.status === 'revision_requested'" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="flex justify-between items-center mb-3">
        <h2 class="text-lg font-semibold text-slate-900">Reviewer note</h2>
        <span class="text-xs font-medium text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full">
          Revisions used: {{ selectedProposal.revision_count }} / {{ selectedProposal.max_revisions }}
        </span>
      </div>
      <textarea
        v-model="reviewerNote"
        rows="4"
        class="mt-3 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 transition"
        placeholder="Optional note for the student..."
      ></textarea>
      <div class="mt-3 flex flex-wrap justify-end gap-2">
        <button 
          v-if="isDepartmentHead && selectedProposal.revision_count >= selectedProposal.max_revisions"
          @click="grantExtraRevision" 
          :disabled="isSubmitting"
          class="rounded-lg border border-teal-300 bg-teal-50 px-4 py-2 text-sm font-medium text-teal-700 hover:bg-teal-100 disabled:opacity-50"
        >
          Grant Extra Edit
        </button>
        <button 
          @click="submitReview('revision_requested')" 
          :disabled="isSubmitting || selectedProposal.revision_count >= selectedProposal.max_revisions"
          class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
          :title="selectedProposal.revision_count >= selectedProposal.max_revisions ? 'Maximum revisions reached' : ''"
        >
          Request revision
        </button>
        <button 
          @click="submitReview('rejected')" 
          :disabled="isSubmitting"
          class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
        >
          Reject
        </button>
        <button 
          @click="submitReview('accepted')" 
          :disabled="isSubmitting"
          class="rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white hover:bg-blue-950 disabled:opacity-50"
        >
          Accept
        </button>
      </div>
    </article>
  </section>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'vue-toastification'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const authUser = window.authUser || {}
const isDepartmentHead = authUser.role === 'department_head'

const reviewerNote = ref('')
const isSubmitting = ref(false)

const selectedProposal = ref({
  title: 'Loading...',
  author: '',
  department: '',
  similarity: '—',
  status: 'pending',
  problem: '',
  solution: '',
  tags: '',
  revision_count: 0,
  max_revisions: 2
})

const closestMatches = ref([])
const recommendations = ref([])
const aiStatus = ref('none')       // 'pending' | 'success' | 'failed' | 'none'
const aiSummary = ref(null)        // breakdown summary from /similarity endpoint

onMounted(async () => {
  fetchProposal()
})

const fetchProposal = async () => {
  try {
    const [propRes, simRes] = await Promise.all([
      axios.get(`/department/proposals/${route.params.id}`),
      axios.get(`/department/proposals/${route.params.id}/similarity`)
    ])
    selectedProposal.value = propRes.data.proposal
    aiStatus.value  = simRes.data.ai_status  ?? 'none'
    aiSummary.value = simRes.data.summary     ?? null
    closestMatches.value = simRes.data.results ?? []
    recommendations.value = simRes.data.recommendations ?? []
  } catch (error) {
    console.error('Error fetching proposal details:', error)
    toast.error('Failed to load proposal details.')
  }
}

// ── Computed ────────────────────────────────────────────────────────────────

/** Parse the similarity string (e.g. "34.5%") to a number for the progress bar */
const overallScoreNum = computed(() => {
  const raw = selectedProposal.value?.similarity ?? '0'
  return parseFloat(raw) || 0
})

const breakdownDimensions = computed(() => [
  { key: 'semantic',     label: 'Semantic',      value: aiSummary.value?.semantic_similarity     ?? null },
  { key: 'functions',   label: 'Functions',     value: aiSummary.value?.functions_similarity    ?? null },
  { key: 'objectives',  label: 'Objectives',    value: aiSummary.value?.objectives_similarity   ?? null },
  { key: 'tags',        label: 'Tags',          value: aiSummary.value?.tags_similarity         ?? null },
  { key: 'tech',        label: 'Technologies',  value: aiSummary.value?.technologies_similarity ?? null },
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

// ── Actions ──────────────────────────────────────────────────────────────────

const submitReview = async (decision) => {
  if (isSubmitting.value) return
  
  isSubmitting.value = true
  try {
    await axios.post(`/department/proposals/${route.params.id}/review`, {
      decision: decision,
      note: reviewerNote.value
    })
    toast.success(`Proposal ${decision.replace('_', ' ')} successfully.`)
    router.push({ name: 'DepartmentQueue' })
  } catch (error) {
    console.error('Error submitting review:', error)
    toast.error('Failed to submit review.')
  } finally {
    isSubmitting.value = false
  }
}

const grantExtraRevision = async () => {
  if (isSubmitting.value) return
  isSubmitting.value = true
  try {
    const res = await axios.post(`/department/proposals/${route.params.id}/grant-revision`)
    selectedProposal.value.max_revisions = res.data.max_revisions
    toast.success('Extra revision granted successfully.')
  } catch (error) {
    console.error('Error granting extra revision:', error)
    toast.error('Failed to grant extra revision.')
  } finally {
    isSubmitting.value = false
  }
}

const formatStatus = (status) => {
  if (status === 'revision_requested') return 'Revision Needed'
  return status.charAt(0).toUpperCase() + status.slice(1)
}

const statusClass = (status) => {
  if (status === 'accepted') return 'bg-emerald-100 text-emerald-700'
  if (status === 'revision_requested') return 'bg-cyan-100 text-cyan-700'
  if (status === 'rejected') return 'bg-red-100 text-red-700'
  if (status === 'pending') return 'bg-amber-100 text-amber-700'
  return 'bg-slate-100 text-slate-700'
}
</script>
