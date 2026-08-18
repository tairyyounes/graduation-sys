<template>
  <section class="space-y-5">
    <div class="flex items-center justify-between">
      <router-link :to="{ name: 'DepartmentProposal' }" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-teal-600 transition">
        <svg class="me-1 h-4 w-4 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        {{ $t('dept.compare.back') }}
      </router-link>
      <div class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">
        {{ $t('dept.compare.similarity_score', { score: historicalProposal.score }) }}
      </div>
    </div>

    <div>
      <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">{{ $t('dept.compare.title') }}</h1>
      <p class="mt-1 text-sm text-slate-500">{{ $t('dept.compare.subtitle') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <!-- Left Column: Current Proposal -->
      <article class="flex flex-col rounded-2xl border border-teal-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-teal-100 bg-teal-50/50 p-5">
          <div class="mb-3 inline-block rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-semibold text-teal-800">
            {{ $t('dept.compare.current') }}
          </div>
          <h2 class="text-xl font-bold text-slate-900">{{ currentProposal.title }}</h2>
          <p class="mt-1 text-sm font-medium text-slate-600">{{ currentProposal.author }} · Programming</p>
        </div>
        <div class="flex-1 p-5">
          <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500 mb-3">{{ $t('dept.compare.abstract') }}</h3>
          <p class="text-base leading-relaxed text-slate-700 whitespace-pre-wrap">
            {{ currentProposal.description }}
          </p>
          
          <div class="mt-6 border-t border-slate-100 pt-5">
             <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500 mb-3">{{ $t('dept.compare.keywords') }}</h3>
             <div class="flex flex-wrap gap-2">
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">#library</span>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">#NFC</span>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">#recommendation</span>
             </div>
          </div>
        </div>
      </article>

      <!-- Right Column: Historical Proposal -->
      <article class="flex flex-col rounded-2xl border border-amber-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-amber-100 bg-amber-50/50 p-5">
          <div class="mb-3 inline-block rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
            {{ $t('dept.compare.historical_match', { year: historicalProposal.year }) }}
          </div>
          <h2 class="text-xl font-bold text-slate-900">{{ historicalProposal.title }}</h2>
          <p class="mt-1 text-sm font-medium text-slate-600">{{ historicalProposal.author }}</p>
        </div>
        <div class="flex-1 p-5">
          <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500 mb-3">{{ $t('dept.compare.abstract') }}</h3>
          <p class="text-base leading-relaxed text-slate-700 whitespace-pre-wrap">
            {{ historicalProposal.description }}
          </p>

          <div class="mt-6 border-t border-slate-100 pt-5">
             <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500 mb-3">{{ $t('dept.compare.keywords') }}</h3>
             <div class="flex flex-wrap gap-2">
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">#machine_learning</span>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">#IDS</span>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">#security</span>
             </div>
          </div>
        </div>
      </article>
    </div>

    <!-- Actions -->
    <div class="flex justify-end gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <router-link
        :to="{ name: 'DepartmentProposal' }"
        class="rounded-lg border border-slate-300 bg-white px-5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
      >
        {{ $t('dept.compare.done') }}
      </router-link>
      <button class="rounded-lg bg-red-600 px-5 py-2 text-sm font-medium text-white transition hover:bg-red-700">
        {{ $t('dept.compare.flag') }}
      </button>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

// Current mock data for the proposal
const currentProposal = ref({
  title: 'Smart Library Management System with NFC',
  author: 'Tayri Mousa Ali',
  description: 'A web platform to manage library operations including borrowing, returns, and inventory using NFC technology and a recommendation engine. The system will track book usage, predict popular categories, and offer an interactive portal for students to find study materials based on their major.'
})

// Historical mock data based on route param
const historicalProposal = ref({
  title: 'Network Traffic Anomaly Detector using ML', 
  author: 'Karim Adel', 
  year: '2024', 
  score: '78%',
  description: 'This project introduces a machine learning-based framework to detect anomalies in real-time network traffic. It utilizes deep neural networks to identify patterns associated with zero-day attacks and integrates with existing firewall infrastructures.'
})

const closestMatches = [
  { 
    id: 1,
    title: 'Network Traffic Anomaly Detector using ML', 
    author: 'Karim Adel', 
    year: '2024', 
    score: '78%',
    description: 'This project introduces a machine learning-based framework to detect anomalies in real-time network traffic. It utilizes deep neural networks to identify patterns associated with zero-day attacks and integrates with existing firewall infrastructures.'
  },
  { 
    id: 2,
    title: 'Real-time IDS with Random Forests', 
    author: 'Lina Hassen', 
    year: '2023', 
    score: '64%',
    description: 'An intrusion detection system implemented using Random Forest classifiers. The project evaluates feature selection algorithms to minimize the latency of packet inspection while maintaining a high detection accuracy on standard datasets.'
  },
  { 
    id: 3,
    title: 'Hybrid IDS combining Snort and Neural Nets', 
    author: 'Yousef Tariq', 
    year: '2022', 
    score: '51%',
    description: 'Combining rule-based detection via Snort with an artificial neural network. This hybrid approach significantly reduces false positives by passing flagged packets through a secondary AI verification step before alerting administrators.'
  },
  { 
    id: 4,
    title: 'Anomaly-based Network Security Toolkit', 
    author: 'Mariam Khaled', 
    year: '2024', 
    score: '47%',
    description: 'A comprehensive toolkit designed for university networks to monitor abnormal behavior. The toolkit provides a dashboard summarizing alert trends and uses statistical analysis rather than pure machine learning.'
  },
]

onMounted(() => {
  // If an ID is passed in the route, we fetch that specific historical match to display.
  // In a real app, this would be an API call.
  const matchId = route.params.id
  if (matchId) {
    const found = closestMatches.find(m => m.id === parseInt(matchId))
    if (found) {
      historicalProposal.value = found
    }
  }
})
</script>
