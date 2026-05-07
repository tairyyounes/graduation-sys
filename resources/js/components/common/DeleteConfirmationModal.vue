<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 px-4 py-8 backdrop-blur-sm transition-opacity"
    @click.self="$emit('close')"
  >
    <div class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl sm:p-6 transform transition-all">
      <div class="flex flex-col items-center text-center">
        <!-- Warning Icon -->
        <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-100 text-red-600">
          <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        
        <h3 class="mb-2 text-xl font-bold text-slate-900">{{ title }}</h3>
        <p class="mb-6 text-sm text-slate-500">{{ message }}</p>

        <div class="flex w-full flex-col-reverse gap-3 sm:flex-row">
          <button
            type="button"
            class="flex-1 rounded-xl border border-slate-300 bg-white py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400"
            @click="$emit('close')"
          >
            Cancel
          </button>
          <button
            type="button"
            class="flex-1 rounded-xl bg-red-600 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50"
            :disabled="isDeleting"
            @click="$emit('confirm')"
          >
            {{ isDeleting ? 'Deleting...' : 'Delete' }}
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
    required: true
  },
  isDeleting: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Are you sure?'
  },
  message: {
    type: String,
    default: 'This action cannot be undone. Are you sure you want to proceed?'
  }
})

defineEmits(['close', 'confirm'])
</script>
