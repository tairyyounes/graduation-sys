<template>
  <section class="space-y-5">
    <div class="flex items-center justify-between">
      <router-link :to="backLink" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-teal-600 transition">
        <svg class="me-1 h-4 w-4 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        {{ $t('dept.compare.back') }}
      </router-link>
      <div v-if="match" class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">
        {{ $t('dept.compare.similarity_score', { score: match.score }) }}
      </div>
    </div>

    <div>
      <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">{{ $t('dept.compare.title') }}</h1>
      <p class="mt-1 text-sm text-slate-500">{{ $t('dept.compare.subtitle') }}</p>
    </div>

    <p v-if="loading" class="py-10 text-center text-sm text-slate-400">…</p>
    <p v-else-if="!proposal || !match" class="py-10 text-center text-sm text-slate-500">{{ $t('dept.compare.not_found') }}</p>

    <template v-else>
      <!-- AI similarity summary -->
      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-6 md:flex-row md:items-start">
          <div class="flex items-center gap-4">
            <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-2xl border border-teal-100 bg-teal-50">
              <span class="text-xl font-black text-teal-700">{{ match.score }}</span>
            </div>
            <h3 class="text-lg font-bold text-slate-900">{{ $t('similarity.breakdown') }}</h3>
          </div>
          <div class="flex-1 space-y-2.5">
            <div v-for="d in breakdown" :key="d.key" class="flex items-center gap-3 text-sm">
              <span class="w-24 shrink-0 text-slate-600">{{ d.label }}</span>
              <div class="h-2 flex-1 rounded-full bg-slate-100">
                <div class="h-2 rounded-full bg-amber-500" :style="{ width: (d.value ?? 0) + '%' }"></div>
              </div>
              <span class="w-12 text-end font-medium text-slate-700">{{ d.value !== null ? d.value + '%' : '—' }}</span>
            </div>
          </div>
        </div>
        <div v-if="match.verdict || match.explanation" class="mt-5 space-y-2 border-t border-slate-100 pt-5 text-sm text-slate-700">
          <p v-if="match.verdict"><span class="font-semibold">{{ $t('similarity.verdict') }}:</span> {{ match.verdict }}</p>
          <p v-if="match.explanation" class="leading-relaxed text-slate-600">{{ match.explanation }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Current proposal -->
        <article class="flex flex-col rounded-2xl border border-teal-200 bg-white shadow-sm overflow-hidden">
          <div class="border-b border-teal-100 bg-teal-50/50 p-5">
            <div class="mb-3 inline-block rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-semibold text-teal-800">
              {{ $t('dept.compare.current') }}
            </div>
            <h2 class="text-xl font-bold text-slate-900">{{ proposal.title }}</h2>
            <p class="mt-1 text-sm font-medium text-slate-600">{{ proposal.author }} · {{ proposal.department }}</p>
          </div>
          <ProposalDetails :item="proposal" />
        </article>

        <!-- Matched proposal -->
        <article class="flex flex-col rounded-2xl border border-amber-200 bg-white shadow-sm overflow-hidden">
          <div class="border-b border-amber-100 bg-amber-50/50 p-5">
            <div class="mb-3 inline-block rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
              {{ $t('dept.compare.historical_match', { year: match.year }) }}
            </div>
            <h2 class="text-xl font-bold text-slate-900">{{ match.title }}</h2>
            <p class="mt-1 text-sm font-medium text-slate-600">{{ match.author ? match.author + ' · ' : '' }}{{ match.domain }}</p>
          </div>
          <ProposalDetails :item="match" />
        </article>
      </div>

      <div class="flex justify-end rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <router-link
          :to="backLink"
          class="rounded-lg border border-slate-300 bg-white px-5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
        >
          {{ $t('dept.compare.done') }}
        </router-link>
      </div>
    </template>
  </section>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const route = useRoute()
const { t } = useI18n()

const loading = ref(true)
const proposal = ref(null)
const match = ref(null)

const backLink = computed(() => ({ name: 'DepartmentProposal', params: { id: route.params.id } }))

function getTags(value) {
  if (!value) return []
  const list = Array.isArray(value) ? value : String(value).split(',')
  return list.map(s => String(s).trim()).filter(Boolean)
}

// Same field layout as the student compare page, shared by both columns.
const ProposalDetails = defineComponent({
  props: { item: { type: Object, required: true } },
  setup(props) {
    const heading = key => h('h3', { class: 'text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5' }, t(key))
    const boxed = (key, text) => h('div', [
      heading(key),
      h('p', { class: 'text-sm leading-relaxed text-slate-700 bg-slate-50 p-4 rounded-xl border border-slate-100 whitespace-pre-wrap' }, text || '—'),
    ])
    const plain = (key, text) => h('div', [
      heading(key),
      h('p', { class: 'text-sm leading-relaxed text-slate-600 whitespace-pre-wrap' }, text || '—'),
    ])
    const chips = (key, value, cls) => h('div', [
      heading(key),
      h('div', { class: 'flex flex-wrap gap-1' },
        getTags(value).map(v => h('span', { key: v, class: cls }, v))),
    ])

    return () => {
      const p = props.item
      return h('div', { class: 'flex-1 p-5 space-y-6' }, [
        boxed('fields.problem_statement', p.problem),
        boxed('fields.proposed_solution', p.solution),
        h('div', { class: 'grid grid-cols-1 sm:grid-cols-2 gap-4' }, [
          plain('fields.objectives', p.objectives),
          plain('fields.core_functions', p.functions),
        ]),
        h('div', { class: 'grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-slate-100' }, [
          chips('fields.tags', p.tags, 'inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600'),
          chips('fields.technologies', p.tech, 'inline-flex items-center rounded-md bg-blue-50 text-blue-700 px-2 py-0.5 text-xs font-medium border border-blue-100'),
        ]),
      ])
    }
  },
})

const breakdown = computed(() => [
  { key: 'problem',    label: t('similarity.problem'),    value: match.value?.problem_similarity ?? null },
  { key: 'solution',   label: t('similarity.solution'),   value: match.value?.solution_similarity ?? null },
  { key: 'objectives', label: t('similarity.objectives'), value: match.value?.objectives_similarity ?? null },
  { key: 'functions',  label: t('similarity.functions'),  value: match.value?.functions_similarity ?? null },
  { key: 'tags',       label: t('similarity.tags'),       value: match.value?.tags_similarity ?? null },
  { key: 'tech',       label: t('similarity.tech'),       value: match.value?.technologies_similarity ?? null },
])

onMounted(async () => {
  try {
    const [propRes, simRes] = await Promise.all([
      axios.get(`/department/proposals/${route.params.id}`),
      axios.get(`/department/proposals/${route.params.id}/similarity`),
    ])
    proposal.value = propRes.data.proposal
    match.value = (simRes.data.results ?? [])[Number(route.params.match)] ?? null
  } catch (error) {
    console.error('Error loading comparison:', error)
  } finally {
    loading.value = false
  }
})
</script>
