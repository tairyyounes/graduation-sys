<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

      <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 w-full max-w-3xl flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="border-b border-slate-100 px-6 py-5 bg-slate-50/50 flex justify-between items-center shrink-0">
          <h3 class="text-xl font-bold text-slate-900">{{ $t('proposals.draft_new') }}</h3>
          <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- Form Body -->
        <div class="px-6 py-6 overflow-y-auto flex-1 space-y-5">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('proposals.title') }}</label>
            <input v-model="form.title" type="text" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-4 py-2 border" placeholder="Enter a descriptive title" required>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Domain / Department</label>
            <input 
              :value="studentDepartment" 
              type="text" 
              class="w-full rounded-lg border-slate-200 bg-slate-50 shadow-sm sm:text-sm px-4 py-2 border cursor-not-allowed text-slate-500 font-medium" 
              disabled
            >
            <p class="mt-1 text-[11px] text-slate-400 italic">Proposals are automatically assigned to your specialization.</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('messages.problem_statement') }}</label>
            <textarea v-model="form.problem" rows="3" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-4 py-2 border" placeholder="What specific problem are you solving?" required></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('messages.proposed_solution') }}</label>
            <textarea v-model="form.solution" rows="3" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-4 py-2 border" placeholder="How does your project solve this problem?" required></textarea>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('common.objectives') }}</label>
              <textarea v-model="form.objectives" rows="2" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-4 py-2 border" placeholder="Main goals..." required></textarea>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('common.core_functions') }}</label>
              <textarea v-model="form.functions" rows="2" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-4 py-2 border" placeholder="Key features..." required></textarea>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Tags / Keywords</label>
              <input v-model="form.tags" type="text" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-4 py-2 border" placeholder="e.g. AI, Web App, Healthcare" required>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('messages.technology_used') }}</label>
              <input v-model="form.tech" type="text" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-4 py-2 border" placeholder="e.g. Vue, Laravel, Python" required>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="bg-slate-50 px-6 py-4 flex flex-wrap-reverse sm:flex-nowrap justify-between gap-3 border-t border-slate-100 shrink-0">
          <button @click="$emit('close')" type="button" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none transition-colors">
            Cancel
          </button>
          <div class="flex gap-3 w-full sm:w-auto">
            <template v-if="isEditing">
              <button @click="$emit('update')" type="button" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-transparent bg-teal-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none transition-colors">
                Update Proposal
              </button>
            </template>
            <template v-else>
              <button @click="$emit('save-draft')" type="button" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-teal-600 bg-white px-4 py-2.5 text-sm font-medium text-teal-700 shadow-sm hover:bg-teal-50 focus:outline-none transition-colors">
                Save as Draft
              </button>
              <button @click="$emit('confirm-proposal')" type="button" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-transparent bg-teal-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none transition-colors">
                Confirm Proposal
              </button>
            </template>
          </div>
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
  form: {
    type: Object,
    required: true,
  },
  isEditing: {
    type: Boolean,
    default: false,
  },
  studentDepartment: {
    type: String,
    default: '',
  },
})

defineEmits(['close', 'save-draft', 'confirm-proposal', 'update'])
</script>
