<template>
  <section class="space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      <div class="p-6 sm:p-8 border-b border-slate-100 bg-slate-50/30 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <h3 class="text-lg font-semibold text-slate-900">Team Members</h3>
        <button
          v-if="teamMembers.length < 3"
          @click="$emit('open-invite')"
          type="button"
          class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 transition ring-1 ring-teal-600 shrink-0"
        >
          <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
          Invite Member
        </button>
        <div v-else class="text-sm text-amber-600 bg-amber-50 px-3 py-1.5 rounded-md border border-amber-200 font-medium">
          Maximum team size reached (3/3)
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="py-4 pl-6 pr-3 text-left text-sm font-semibold text-slate-900">Name</th>
              <th class="px-3 py-4 text-left text-sm font-semibold text-slate-900">Registration Number</th>
              <th class="px-3 py-4 text-left text-sm font-semibold text-slate-900">Role</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr v-for="member in teamMembers" :key="member.id" class="hover:bg-slate-50/50 transition-colors">
              <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-slate-900">
                <div class="flex items-center">
                  <div class="h-8 w-8 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs mr-3">
                    {{ member.name ? member.name.split(' ').map(n => n[0]).join('') : '' }}
                  </div>
                  {{ member.name }}
                </div>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600">{{ member.regNumber }}</td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600">
                <span :class="[
                  member.role === 'Owner' ? 'bg-teal-50 text-teal-700 ring-teal-600/20' : 'bg-slate-100 text-slate-700 ring-slate-500/10',
                  'inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium ring-1 ring-inset'
                ]">
                  {{ member.role }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>

<script setup>
defineProps({
  teamMembers: {
    type: Array,
    required: true,
  },
})

defineEmits(['open-invite'])
</script>
