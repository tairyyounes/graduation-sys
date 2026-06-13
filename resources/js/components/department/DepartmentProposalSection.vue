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
        <p class="text-sm text-slate-500">Overall similarity</p>
        <p class="mt-2 text-5xl font-semibold text-slate-900">{{ selectedProposal.similarity }}</p>
        <div class="mt-3 h-2 rounded-full bg-slate-200">
          <div class="h-2 rounded-full bg-blue-900" :style="{ width: selectedProposal.similarity }"></div>
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
          <span v-for="tag in selectedProposal.tags.split(',')" :key="tag" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-600">#{{ tag.trim() }}</span>
        </div>
      </article>
    </div>

    <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <h2 class="text-lg font-semibold text-slate-900">Closest matches</h2>
      <div class="mt-3 space-y-3">
        <div v-for="match in closestMatches" :key="match.title" class="flex flex-col gap-2 border-t border-slate-100 pt-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="text-sm font-medium text-slate-900">{{ match.title }}</p>
            <p class="text-xs text-slate-500">{{ match.author }} · {{ match.year }}</p>
          </div>
          <div class="flex items-center gap-2">
            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">{{ match.score }}</span>
            <router-link 
              :to="{ name: 'DepartmentCompare', params: { id: match.id } }"
              class="rounded-md border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 transition"
            >
              Compare
            </router-link>
          </div>
        </div>
        <div v-if="closestMatches.length === 0" class="py-4 text-center text-sm text-slate-400">
          No similarity analysis has been generated yet.
        </div>
      </div>
    </article>

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
import { ref, onMounted } from 'vue'
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
  similarity: '0%',
  status: 'pending',
  problem: '',
  solution: '',
  tags: '',
  revision_count: 0,
  max_revisions: 2
})

const closestMatches = ref([])

onMounted(async () => {
  fetchProposal()
})

const fetchProposal = async () => {
  try {
    const [propRes, simRes] = await Promise.all([
      axios.get(`/department/proposals/${route.params.id}`),
      axios.get(`/student/proposals/${route.params.id}/similarity`) // Reusing student similarity endpoint for now
    ])
    selectedProposal.value = propRes.data.proposal
    closestMatches.value = simRes.data.results
  } catch (error) {
    console.error('Error fetching proposal details:', error)
    toast.error('Failed to load proposal details.')
  }
}

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
