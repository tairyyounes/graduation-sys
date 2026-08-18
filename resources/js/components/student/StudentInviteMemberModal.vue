<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

      <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 w-full max-w-md flex flex-col">
        <!-- Header -->
        <div class="border-b border-slate-100 px-6 py-5 bg-slate-50/50 flex justify-between items-center">
          <h3 class="text-lg font-bold text-slate-900">{{ $t('messages.invite_team_member') }}</h3>
          <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- Body -->
        <div class="px-6 py-6">
          <p class="text-sm text-slate-500 mb-4">Enter the registration number of the student you wish to invite to your project team.</p>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('messages.registration_number') }}</label>
            <input
              :value="regNumber"
              @input="$emit('update:regNumber', $event.target.value)"
              type="text"
              class="w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-4 py-2 border"
              placeholder="e.g. 2023055"
            >
            <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="bg-slate-50 px-6 py-4 flex justify-end gap-3 border-t border-slate-100">
          <button @click="$emit('close')" type="button" class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none transition-colors">
            Cancel
          </button>
          <button @click="$emit('send')" type="button" class="inline-flex justify-center rounded-lg border border-transparent bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none transition-colors">
            Send Invitation
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  isOpen: {
    type: Boolean,
    required: true,
  },
  regNumber: {
    type: String,
    default: '',
  },
  error: {
    type: String,
    default: '',
  },
})

defineEmits(['close', 'send', 'update:regNumber'])
</script>
