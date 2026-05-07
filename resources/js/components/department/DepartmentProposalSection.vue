<template>
  <section class="space-y-5">
    <router-link :to="{ name: 'DepartmentQueue' }" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-indigo-600 transition">
      <svg class="mr-1 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      Back to queue
    </router-link>
    <div>
      <div class="mb-2 flex items-center gap-2 text-xs">
        <span class="rounded-full bg-emerald-100 px-2.5 py-1 font-semibold text-emerald-700">Accepted</span>
        <span class="text-slate-500">Programming</span>
      </div>
      <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-4xl">{{ selectedProposal.title }}</h1>
      <p class="mt-1 text-sm text-slate-500">{{ selectedProposal.author }} · 222075</p>
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
        <p class="mt-2 text-sm leading-6 text-slate-600">
          A web platform to manage library operations including borrowing, returns, and inventory using NFC technology
          and a recommendation engine.
        </p>
        <div class="mt-3 flex flex-wrap gap-2">
          <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-600">#library</span>
          <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-600">#NFC</span>
          <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-600">#recommendation</span>
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
            <button class="rounded-md border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">Compare</button>
          </div>
        </div>
      </div>
    </article>

    <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <h2 class="text-lg font-semibold text-slate-900">Reviewer note</h2>
      <textarea
        v-model="reviewerNote"
        rows="4"
        class="mt-3 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition"
        placeholder="Optional note for the student..."
      ></textarea>
      <div class="mt-3 flex flex-wrap justify-end gap-2">
        <button class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Request revision</button>
        <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">Reject</button>
        <button class="rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white hover:bg-blue-950">Accept</button>
      </div>
    </article>
  </section>
</template>

<script setup>
import { ref } from 'vue'

const reviewerNote = ref('')

const selectedProposal = ref({
  title: 'Smart Library Management System with NFC',
  author: 'Tayri Mousa Ali',
  department: 'Programming',
  similarity: '34%',
  status: 'Accepted'
})

const closestMatches = [
  { title: 'Network Traffic Anomaly Detector using ML', author: 'Karim Adel', year: '2024', score: '78%' },
  { title: 'Real-time IDS with Random Forests', author: 'Lina Hassen', year: '2023', score: '64%' },
  { title: 'Hybrid IDS combining Snort and Neural Nets', author: 'Yousef Tariq', year: '2022', score: '51%' },
  { title: 'Anomaly-based Network Security Toolkit', author: 'Mariam Khaled', year: '2024', score: '47%' },
]
</script>
