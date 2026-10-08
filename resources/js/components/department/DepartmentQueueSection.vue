<template>
  <section class="space-y-5">
    <!-- Non-committee member warning notice -->
    <div v-if="!isCommitteeMember" class="rounded-2xl border border-amber-200 bg-amber-50/70 p-6 text-start">
      <div class="flex items-start gap-4">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
          <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <div>
          <h3 class="text-base font-semibold text-amber-900">{{ $t('dept.queue.not_in_committee_title') }}</h3>
          <p class="mt-1 text-sm text-amber-800 leading-relaxed">{{ $t('dept.queue.not_in_committee_desc') }}</p>
        </div>
      </div>
    </div>

    <!-- Empty State for Committee Members with no proposals -->
    <div v-else-if="queueRows.length === 0" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 py-16 px-4 text-center">
      <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500 mb-4">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h6.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      </div>
      <h3 class="text-lg font-semibold text-slate-900">{{ $t('dept.proposal.no_analysis') }}</h3>
      <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $t('messages.no_activity_yet') }}</p>
    </div>

    <!-- Table of Proposals -->
    <div v-else class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
      <table class="min-w-full text-start text-sm">
        <thead class="bg-slate-50 text-slate-500">
          <tr>
            <th class="px-4 py-3 font-medium">{{ $t('fields.title') }}</th>
            <th class="px-4 py-3 font-medium">{{ $t('fields.author') }}</th>
            <th class="px-4 py-3 font-medium">{{ $t('fields.department') }}</th>
            <th class="px-4 py-3 font-medium">{{ $t('fields.similarity') }}</th>
            <th class="px-4 py-3 font-medium">{{ $t('dept.queue.approvals_col') }}</th>
            <th class="px-4 py-3 font-medium">{{ $t('fields.status') }}</th>
            <th class="px-4 py-3 font-medium">{{ $t('fields.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in queueRows" :key="row.id" class="border-t border-slate-100 hover:bg-slate-50/60 transition">
            <td class="px-4 py-3 font-medium text-slate-900">{{ row.title }}</td>
            <td class="px-4 py-3 text-slate-600">{{ row.author }}</td>
            <td class="px-4 py-3 text-slate-600">{{ row.department }}</td>
            <td class="px-4 py-3"><span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ row.similarity }}</span></td>
            <td class="px-4 py-3">
              <span 
                v-if="row.committee_approvals" 
                :class="row.committee_approvals.all_approved ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-200'"
                class="rounded-full px-2.5 py-0.5 text-xs font-medium border"
              >
                {{ row.committee_approvals.approved }} / {{ row.committee_approvals.total }}
              </span>
              <span v-else class="text-xs text-slate-400">—</span>
            </td>
            <td class="px-4 py-3"><span :class="statusClass(row.status)" class="rounded-full px-2.5 py-1 text-xs font-semibold">{{ formatStatus(row.status) }}</span></td>
            <td class="px-4 py-3">
              <router-link :to="{ name: 'DepartmentProposal', params: { id: row.id } }" class="rounded-md border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100 transition">
                {{ $t('common.view') }}
              </router-link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const { t } = useI18n()

const queueRows = ref([])
const isCommitteeMember = ref(true)

onMounted(async () => {
  try {
    const res = await axios.get('/department/proposals?status=submitted')
    queueRows.value = res.data.proposals ?? []
    if (res.data.is_committee_member !== undefined) {
      isCommitteeMember.value = res.data.is_committee_member
    }
  } catch (error) {
    console.error('Error fetching review queue:', error)
  }
})

const statusClass = (status) => {
  if (status === 'accepted') return 'bg-emerald-100 text-emerald-700'
  if (status === 'revision_requested') return 'bg-cyan-100 text-cyan-700'
  if (status === 'rejected') return 'bg-red-100 text-red-700'
  if (status === 'pending') return 'bg-amber-100 text-amber-700'
  return 'bg-slate-100 text-slate-700'
}

const formatStatus = (status) => {
  const key = `status.${status}`
  const translated = t(key)
  return translated === key ? status.charAt(0).toUpperCase() + status.slice(1) : translated
}
</script>
