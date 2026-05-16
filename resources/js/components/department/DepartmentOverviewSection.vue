<template>
  <section class="space-y-5">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-4xl">Dashboard</h1>
      <p class="mt-1 text-sm text-slate-500 sm:text-base">Here's what's happening today.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <article v-for="card in overviewCards" :key="card.title" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-sm text-slate-500">{{ card.title }}</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ card.value }}</p>
      </article>
    </div>

    <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="mb-3 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-900">Review queue</h2>
        <router-link :to="{ name: 'DepartmentQueue' }" class="text-sm font-medium text-slate-700 hover:text-indigo-600 transition">View all</router-link>
      </div>
      <div class="space-y-3">
        <div v-for="proposal in queueRows.slice(0, 2)" :key="proposal.title" class="flex flex-col gap-2 border-t border-slate-100 pt-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="text-sm font-medium text-slate-900">{{ proposal.title }}</p>
            <p class="text-xs text-slate-500">{{ proposal.author }} · {{ proposal.department }}</p>
          </div>
          <div class="flex items-center gap-2">
            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ proposal.similarity }}</span>
            <span :class="statusClass(proposal.status)" class="rounded-full px-2.5 py-1 text-xs font-semibold">{{ formatStatus(proposal.status) }}</span>
          </div>
        </div>
      </div>
    </article>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const overviewCards = ref([
  { title: 'Review queue', value: '0' },
  { title: 'Accepted', value: '0' },
  { title: 'Needs revision', value: '0' },
  { title: 'Rejected', value: '0' },
])

const queueRows = ref([])

onMounted(async () => {
  try {
    const [statsRes, proposalsRes] = await Promise.all([
      axios.get('/department/stats'),
      axios.get('/department/proposals?status=submitted')
    ])
    
    const stats = statsRes.data.stats
    overviewCards.value = [
      { title: 'Review queue', value: stats.pending.toString() },
      { title: 'Accepted', value: stats.accepted.toString() },
      { title: 'Needs revision', value: stats.revision.toString() },
      { title: 'Rejected', value: stats.rejected.toString() },
    ]
    
    queueRows.value = proposalsRes.data.proposals
  } catch (error) {
    console.error('Error fetching department overview:', error)
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
  if (status === 'revision_requested') return 'Revision Needed'
  return status.charAt(0).toUpperCase() + status.slice(1)
}
</script>
