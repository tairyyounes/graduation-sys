<template>
  <section class="space-y-6">
    <div class="mb-8">
      <h2 class="text-2xl font-bold text-slate-900">Similarity Report</h2>
      <p class="mt-1 text-slate-500 text-sm">AI-based semantic comparison for the active proposal.</p>
    </div>

    <div v-if="!activeProposal" class="rounded-xl border border-slate-200 bg-white p-12 shadow-sm text-center">
      <p class="text-slate-500">You must confirm a proposal to view its similarity report.</p>
      <button @click="$emit('navigate', 'Project Workspace')" class="mt-4 text-teal-600 font-medium hover:text-teal-700">Go to Workspace</button>
    </div>

    <template v-else>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Active Proposal Summary Card -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
          <div class="flex justify-between items-start mb-4">
            <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Active Proposal Summary</p>
            <span :class="[
              activeProposal.similarity < 30 ? 'bg-teal-50 text-teal-700 border-teal-200'
              : activeProposal.similarity < 60 ? 'bg-amber-50 text-amber-700 border-amber-200'
              : 'bg-red-50 text-red-700 border-red-200',
              'inline-flex items-center rounded-full px-3 py-1 text-xs font-bold border shadow-sm'
            ]">
              {{ activeProposal.similarity < 30 ? 'Low Risk' : activeProposal.similarity < 60 ? 'Medium Risk' : 'High Risk' }}
            </span>
          </div>
          <h3 class="text-xl font-bold text-slate-900 mb-2">{{ activeProposal.title }}</h3>
          <div class="flex flex-wrap gap-x-6 gap-y-2 mt-4 text-sm text-slate-600">
            <div><span class="font-medium text-slate-900">Domain:</span> {{ activeProposal.domain }}</div>
            <div><span class="font-medium text-slate-900">Status:</span> {{ activeProposal.status }}</div>
            <div><span class="font-medium text-slate-900">Checked:</span> Just now</div>
          </div>
        </div>

        <!-- Main Similarity Score Card -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col items-center justify-center text-center relative overflow-hidden">
          <div class="relative z-10">
            <p class="text-sm font-semibold text-slate-500 mb-2">Overall Similarity</p>
            <div class="text-5xl font-black mb-2" :class="activeProposal.similarity < 30 ? 'text-teal-600' : activeProposal.similarity < 60 ? 'text-amber-500' : 'text-red-500'">
              {{ activeProposal.similarity }}<span class="text-3xl text-slate-400">%</span>
            </div>
            <p class="text-xs text-slate-500 mt-2 px-4">
              This score represents how close the proposal idea is to previously submitted projects in the same domain.
            </p>
          </div>
        </div>
      </div>

      <!-- AI Recommendation Card -->
      <div class="mb-6 rounded-xl border border-slate-200 bg-gradient-to-r from-slate-50 to-white p-6 shadow-sm border-l-4" :class="activeProposal.similarity < 30 ? 'border-l-teal-500' : activeProposal.similarity < 60 ? 'border-l-amber-500' : 'border-l-red-500'">
        <div class="flex items-start gap-4">
          <div class="mt-1" :class="activeProposal.similarity < 30 ? 'text-teal-500' : activeProposal.similarity < 60 ? 'text-amber-500' : 'text-red-500'">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div>
            <h4 class="text-sm font-bold text-slate-900 mb-1">AI Recommendation</h4>
            <p class="text-sm text-slate-700">
              {{
                activeProposal.similarity < 30
                  ? 'The proposal appears sufficiently distinct and can proceed for review. The core concepts show strong originality.'
                  : activeProposal.similarity < 60
                    ? 'Some parts overlap with previous proposals. Review the matched projects below to ensure your specific implementation or objectives are unique before the domain review.'
                    : 'This proposal is highly similar to previous work and will likely be rejected. It is strongly recommended to revise the core problem or solution to be more unique.'
              }}
            </p>
          </div>
        </div>
      </div>

      <!-- Similarity Breakdown -->
      <h3 class="text-lg font-semibold text-slate-900 mb-4 mt-8">Semantic Breakdown</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium text-slate-500 mb-1">Title</p>
          <p class="text-lg font-bold text-slate-800">{{ Math.max(0, activeProposal.similarity - 5) }}%</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium text-slate-500 mb-1">Problem</p>
          <p class="text-lg font-bold text-slate-800">{{ Math.min(100, activeProposal.similarity + 12) }}%</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium text-slate-500 mb-1">Solution</p>
          <p class="text-lg font-bold text-slate-800">{{ activeProposal.similarity }}%</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium text-slate-500 mb-1">Objectives</p>
          <p class="text-lg font-bold text-slate-800">{{ Math.max(0, activeProposal.similarity - 8) }}%</p>
        </div>
      </div>

      <!-- Top Similar Projects -->
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
                <th class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Similarity</th>
                <th class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Matched Keywords</th>
                <th class="px-3 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider pr-6">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
              <tr v-for="(match, index) in topMatches" :key="index" class="hover:bg-slate-50/50 transition-colors">
                <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-slate-400">#{{ index + 1 }}</td>
                <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-slate-900">{{ match.title }}</td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600">{{ match.domain }}</td>
                <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold" :class="match.similarity < 30 ? 'text-teal-600' : 'text-amber-600'">{{ match.similarity }}%</td>
                <td class="whitespace-nowrap px-3 py-4 text-xs text-slate-500">AI, Chatbot, Education</td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-right pr-6">
                  <button class="text-teal-600 hover:text-teal-900 font-medium text-sm transition-colors">View Details</button>
                </td>
              </tr>
            </tbody>
          </table>
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
defineProps({
  activeProposal: {
    type: Object,
    default: null,
  },
  topMatches: {
    type: Array,
    required: true,
  },
})

defineEmits(['navigate', 'recheck'])
</script>
