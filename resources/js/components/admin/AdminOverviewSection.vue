<template>
  <section class="space-y-5 sm:space-y-6">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">Admin dashboard</h1>
      <p class="mt-1 text-sm text-slate-500 sm:text-base">Here's what's happening today.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <article
        v-for="card in overviewCards"
        :key="card.title"
        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
      >
        <p class="text-sm font-medium text-slate-500">{{ card.title }}</p>
        <p class="mt-3 text-3xl font-semibold leading-none text-slate-900 sm:text-4xl">{{ card.value }}</p>
      </article>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:gap-5">
      <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 lg:col-span-8">
        <h2 class="text-lg font-semibold text-slate-900 sm:text-xl">Submissions over time</h2>
        <div class="mt-4 grid h-56 grid-cols-6 items-end gap-2 sm:h-72 sm:gap-3">
          <div v-for="bar in submissionsBars" :key="bar.month" class="flex flex-col items-center gap-2">
            <div class="w-full rounded-lg bg-teal-600 transition-all duration-300" :style="{ height: `${bar.height}%` }"></div>
            <p class="text-xs text-slate-500 sm:text-sm">{{ bar.month }}</p>
          </div>
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 lg:col-span-4">
        <h2 class="text-lg font-semibold text-slate-900 sm:text-xl">Decision breakdown</h2>
        <div class="mt-6 flex justify-center sm:mt-8">
          <div class="h-40 w-40 rounded-full border-[22px] border-teal-500 border-t-sky-500 border-l-red-500 sm:h-48 sm:w-48 sm:border-[26px]"></div>
        </div>
        <div class="mt-6 flex flex-wrap justify-center gap-x-4 gap-y-2 text-sm text-slate-600 sm:text-base">
          <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-teal-500"></span>Accepted</span>
          <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>Rejected</span>
          <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-sky-500"></span>Revise</span>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const overviewCards = ref([
  { title: 'Proposals analyzed', value: '...' },
  { title: 'User management', value: '...' },
  { title: 'Departments', value: '...' },
  { title: 'Semantic accuracy', value: '...' },
])

const submissionsBars = ref([
  { month: 'Jan', height: 0 },
  { month: 'Feb', height: 0 },
  { month: 'Mar', height: 0 },
  { month: 'Apr', height: 0 },
  { month: 'May', height: 0 },
  { month: 'Jun', height: 0 },
])

const fetchStats = async () => {
  try {
    const res = await fetch('/admin/stats', {
      headers: { Accept: 'application/json' }
    })
    if (res.ok) {
      const data = await res.json()
      overviewCards.value = data.overviewCards
      submissionsBars.value = data.submissionsBars
    }
  } catch (err) {
    console.error('Failed to load admin stats:', err)
  }
}

onMounted(() => {
  fetchStats()
})
</script>
