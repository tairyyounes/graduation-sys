<template>
  <section class="space-y-5">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="text-base font-semibold text-slate-900">{{ $t('dept.leave.title') }}</h2>
      <p class="mt-1 text-sm text-slate-500">{{ $t('dept.leave.description') }}</p>
    </div>

    <div v-if="loading" class="py-10 text-center text-sm text-slate-500">{{ $t('common.loading') }}</div>

    <div
      v-else-if="requests.length === 0"
      class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 py-16 px-4 text-center"
    >
      <h3 class="text-lg font-semibold text-slate-900">{{ $t('dept.leave.empty_title') }}</h3>
      <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $t('dept.leave.empty_desc') }}</p>
    </div>

    <article
      v-for="req in requests"
      v-else
      :key="req.id"
      class="rounded-xl border bg-white shadow-sm overflow-hidden"
      :class="req.status === 'pending' ? 'border-amber-200' : 'border-slate-200'"
    >
      <header class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4">
        <div class="min-w-0">
          <p class="text-sm font-semibold text-slate-900">
            {{ $t('dept.leave.from', { name: req.student.name, number: req.student.regNumber }) }}
          </p>
          <p class="mt-0.5 text-sm text-slate-600 truncate">
            {{ $t('dept.leave.project', { title: req.proposal.title || '—' }) }}
            <span class="ms-1 text-xs text-slate-400">({{ formatProposalStatus(req.proposal) }})</span>
          </p>
        </div>
        <span :class="statusClass(req.status)" class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold">
          {{ $t(`dept.leave.status.${req.status}`) }}
        </span>
      </header>

      <div class="space-y-3 px-5 py-4">
        <div>
          <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ $t('dept.leave.reason') }}</p>
          <p class="mt-1 whitespace-pre-line break-words text-sm text-slate-800">{{ req.reason }}</p>
        </div>
        <div>
          <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ $t('dept.leave.team') }}</p>
          <p class="mt-1 text-sm text-slate-700">
            {{ req.team.map(m => `${m.name} (${m.regNumber})`).join('، ') || '—' }}
          </p>
        </div>
        <p class="text-xs text-slate-400">
          {{ $t('dept.leave.sent_at', { date: formatDate(req.date) }) }}
          <template v-if="req.decided_at">
            · {{ $t('dept.leave.decided', { name: req.decided_by || '—', date: formatDate(req.decided_at) }) }}
          </template>
        </p>
      </div>

      <footer v-if="req.status === 'pending'" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-slate-100 bg-slate-50 px-5 py-3">
        <p class="text-xs text-slate-500">{{ $t('dept.leave.approve_hint') }}</p>
        <div class="flex shrink-0 gap-2">
          <button
            type="button"
            :disabled="busyId === req.id"
            @click="decide(req, 'reject')"
            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50 transition"
          >{{ $t('dept.leave.reject') }}</button>
          <button
            type="button"
            :disabled="busyId === req.id"
            @click="decide(req, 'approve')"
            class="inline-flex items-center rounded-lg bg-red-600 px-3.5 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 disabled:opacity-50 transition"
          >{{ $t('dept.leave.approve') }}</button>
        </div>
      </footer>
    </article>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useToast } from 'vue-toastification'

const { t, locale } = useI18n()
const toast = useToast()

const requests = ref([])
const loading = ref(true)
const busyId = ref(null)

async function load() {
  try {
    const res = await axios.get('/department/leave-requests')
    requests.value = res.data.requests || []
  } catch (error) {
    toast.error(t('dept.leave.load_failed'))
  } finally {
    loading.value = false
  }
}

async function decide(req, decision) {
  const question = decision === 'approve' ? t('dept.leave.confirm_approve') : t('dept.leave.confirm_reject')
  if (!confirm(question)) return

  busyId.value = req.id
  try {
    const res = await axios.post(`/department/leave-requests/${req.id}/decide`, { decision })
    toast.success(res.data.message)
    await load()
  } catch (error) {
    toast.error(error.response?.data?.message || t('dept.leave.decide_failed'))
  } finally {
    busyId.value = null
  }
}

const statusClass = (status) => {
  if (status === 'approved') return 'bg-emerald-100 text-emerald-700'
  if (status === 'rejected') return 'bg-red-100 text-red-700'
  return 'bg-amber-100 text-amber-700'
}

const formatProposalStatus = (proposal) => {
  if (proposal.submission_status === 'archived') return t('dept.leave.proposal_cancelled')
  const key = `status.${proposal.review_status}`
  const translated = t(key)
  return translated === key ? proposal.review_status : translated
}

const formatDate = (value) => value
  ? new Date(value).toLocaleDateString(locale.value === 'ar' ? 'ar-LY' : 'en-GB')
  : '—'

onMounted(load)
</script>
