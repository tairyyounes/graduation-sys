<template>
  <section class="space-y-5">
    <!-- Page title moved to layout header -->

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <article v-for="card in overviewCards" :key="card.title" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md transition-shadow">
        <p class="text-sm text-slate-500">{{ card.title }}</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ card.value }}</p>
      </article>
    </div>

    <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="mb-3 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-900">Review queue</h2>
        <router-link :to="{ name: 'DepartmentQueue' }" class="text-sm font-medium text-slate-700 hover:text-teal-600 transition">View all</router-link>
      </div>
      <div class="space-y-3">
        <div v-for="proposal in queueRows.slice(0, 2)" :key="proposal.title" class="flex flex-col gap-2 border-t border-slate-100 pt-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="text-sm font-medium text-slate-900">{{ proposal.title }}</p>
            <p class="text-xs text-slate-500">{{ proposal.author }} · {{ proposal.department }}</p>
          </div>
          <div class="flex items-center gap-2">
            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ proposal.similarity }}</span>
            <span :class="statusClass(proposal.status)" class="rounded-full px-2.5 py-1 text-xs font-semibold">{{ proposal.status }}</span>
          </div>
        </div>
      </div>
    </article>
  </section>
</template>

<script setup>
const overviewCards = [
  { title: 'Review queue', value: '2' },
  { title: 'Accepted', value: '1' },
  { title: 'Needs revision', value: '1' },
  { title: 'Overall similarity', value: '47%' },
]

const queueRows = [
  { title: 'Smart Library Management System with NFC', author: 'Tayri Mousa Ali', department: 'Programming', similarity: '34%', status: 'Accepted' },
  { title: 'AI-based Network Intrusion Detection', author: 'Shaymaa Salem Ambashi', department: 'Networks', similarity: '78%', status: 'Needs revision' },
  { title: 'Smart Greenhouse Control System', author: 'Ahmed Khalid', department: 'Control', similarity: '22%', status: 'Pending' },
]

const statusClass = (status) => {
  if (status === 'Accepted') return 'bg-emerald-100 text-emerald-700'
  if (status === 'Needs revision') return 'bg-cyan-100 text-cyan-700'
  if (status === 'Rejected') return 'bg-red-100 text-red-700'
  if (status === 'Pending') return 'bg-amber-100 text-amber-700'
  return 'bg-slate-100 text-slate-700'
}
</script>
