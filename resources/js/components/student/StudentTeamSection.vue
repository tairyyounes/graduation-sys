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

    <!-- Not in any team: a team always belongs to a proposal (a draft is enough) -->
    <div
      v-if="!hasTeam"
      class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white py-14 px-6 text-center shadow-sm"
    >
      <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      </div>
      <h3 class="text-base font-semibold text-slate-900">{{ $t('student.team.no_team_title') }}</h3>
      <p class="mt-1 max-w-md text-sm text-slate-500">{{ $t('student.team.no_team_desc') }}</p>
      <button
        type="button"
        @click="$emit('new-proposal')"
        class="mt-5 inline-flex items-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 transition"
      >
        <svg class="w-4 h-4 me-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
        {{ $t('student.team.no_team_action') }}
      </button>
    </div>

    <div v-else class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
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

      <!-- Leave requests waiting for the department's decision -->
      <div
        v-for="req in leavePending"
        :key="req.id"
        class="px-6 py-4 border-t border-amber-100 bg-amber-50/60 flex flex-col sm:flex-row sm:items-start justify-between gap-3"
      >
        <div class="flex items-start gap-3 min-w-0">
          <span class="mt-0.5 inline-flex items-center rounded-md bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-600/20 shrink-0">
            {{ $t('student.leave.status_pending') }}
          </span>
          <div class="min-w-0">
            <p class="text-sm font-medium text-slate-800">
              {{ req.mine
                ? $t('student.leave.my_request_pending')
                : $t('student.leave.teammate_request_pending', { name: req.name, number: req.regNumber }) }}
            </p>
            <p class="mt-1 text-sm text-slate-600 break-words">{{ $t('student.leave.reason_label') }}: {{ req.reason }}</p>
          </div>
        </div>
        <button
          v-if="req.mine"
          type="button"
          :disabled="busy"
          @click="respond('cancel-leave-request')"
          class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50 transition shrink-0"
        >{{ $t('student.leave.withdraw') }}</button>
      </div>

      <!-- Leave the team: directly without an active proposal, otherwise by request -->
      <div
        v-if="leaveMode === 'direct' || (leaveMode === 'request' && !myLeaveRequest)"
        class="px-6 py-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
      >
        <p class="text-sm text-slate-500">
          {{ leaveMode === 'direct' ? $t('student.leave.direct_hint') : $t('student.leave.request_hint') }}
        </p>
        <button
          v-if="leaveMode === 'direct'"
          type="button"
          :disabled="busy"
          @click="confirmLeave"
          class="inline-flex items-center rounded-lg border border-red-200 bg-white px-3.5 py-2 text-sm font-medium text-red-600 shadow-sm hover:bg-red-50 disabled:opacity-50 transition shrink-0"
        >{{ $t('student.leave.leave_button') }}</button>
        <button
          v-else
          type="button"
          @click="openLeaveForm"
          class="inline-flex items-center rounded-lg border border-red-200 bg-white px-3.5 py-2 text-sm font-medium text-red-600 shadow-sm hover:bg-red-50 transition shrink-0"
        >{{ $t('student.leave.request_button') }}</button>
      </div>
    </div>

    <!-- Leave request form (active proposal) -->
    <div v-if="showLeaveForm" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex min-h-screen items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeLeaveForm"></div>
        <form
          @submit.prevent="sendLeaveRequest"
          class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white text-start shadow-xl"
        >
          <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-5">
            <h3 class="text-lg font-bold text-slate-900">{{ $t('student.leave.form_title') }}</h3>
          </div>
          <div class="px-6 py-6 space-y-3">
            <p class="text-sm text-slate-500">{{ $t('student.leave.form_desc') }}</p>
            <label for="leave-reason" class="block text-sm font-medium text-slate-700">
              {{ $t('student.leave.reason_label') }} <span class="text-red-500">*</span>
            </label>
            <textarea
              id="leave-reason"
              v-model="leaveReason"
              rows="4"
              maxlength="1000"
              class="w-full rounded-lg border px-4 py-2 shadow-sm sm:text-sm focus:border-teal-500 focus:ring-teal-500"
              :class="leaveError ? 'border-red-400' : 'border-slate-300'"
              :placeholder="$t('student.leave.reason_placeholder')"
            ></textarea>
            <p v-if="leaveError" class="text-sm text-red-600">{{ leaveError }}</p>
          </div>
          <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
            <button type="button" @click="closeLeaveForm" class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 transition-colors">
              {{ $t('common.cancel') }}
            </button>
            <button type="submit" :disabled="sending" class="inline-flex justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 disabled:opacity-50 transition-colors">
              {{ $t('student.leave.send_request') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  // False when the student has no proposal (not even a draft), so no team either.
  hasTeam: {
    type: Boolean,
    default: true,
  },
  teamMembers: {
    type: Array,
    required: true,
  },
  maxSize: {
    type: Number,
    default: 3,
  },
  teamRequest: {
    type: Object,
    default: null,
  },
  invitations: {
    type: Array,
    default: () => [],
  },
  // { mode: 'direct' | 'request' | 'none', pending: [...] } from the team endpoint
  leave: {
    type: Object,
    default: null,
  },
  // Sends the leave request; resolves to an error message, or null on success.
  submitLeaveRequest: {
    type: Function,
    default: null,
  },
})

const emit = defineEmits(['new-proposal', 'open-invite','cancel-request', 'accept-invitation', 'reject-invitation', 'leave-team', 'cancel-leave-request'])

const leaveMode = computed(() => props.leave?.mode ?? 'none')
const leavePending = computed(() => props.leave?.pending ?? [])
const myLeaveRequest = computed(() => leavePending.value.find(r => r.mine) ?? null)

function confirmLeave() {
  if (confirm(t('student.leave.confirm'))) respond('leave-team')
}

const showLeaveForm = ref(false)
const leaveReason = ref('')
const leaveError = ref('')
const sending = ref(false)

function openLeaveForm() {
  leaveReason.value = ''
  leaveError.value = ''
  showLeaveForm.value = true
}

function closeLeaveForm() {
  showLeaveForm.value = false
}

async function sendLeaveRequest() {
  if (!leaveReason.value.trim()) {
    leaveError.value = t('student.leave.reason_required')
    return
  }
  if (!props.submitLeaveRequest) return
  sending.value = true
  const error = await props.submitLeaveRequest(leaveReason.value.trim())
  sending.value = false
  if (error) {
    leaveError.value = error
  } else {
    showLeaveForm.value = false
  }
}

const isPending = computed(() => props.teamRequest?.status === 'pending')

// Brief guard against double clicks while the parent sends the request.
const busy = ref(false)
function respond(event, payload) {
  busy.value = true
  emit(event, payload)
  setTimeout(() => { busy.value = false }, 1500)
}
</script>
