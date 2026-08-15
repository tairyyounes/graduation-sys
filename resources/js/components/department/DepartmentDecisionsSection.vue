<template>
  <section class="space-y-5">
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
      <table class="min-w-full text-start text-sm">
        <thead class="bg-slate-50 text-slate-500">
          <tr>
            <th class="px-4 py-3 font-medium">{{ $t('fields.title') }}</th>
            <th class="px-4 py-3 font-medium">{{ $t('fields.author') }}</th>
            <th class="px-4 py-3 font-medium">{{ $t('fields.department') }}</th>
            <th class="px-4 py-3 font-medium">{{ $t('fields.status') }}</th>
            <th class="px-4 py-3 font-medium">{{ $t('dept.updated') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="decision in decisionsRows" :key="decision.title" class="border-t border-slate-100">
            <td class="px-4 py-3 text-slate-900">{{ decision.title }}</td>
            <td class="px-4 py-3 text-slate-600">{{ decision.author }}</td>
            <td class="px-4 py-3 text-slate-600">{{ decision.department }}</td>
            <td class="px-4 py-3"><span :class="statusClass(decision.status)" class="rounded-full px-2.5 py-1 text-xs font-semibold">{{ formatStatus(decision.status) }}</span></td>
            <td class="px-4 py-3 text-slate-500">{{ decision.date }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const { t } = useI18n()

const decisionsRows = ref([])

onMounted(async () => {
  try {
    const res = await axios.get('/department/proposals?status=submitted')
    // Filter out pending ones if we only want final decisions, but usually decisions section shows everything that was reviewed
    decisionsRows.value = res.data.proposals.filter(p => p.status !== 'pending')
  } catch (error) {
    console.error('Error fetching decisions:', error)
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
  const key = `status.${status}`
  const translated = t(key)
  return translated === key ? status.charAt(0).toUpperCase() + status.slice(1) : translated
}
</script>
