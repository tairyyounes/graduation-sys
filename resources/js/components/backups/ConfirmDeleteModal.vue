<template>
  <div
    v-if="isOpen && backupItem"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs transition-opacity"
    @click.self="$emit('close')"
  >
    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl transition-all">
      <!-- Modal Header -->
      <div class="flex items-center gap-3">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-rose-50 text-rose-600">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </div>
        <div>
          <h3 class="text-base font-bold text-slate-900">
            {{ $t('backups.delete_modal.title') }}
          </h3>
          <p class="text-xs text-slate-500">
            {{ $t('backups.delete_modal.warning') }}
          </p>
        </div>
      </div>

      <!-- Item Details Card -->
      <div class="mt-4 rounded-xl border border-slate-100 bg-slate-50 p-3.5 space-y-1 text-xs">
        <div class="flex items-center justify-between">
          <span class="text-slate-500">{{ $t('backups.delete_modal.file') }}</span>
          <span class="font-mono font-bold text-slate-800 truncate max-w-[200px]" :title="backupItem.file_name">
            {{ backupItem.file_name }}
          </span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-slate-500">{{ $t('backups.delete_modal.disk') }}</span>
          <span class="font-semibold text-slate-700">{{ backupItem.disk }}</span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-slate-500">{{ $t('backups.table.size') }}</span>
          <span class="font-mono text-slate-700">{{ backupItem.formatted_size }}</span>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="mt-6 flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
        <button
          type="button"
          class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50"
          :disabled="loading"
          @click="$emit('close')"
        >
          {{ $t('backups.actions.cancel') }}
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-rose-700 disabled:opacity-50"
          :disabled="loading"
          @click="$emit('confirm', backupItem)"
        >
          <svg v-if="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span>{{ $t('backups.actions.confirm_delete') }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  backupItem: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['close', 'confirm'])
</script>
