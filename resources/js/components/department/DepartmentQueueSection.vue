<template>
  <section class="space-y-5">
    <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-4xl">Review queue</h1>
    <div class="hidden overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm md:block">
      <table class="min-w-full text-left text-sm">
        <thead class="bg-slate-50 text-slate-500">
          <tr>
            <th class="px-4 py-3 font-medium">Title</th>
            <th class="px-4 py-3 font-medium">Author</th>
            <th class="px-4 py-3 font-medium">Department</th>
            <th class="px-4 py-3 font-medium">Similarity</th>
            <th class="px-4 py-3 font-medium">Status</th>
            <th class="px-4 py-3 font-medium">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in queueRows" :key="row.title" class="border-t border-slate-100">
            <td class="px-4 py-3 text-slate-900">{{ row.title }}</td>
            <td class="px-4 py-3 text-slate-600">{{ row.author }}</td>
            <td class="px-4 py-3 text-slate-600">{{ row.department }}</td>
            <td class="px-4 py-3"><span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ row.similarity }}</span></td>
            <td class="px-4 py-3"><span :class="statusClass(row.status)" class="rounded-full px-2.5 py-1 text-xs font-semibold">{{ row.status }}</span></td>
            <td class="px-4 py-3">
              <router-link :to="{ name: 'DepartmentProposal' }" class="rounded-md border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
                View
              </router-link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
const queueRows = [
  { title: 'Smart Library Management System with NFC', author: 'Tayri Mousa Ali', department: 'Programming', similarity: '34%', status: 'Accepted' },
  { title: 'AI-based Network Intrusion Detection', author: 'Shaymaa Salem Ambashi', department: 'Networks', similarity: '78%', status: 'Needs revision' },
  { title: 'Smart Greenhouse Control System', author: 'Ahmed Khalid', department: 'Control', similarity: '22%', status: 'Pending' },
  { title: 'Graduation Project Similarity Detection Platform', author: 'Tayri Mousa Ali', department: 'Programming', similarity: '12%', status: 'Analyzing' },
  { title: 'Campus Mesh Wi-Fi Optimization', author: 'Sara Omar', department: 'Networks', similarity: '41%', status: 'Rejected' },
]

const statusClass = (status) => {
  if (status === 'Accepted') return 'bg-emerald-100 text-emerald-700'
  if (status === 'Needs revision') return 'bg-cyan-100 text-cyan-700'
  if (status === 'Rejected') return 'bg-red-100 text-red-700'
  if (status === 'Pending') return 'bg-amber-100 text-amber-700'
  return 'bg-slate-100 text-slate-700'
}
</script>
