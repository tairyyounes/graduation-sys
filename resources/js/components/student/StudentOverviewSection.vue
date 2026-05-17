<template>
  <section class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md transition-shadow">
        <p class="text-sm font-medium text-slate-500">Active Proposal</p>
        <template v-if="activeProposal">
          <p class="mt-2 text-xl font-bold text-slate-900 truncate">{{ activeProposal.title }}</p>
          <p class="mt-1 text-sm text-slate-600 line-clamp-1">{{ activeProposal.problem }}</p>
        </template>
        <template v-else>
          <p class="mt-2 text-xl font-bold text-slate-400">No Active Proposal</p>
        </template>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md transition-shadow">
        <p class="text-sm font-medium text-slate-500">Status</p>
        <p class="mt-2 text-xl font-bold text-slate-900">{{ activeProposal ? activeProposal.status : 'None' }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md transition-shadow">
        <p class="text-sm font-medium text-slate-500">Similarity Score</p>
        <div class="mt-2 flex items-baseline gap-2">
          <p class="text-xl font-bold text-slate-900">{{ activeProposal && activeProposal.similarity !== null ? activeProposal.similarity + '%' : 'N/A' }}</p>
          <span
            v-if="activeProposal && activeProposal.similarity !== null"
            :class="[
              activeProposal.similarity < 30 ? 'bg-teal-50 text-teal-700 ring-teal-600/20'
              : activeProposal.similarity < 60 ? 'bg-amber-50 text-amber-700 ring-amber-600/20'
              : 'bg-red-50 text-red-700 ring-red-600/20',
              'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset'
            ]"
          >
            {{ activeProposal.similarity < 30 ? 'Low Risk' : activeProposal.similarity < 60 ? 'Medium Risk' : 'High Risk' }}
          </span>
        </div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md transition-shadow">
        <p class="text-sm font-medium text-slate-500">Team Size</p>
        <p class="mt-2 text-xl font-bold text-slate-900">{{ Math.max(1, teamSize) }} / 3</p>
      </div>
    </div>

    <div class="mt-8 bg-gradient-to-r from-teal-600 to-cyan-600 rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
      <div class="relative z-10">
        <h2 class="text-2xl font-bold mb-2">Welcome back, Tayri!</h2>
        <p class="text-teal-100 max-w-2xl text-sm leading-relaxed">
          Your graduation proposal journey is looking good. Use ProposalGuard AI to check your drafts against previous projects to ensure originality and a smooth approval process from your domain reviewers.
        </p>
        <button @click="$emit('navigate', 'Project Workspace')" class="mt-6 bg-white text-teal-700 px-5 py-2.5 rounded-lg text-sm font-semibold shadow-sm hover:bg-slate-50 transition-colors">
          Go to Workspace
        </button>
      </div>
      <svg class="absolute right-0 top-0 h-full text-white/10 transform translate-x-1/4 scale-150" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
      </svg>
    </div>
  </section>
</template>

<script setup>
defineProps({
  activeProposal: {
    type: Object,
    default: null,
  },
  teamSize: {
    type: Number,
    required: true,
  },
})

defineEmits(['navigate'])
</script>
