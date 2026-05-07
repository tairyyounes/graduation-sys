<template>
  <section class="space-y-5">
    <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-4xl">Decisions</h1>
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
      <table class="min-w-full text-left text-sm">
        <thead class="bg-slate-50 text-slate-500">
          <tr>
            <th class="px-4 py-3 font-medium">Title</th>
            <th class="px-4 py-3 font-medium">Author</th>
            <th class="px-4 py-3 font-medium">Department</th>
            <th class="px-4 py-3 font-medium">Status</th>
            <th class="px-4 py-3 font-medium">Updated</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="decision in decisionsRows" :key="decision.title" class="border-t border-slate-100">
            <td class="px-4 py-3 text-slate-900">{{ decision.title }}</td>
            <td class="px-4 py-3 text-slate-600">{{ decision.author }}</td>
            <td class="px-4 py-3 text-slate-600">{{ decision.department }}</td>
            <td class="px-4 py-3"><span :class="statusClass(decision.status)" class="rounded-full px-2.5 py-1 text-xs font-semibold">{{ decision.status }}</span></td>
            <td class="px-4 py-3 text-slate-500">{{ decision.updated }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
const decisionsRows = [
  { title: 'Smart Library Management System with NFC', author: 'Tayri Mousa Ali', department: 'Programming', status: 'Accepted', updated: '2026-04-22' },
  { title: 'AI-based Network Intrusion Detection', author: 'Shaymaa Salem Ambashi', department: 'Networks', status: 'Needs revision', updated: '2026-04-25' },
  { title: 'Campus Mesh Wi-Fi Optimization', author: 'Sara Omar', department: 'Networks', status: 'Rejected', updated: '2026-04-15' },
]

const statusClass = (status) => {
  if (status === 'Accepted') return 'bg-emerald-100 text-emerald-700'
  if (status === 'Needs revision') return 'bg-cyan-100 text-cyan-700'
  if (status === 'Rejected') return 'bg-red-100 text-red-700'
  if (status === 'Pending') return 'bg-amber-100 text-amber-700'
  return 'bg-slate-100 text-slate-700'
}
</script>
