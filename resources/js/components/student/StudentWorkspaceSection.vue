<template>
  <section class="space-y-6">
    <div class="border-b border-slate-200 flex justify-between items-end">
      <nav class="-mb-px flex space-x-8 rtl:space-x-reverse">
        <button
          v-for="tab in workspaceTabs"
          :key="tab"
          @click="$emit('update:workspaceTab', tab)"
          :class="[
            workspaceTab === tab
              ? 'border-teal-500 text-teal-600'
              : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700',
            'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors'
          ]"
        >
          {{ tabLabel(tab) }}
          <span v-if="tab === 'Draft Ideas' && draftCount" class="ms-2 bg-slate-100 text-slate-600 py-0.5 px-2 rounded-full text-xs">{{ draftCount }}</span>
          <span v-if="tab === 'Archived Ideas' && archivedCount" class="ms-2 bg-slate-100 text-slate-600 py-0.5 px-2 rounded-full text-xs">{{ archivedCount }}</span>
        </button>
      </nav>
    </div>

    <!-- Draft Ideas Tab -->
    <div v-if="workspaceTab === 'Draft Ideas'" class="space-y-4">
      <div class="flex justify-between items-center mb-6">
        <p class="text-sm text-slate-500">{{ $t('student.workspace.drafts_private') }}</p>
        <button @click="$emit('open-new-proposal')" class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 transition-colors ring-1 ring-teal-600">
          <svg class="w-4 h-4 me-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          {{ $t('student.workspace.new_proposal') }}
        </button>
      </div>

      <div v-if="draftIdeas.length === 0" class="text-center bg-white rounded-xl border border-slate-200 border-dashed py-16 px-6 shadow-sm">
        <div class="mx-auto w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <p class="text-base font-medium text-slate-900">{{ $t('student.workspace.no_drafts') }}</p>
        <p class="mt-1 text-sm text-slate-500">{{ $t('student.workspace.create_first') }}</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <StudentProposalCard
          v-for="idea in draftIdeas"
          :key="idea.id"
          :proposal="idea"
          type="draft"
          @open="$emit('open-proposal-details', idea, 'draft')"
        />
      </div>
    </div>

    <!-- Active Proposal Tab -->
    <div v-if="workspaceTab === 'Active Proposal'" class="space-y-6">
      <div v-if="activeProposal" class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="p-6 sm:p-8">
          <div class="flex justify-between items-start mb-6">
            <span class="inline-flex items-center rounded-md bg-teal-50 px-2.5 py-1 text-xs font-medium text-teal-700 ring-1 ring-inset ring-teal-700/10">{{ $t('student.overview.active_proposal') }}</span>
            <span class="inline-flex items-center rounded-md bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-600/20">{{ activeProposal.status }}</span>
          </div>

          <h3 class="text-2xl font-bold text-slate-900 mb-4">{{ activeProposal.title }}</h3>
          <div class="prose prose-sm prose-slate max-w-none mb-6">
            <h4 class="text-slate-900 font-semibold mb-2">{{ $t('fields.problem_statement') }}</h4>
            <p class="text-slate-600 leading-relaxed mb-4">{{ activeProposal.problem }}</p>
            <h4 class="text-slate-900 font-semibold mb-2">{{ $t('fields.proposed_solution') }}</h4>
            <p class="text-slate-600 leading-relaxed">{{ activeProposal.solution }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 bg-slate-50 rounded-lg p-5 border border-slate-100 mb-6">
            <div>
              <p class="text-xs font-medium text-slate-500">{{ $t('fields.domain') }}</p>
              <p class="mt-1 text-sm font-medium text-slate-900">{{ activeProposal.domain }}</p>
            </div>
            <div class="sm:col-span-2">
              <p class="text-xs font-medium text-slate-500">{{ $t('fields.tags') }}</p>
              <p class="mt-1 text-sm text-slate-900">{{ activeProposal.tags }}</p>
            </div>
            <div>
              <p class="text-xs font-medium text-slate-500">{{ $t('student.workspace.similarity') }}</p>
              <p class="mt-1 text-sm font-bold" :class="[activeProposal.similarity < 30 ? 'text-teal-600' : activeProposal.similarity < 60 ? 'text-amber-600' : 'text-red-600']">
                {{ activeProposal.similarity !== null ? activeProposal.similarity + '%' : $t('student.workspace.not_checked') }}
              </p>
            </div>
          </div>

          <div class="flex gap-3">
            <button @click="$emit('navigate', 'Similarity Report')" class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 transition">{{ $t('student.workspace.view_full_report') }}</button>
            <button @click="$emit('open-proposal-details', activeProposal, 'active')" class="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition">{{ $t('student.workspace.view_all_details') }}</button>
          </div>
        </div>
      </div>

      <div v-else class="text-center bg-white rounded-xl border border-slate-200 border-dashed py-20 px-6 shadow-sm">
        <div class="mx-auto w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900">{{ $t('student.overview.no_active_proposal') }}</h3>
        <p class="mt-2 text-sm text-slate-500 max-w-md mx-auto">{{ $t('student.workspace.no_active_desc') }}</p>
        <button @click="$emit('update:workspaceTab', 'Draft Ideas')" class="mt-6 inline-flex items-center justify-center rounded-lg bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition">
          {{ $t('student.workspace.view_drafts') }}
        </button>
      </div>
    </div>

    <!-- Archived Ideas Tab -->
    <div v-if="workspaceTab === 'Archived Ideas'" class="space-y-4">
      <div v-if="archivedIdeas.length === 0" class="text-center bg-white rounded-xl border border-slate-200 border-dashed py-16 px-6 shadow-sm">
        <div class="mx-auto w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
        </div>
        <p class="text-base font-medium text-slate-900">{{ $t('student.workspace.no_archived') }}</p>
        <p class="mt-1 text-sm text-slate-500">{{ $t('student.workspace.archived_hint') }}</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <StudentProposalCard
          v-for="idea in archivedIdeas"
          :key="idea.id"
          :proposal="idea"
          type="archived"
          @open="$emit('open-proposal-details', idea, 'archived')"
        />
      </div>
    </div>
  </section>
</template>

<script setup>
import StudentProposalCard from './StudentProposalCard.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

// خريطة اسم التبويب الداخلي (مفتاح منطقي إنجليزي) -> مفتاح الترجمة
const TAB_KEYS = {
  'Draft Ideas': 'student.workspace.tab_drafts',
  'Active Proposal': 'student.workspace.tab_active',
  'Archived Ideas': 'student.workspace.tab_archived',
}

function tabLabel(tab) {
  return TAB_KEYS[tab] ? t(TAB_KEYS[tab]) : tab
}

defineProps({
  workspaceTab: {
    type: String,
    required: true,
  },
  workspaceTabs: {
    type: Array,
    required: true,
  },
  draftIdeas: {
    type: Array,
    required: true,
  },
  archivedIdeas: {
    type: Array,
    required: true,
  },
  activeProposal: {
    type: Object,
    default: null,
  },
  draftCount: {
    type: Number,
    default: 0,
  },
  archivedCount: {
    type: Number,
    default: 0,
  },
})

defineEmits(['update:workspaceTab', 'open-new-proposal', 'open-proposal-details', 'navigate'])
</script>
