<template>
  <section class="space-y-6">
    <!-- Team requests received by this student -->
    <div v-if="invitations.length" class="rounded-xl border border-amber-200 bg-amber-50/60 shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-amber-200/70">
        <h3 class="text-base font-semibold text-slate-900">{{ $t('student.team.incoming_title') }}</h3>
        <p class="mt-1 text-sm text-slate-600">{{ $t('student.team.incoming_desc') }}</p>
      </div>
      <ul class="divide-y divide-amber-200/70">
        <li
          v-for="inv in invitations"
          :key="inv.proposal_id"
          class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
        >
          <div class="min-w-0">
            <p class="text-sm font-medium text-slate-900">
              {{ $t('student.team.incoming_from', { name: inv.from_name, number: inv.from_reg_number }) }}
            </p>
            <p v-if="inv.title" class="mt-0.5 text-sm text-slate-600 truncate">{{ $t('student.team.incoming_project', { title: inv.title }) }}</p>
          </div>
          <div class="flex gap-2 shrink-0">
            <button
              type="button"
              :disabled="busy"
              @click="respond('accept-invitation', inv)"
              class="inline-flex items-center rounded-lg bg-teal-600 px-3.5 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 disabled:opacity-50 transition"
            >{{ $t('student.team.accept') }}</button>
            <button
              type="button"
              :disabled="busy"
              @click="respond('reject-invitation', inv)"
              class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50 transition"
            >{{ $t('student.team.decline') }}</button>
          </div>
        </li>
      </ul>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      <div class="p-6 sm:p-8 border-b border-slate-100 bg-slate-50/30 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <h3 class="text-lg font-semibold text-slate-900">{{ $t('student.team.title') }}</h3>
        <div v-if="teamMembers.length >= maxSize" class="text-sm text-teal-700 bg-teal-50 px-3 py-1.5 rounded-md border border-teal-200 font-medium">
          {{ $t('student.team.complete', { max: maxSize }) }}
        </div>
        <button
          v-else-if="!isPending"
          @click="$emit('open-invite')"
          type="button"
          class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 transition ring-1 ring-teal-600 shrink-0"
        >
          <svg class="w-4 h-4 me-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
          {{ $t('student.team.invite_member') }}
        </button>
      </div>

      <!-- Status of the request this team sent -->
      <div
        v-if="teamRequest && teamMembers.length < maxSize"
        class="px-6 py-4 border-b flex flex-col sm:flex-row sm:items-center justify-between gap-3"
        :class="isPending ? 'bg-amber-50/60 border-amber-100' : 'bg-red-50/60 border-red-100'"
      >
        <div class="flex items-start gap-3">
          <span
            class="mt-0.5 inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset shrink-0"
            :class="isPending ? 'bg-amber-100 text-amber-800 ring-amber-600/20' : 'bg-red-100 text-red-700 ring-red-600/20'"
          >{{ isPending ? $t('student.team.status_pending') : $t('student.team.status_rejected') }}</span>
          <p class="text-sm text-slate-700">
            {{ isPending
              ? $t('student.team.request_pending', { name: teamRequest.name, number: teamRequest.regNumber })
              : $t('student.team.request_rejected', { name: teamRequest.name, number: teamRequest.regNumber }) }}
          </p>
        </div>
        <button
          v-if="isPending"
          type="button"
          :disabled="busy"
          @click="respond('cancel-request')"
          class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50 transition shrink-0"
        >{{ $t('student.team.cancel_request') }}</button>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="py-4 ps-6 pe-3 text-start text-sm font-semibold text-slate-900">{{ $t('student.team.name') }}</th>
              <th class="px-3 py-4 text-start text-sm font-semibold text-slate-900">{{ $t('student.team.reg_number') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr v-for="member in teamMembers" :key="member.id" class="hover:bg-slate-50/50 transition-colors">
              <td class="whitespace-nowrap py-4 ps-6 pe-3 text-sm font-medium text-slate-900">
                <div class="flex items-center">
                  <div class="h-8 w-8 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs me-3">
                    {{ member.name ? member.name.split(' ').map(n => n[0]).join('') : '' }}
                  </div>
                  {{ member.name }}
                </div>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600">{{ member.regNumber }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  teamMembers: {
    type: Array,
    required: true,
  },
  maxSize: {
    type: Number,
    default: 2,
  },
  teamRequest: {
    type: Object,
    default: null,
  },
  invitations: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['open-invite', 'cancel-request', 'accept-invitation', 'reject-invitation'])

const isPending = computed(() => props.teamRequest?.status === 'pending')

// Brief guard against double clicks while the parent sends the request.
const busy = ref(false)
function respond(event, payload) {
  busy.value = true
  emit(event, payload)
  setTimeout(() => { busy.value = false }, 1500)
}
</script>
