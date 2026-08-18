<template>
  <div v-if="isOpen && proposal" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

      <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 w-full max-w-4xl flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="border-b border-slate-100 px-6 py-5 bg-slate-50/50 flex justify-between items-start shrink-0">
          <div>
            <div class="flex items-center gap-3 mb-1">
              <span v-if="type === 'active'" class="inline-flex items-center rounded-md bg-teal-50 px-2.5 py-1 text-xs font-medium text-teal-700 ring-1 ring-inset ring-teal-700/10">{{ $t('proposals.active') }}</span>
              <span v-if="type === 'draft'" class="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-inset ring-slate-500/20">{{ $t('common.draft_idea') }}</span>
              <span v-if="type === 'archived'" class="inline-flex items-center rounded-md bg-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-inset ring-slate-500/20">{{ $t('common.archived') }}</span>
              <span v-if="proposal.status" class="inline-flex items-center rounded-md bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-600/20">{{ proposal.status }}</span>
            </div>
            <h3 class="text-2xl font-bold text-slate-900">{{ proposal.title }}</h3>
          </div>
          <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 transition-colors p-1">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- Body -->
        <div class="px-6 py-6 overflow-y-auto flex-1 bg-white">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-6">
              <div>
                <h4 class="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-2">{{ $t('messages.problem_statement') }}</h4>
                <p class="text-slate-600 leading-relaxed text-sm bg-slate-50 p-4 rounded-lg border border-slate-100">{{ proposal.problem || 'Not specified.' }}</p>
              </div>
              <div>
                <h4 class="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-2">{{ $t('messages.proposed_solution') }}</h4>
                <p class="text-slate-600 leading-relaxed text-sm bg-slate-50 p-4 rounded-lg border border-slate-100">{{ proposal.solution || 'Not specified.' }}</p>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <h4 class="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-2">{{ $t('common.objectives') }}</h4>
                  <p class="text-slate-600 text-sm whitespace-pre-wrap">{{ proposal.objectives || 'Not specified.' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-2">{{ $t('common.core_functions') }}</h4>
                  <p class="text-slate-600 text-sm whitespace-pre-wrap">{{ proposal.functions || 'Not specified.' }}</p>
                </div>
              </div>
            </div>

            <div class="space-y-6 lg:border-l lg:border-slate-100 lg:pl-8">
              <div>
                <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">{{ $t('messages.similarity_score') }}</h4>
                <div class="flex items-center mt-2">
                  <template v-if="proposal.similarity !== null">
                    <span class="text-3xl font-black" :class="proposal.similarity < 30 ? 'text-teal-600' : proposal.similarity < 60 ? 'text-amber-500' : 'text-red-500'">{{ proposal.similarity }}%</span>
                    <span class="ml-3 text-sm text-slate-500 font-medium">{{ $t('common.match') }}</span>
                  </template>
                  <template v-else>
                    <span class="text-lg font-medium text-slate-500">{{ $t('common.not_checked') }}</span>
                  </template>
                </div>
              </div>
              <div>
                <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">{{ $t('common.domain') }}</h4>
                <p class="text-sm font-medium text-slate-900">{{ proposal.domain || 'Not assigned' }}</p>
              </div>
              <div>
                <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Tags / Keywords</h4>
                <div class="flex flex-wrap gap-2">
                  <span v-for="tag in (proposal.tags ? proposal.tags.split(',') : [])" :key="tag" class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">
                    {{ tag.trim() }}
                  </span>
                </div>
              </div>
              <div>
                <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">{{ $t('messages.technology_used') }}</h4>
                <div class="flex flex-wrap gap-2">
                  <span v-for="tech in (proposal.tech ? proposal.tech.split(',') : [])" :key="tech" class="inline-flex items-center rounded-md bg-blue-50 text-blue-700 px-2 py-1 text-xs font-medium border border-blue-100">
                    {{ tech.trim() }}
                  </span>
                </div>
              </div>
              <div>
                <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">{{ $t('common.created_date') }}</h4>
                <p class="text-sm text-slate-600">{{ proposal.date || 'Today' }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="bg-slate-50 px-6 py-4 flex flex-wrap sm:flex-nowrap justify-end gap-3 border-t border-slate-100 shrink-0">
          <button 
            v-if="!proposal.is_locked && proposal.status !== 'accepted'"
            @click="$emit('edit', proposal)" 
            class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none transition-colors"
          >
            Edit Proposal
          </button>
          <div v-else class="text-xs text-slate-500 italic self-center px-2">
            Proposal is locked and cannot be edited.
          </div>

          <template v-if="type === 'draft'">
            <button @click="$emit('check-similarity')" class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none transition-colors">
              Check Similarity
            </button>
            <button @click="$emit('archive')" class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm hover:bg-slate-50 focus:outline-none transition-colors">
              Archive
            </button>
            <button @click="$emit('delete', proposal)" class="inline-flex justify-center rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-600 shadow-sm hover:bg-red-50 focus:outline-none transition-colors">
              Delete Draft
            </button>
            <button @click="$emit('confirm')" class="inline-flex justify-center rounded-lg border border-transparent bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none transition-colors">
              Confirm Proposal
            </button>
          </template>

          <template v-else-if="type === 'active'">
            <button @click="$emit('view-report')" class="inline-flex justify-center rounded-lg border border-transparent bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none transition-colors">
              View Report
            </button>
          </template>

          <template v-else-if="type === 'archived'">
            <button @click="$emit('restore')" class="inline-flex justify-center rounded-lg border border-transparent bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none transition-colors">
              Restore to Draft
            </button>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  isOpen: {
    type: Boolean,
    required: true,
  },
  proposal: {
    type: Object,
    default: null,
  },
  type: {
    type: String,
    default: '', // 'draft' | 'active' | 'archived'
  },
})

defineEmits(['close', 'check-similarity', 'archive', 'delete', 'confirm', 'view-report', 'restore', 'edit'])
</script>
