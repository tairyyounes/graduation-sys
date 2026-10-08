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

<<<<<<< Updated upstream
    <p v-if="loading" class="py-10 text-center text-sm text-slate-400">…</p>
    <p v-else-if="!proposal || !match" class="py-10 text-center text-sm text-slate-500">{{ $t('dept.compare.not_found') }}</p>
=======
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <!-- Left Column: Current Proposal -->
      <article class="flex flex-col rounded-2xl border border-teal-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-teal-100 bg-teal-50/50 p-5">
          <div class="mb-3 inline-block rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-semibold text-teal-800">
            {{ $t('dept.compare.current') }}
          </div>
          <h2 class="text-xl font-bold text-slate-900">{{ currentProposal.title }}</h2>
          <p class="mt-1 text-sm font-medium text-slate-600">{{ currentProposal.author }} · {{ currentProposal.department || 'General' }}</p>
        </div>
        <div class="flex-1 p-5 space-y-5">
          <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">{{ $t('fields.problem_statement') }}</h3>
            <p class="text-sm leading-relaxed text-slate-700 bg-slate-50 p-3.5 rounded-xl border border-slate-100">
              {{ currentProposal.problem || currentProposal.description }}
            </p>
          </div>

          <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">{{ $t('fields.proposed_solution') }}</h3>
            <p class="text-sm leading-relaxed text-slate-700 bg-slate-50 p-3.5 rounded-xl border border-slate-100">
              {{ currentProposal.solution || $t('common.not_specified') }}
            </p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">{{ $t('fields.objectives') }}</h3>
              <p class="text-xs leading-relaxed text-slate-600 bg-slate-50 p-3 rounded-lg border border-slate-100 whitespace-pre-wrap">
                {{ currentProposal.objectives || $t('common.not_specified') }}
              </p>
            </div>
            <div>
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">{{ $t('fields.core_functions') }}</h3>
              <p class="text-xs leading-relaxed text-slate-600 bg-slate-50 p-3 rounded-lg border border-slate-100 whitespace-pre-wrap">
                {{ currentProposal.functions || $t('common.not_specified') }}
              </p>
            </div>
          </div>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-slate-100">
             <div>
               <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">{{ $t('fields.tags') }}</h3>
               <div class="flex flex-wrap gap-1.5">
                  <span v-for="tag in (currentProposal.tags ? currentProposal.tags.split(',') : ['library', 'NFC', 'recommendation'])" :key="tag" class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                    #{{ tag.trim() }}
                  </span>
               </div>
             </div>
             <div>
               <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">{{ $t('fields.technologies') }}</h3>
               <div class="flex flex-wrap gap-1.5">
                  <span v-for="tech in (currentProposal.tech ? currentProposal.tech.split(',') : ['Vue.js', 'Laravel', 'Python', 'NFC SDK'])" :key="tech" class="rounded-md bg-blue-50 text-blue-700 px-2 py-0.5 text-xs font-medium border border-blue-100">
                    {{ tech.trim() }}
                  </span>
               </div>
             </div>
          </div>
        </div>
      </article>
>>>>>>> Stashed changes

    <template v-else>
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
<<<<<<< Updated upstream
          <div class="flex-1 space-y-4 p-5">
            <div v-for="f in ['problem', 'solution']" :key="f">
              <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500 mb-1">{{ $t(`similarity.${f}`) }}</h3>
              <p class="text-sm leading-relaxed text-slate-700 whitespace-pre-wrap">{{ proposal[f] || '—' }}</p>
            </div>
            <div v-if="tags.length" class="border-t border-slate-100 pt-4">
              <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500 mb-2">{{ $t('dept.compare.keywords') }}</h3>
              <div class="flex flex-wrap gap-2">
                <span v-for="tag in tags" :key="tag" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">#{{ tag }}</span>
              </div>
            </div>
=======
          <h2 class="text-xl font-bold text-slate-900">{{ historicalProposal.title }}</h2>
          <p class="mt-1 text-sm font-medium text-slate-600">{{ historicalProposal.author }} · {{ historicalProposal.department || 'General' }}</p>
        </div>
        <div class="flex-1 p-5 space-y-5">
          <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">{{ $t('fields.problem_statement') }}</h3>
            <p class="text-sm leading-relaxed text-slate-700 bg-slate-50 p-3.5 rounded-xl border border-slate-100">
              {{ historicalProposal.problem || historicalProposal.description }}
            </p>
          </div>

          <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">{{ $t('fields.proposed_solution') }}</h3>
            <p class="text-sm leading-relaxed text-slate-700 bg-slate-50 p-3.5 rounded-xl border border-slate-100">
              {{ historicalProposal.solution || $t('common.not_available') }}
            </p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">{{ $t('fields.objectives') }}</h3>
              <p class="text-xs leading-relaxed text-slate-600 bg-slate-50 p-3 rounded-lg border border-slate-100 whitespace-pre-wrap">
                {{ historicalProposal.objectives || $t('common.not_available') }}
              </p>
            </div>
            <div>
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">{{ $t('fields.core_functions') }}</h3>
              <p class="text-xs leading-relaxed text-slate-600 bg-slate-50 p-3 rounded-lg border border-slate-100 whitespace-pre-wrap">
                {{ historicalProposal.functions || $t('common.not_available') }}
              </p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-slate-100">
             <div>
               <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">{{ $t('fields.tags') }}</h3>
               <div class="flex flex-wrap gap-1.5">
                  <span v-for="tag in (historicalProposal.tags ? historicalProposal.tags.split(',') : ['machine_learning', 'IDS', 'security'])" :key="tag" class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                    #{{ tag.trim() }}
                  </span>
               </div>
             </div>
             <div>
               <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">{{ $t('fields.technologies') }}</h3>
               <div class="flex flex-wrap gap-1.5">
                  <span v-for="tech in (historicalProposal.tech ? historicalProposal.tech.split(',') : ['Python', 'TensorFlow', 'Scikit-learn', 'Snort'])" :key="tech" class="rounded-md bg-blue-50 text-blue-700 px-2 py-0.5 text-xs font-medium border border-blue-100">
                    {{ tech.trim() }}
                  </span>
               </div>
             </div>
>>>>>>> Stashed changes
          </div>
        </article>

        <!-- Matched proposal -->
        <article class="flex flex-col rounded-2xl border border-amber-200 bg-white shadow-sm overflow-hidden">
          <div class="border-b border-amber-100 bg-amber-50/50 p-5">
            <div class="mb-3 inline-block rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
              {{ $t('dept.compare.historical_match', { year: match.year }) }}
            </div>
            <h2 class="text-xl font-bold text-slate-900">{{ match.title }}</h2>
            <p class="mt-1 text-sm font-medium text-slate-600">{{ match.domain }}</p>
          </div>
          <div class="flex-1 space-y-4 p-5">
            <div>
              <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500 mb-3">{{ $t('similarity.breakdown') }}</h3>
              <div class="space-y-2.5">
                <div v-for="d in breakdown" :key="d.key" class="flex items-center gap-3 text-sm">
                  <span class="w-24 shrink-0 text-slate-600">{{ d.label }}</span>
                  <div class="h-2 flex-1 rounded-full bg-slate-100">
                    <div class="h-2 rounded-full bg-amber-500" :style="{ width: (d.value ?? 0) + '%' }"></div>
                  </div>
                  <span class="w-12 text-end font-medium text-slate-700">{{ d.value !== null ? d.value + '%' : '—' }}</span>
                </div>
              </div>
            </div>
            <div v-if="match.verdict || match.explanation" class="border-t border-slate-100 pt-4 text-sm text-slate-700">
              <p v-if="match.verdict"><span class="font-semibold">{{ $t('similarity.verdict') }}:</span> {{ match.verdict }}</p>
              <p v-if="match.explanation" class="mt-1 leading-relaxed">{{ match.explanation }}</p>
            </div>
          </div>
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
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const route = useRoute()
const { t } = useI18n()

<<<<<<< Updated upstream
const loading = ref(true)
const proposal = ref(null)
const match = ref(null)

const backLink = computed(() => ({ name: 'DepartmentProposal', params: { id: route.params.id } }))

const tags = computed(() =>
  (proposal.value?.tags ?? '').split(',').map(s => s.trim()).filter(Boolean)
)

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
=======
// Current mock data for the proposal
const currentProposal = ref({
  title: 'Smart Library Management System with NFC',
  author: 'Tayri Mousa Ali',
  department: 'Software Engineering',
  problem: 'Manual library checkout and return processes cause bottlenecks, human errors in records, and difficulty in finding relevant academic materials.',
  solution: 'A web platform to manage library operations including borrowing, returns, and inventory using NFC technology and an intelligent recommendation engine.',
  objectives: '1. Automate inventory tracking with NFC tags\n2. Reduce checkout times by 80%\n3. Provide smart book recommendations based on major',
  functions: 'NFC checkout scanner, Student book reservation portal, Real-time overdue alerts, AI book recommendation algorithm',
  tags: 'library, NFC, recommendation, smart_system',
  tech: 'Vue.js, Laravel, Python, PostgreSQL, NFC SDK'
})

// Historical mock data based on route param
const historicalProposal = ref({
  title: 'Network Traffic Anomaly Detector using ML', 
  author: 'Karim Adel', 
  year: '2024', 
  score: '78%',
  department: 'Computer Networks',
  problem: 'Traditional signature-based firewall and IDS tools fail to detect novel zero-day network intrusions in campus data centers.',
  solution: 'A machine learning-based framework to detect anomalies in real-time network traffic using deep neural networks and flow inspection.',
  objectives: '1. Detect zero-day anomalous traffic patterns\n2. Maintain latency below 5ms per packet inspection\n3. Integrate with existing firewall rules automatically',
  functions: 'Packet capturing engine, Deep learning anomaly classifier, Admin alert console, Automated firewall blocking rule generator',
  tags: 'machine_learning, IDS, security, network',
  tech: 'Python, TensorFlow, Scikit-learn, Snort, FastAPI'
})

const closestMatches = [
  { 
    id: 1,
    title: 'Network Traffic Anomaly Detector using ML', 
    author: 'Karim Adel', 
    year: '2024', 
    score: '78%',
    department: 'Computer Networks',
    problem: 'Traditional signature-based firewall and IDS tools fail to detect novel zero-day network intrusions in campus data centers.',
    solution: 'A machine learning-based framework to detect anomalies in real-time network traffic using deep neural networks and flow inspection.',
    objectives: '1. Detect zero-day anomalous traffic patterns\n2. Maintain latency below 5ms per packet inspection\n3. Integrate with existing firewall rules automatically',
    functions: 'Packet capturing engine, Deep learning anomaly classifier, Admin alert console, Automated firewall blocking rule generator',
    tags: 'machine_learning, IDS, security, network',
    tech: 'Python, TensorFlow, Scikit-learn, Snort, FastAPI'
  },
  { 
    id: 2,
    title: 'Real-time IDS with Random Forests', 
    author: 'Lina Hassen', 
    year: '2023', 
    score: '64%',
    department: 'Computer Networks',
    problem: 'High processing overhead in packet inspection tools causes network latency and dropped connections during peak traffic hours.',
    solution: 'An intrusion detection system implemented using optimized Random Forest classifiers and feature selection.',
    objectives: '1. Minimize feature dimensionality for fast classification\n2. Achieve >95% accuracy on benchmark intrusion datasets',
    functions: 'Real-time flow collector, Random Forest analyzer, Security alerts dashboard',
    tags: 'random_forest, security, intrusion_detection',
    tech: 'Python, Scikit-learn, Redis, Docker'
  },
]

onMounted(() => {
  const matchId = route.params.id
  if (matchId) {
    const found = closestMatches.find(m => m.id === parseInt(matchId))
    if (found) {
      historicalProposal.value = found
    }
>>>>>>> Stashed changes
  }
})
</script>
