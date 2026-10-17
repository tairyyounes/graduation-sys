<template>
  <div
    v-if="isOpen && backupItem"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs transition-opacity"
    @click.self="handleClose"
  >
    <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl transition-all">
      <!-- Modal Header with Danger Badge -->
      <div class="mb-4 flex items-start gap-3 border-b border-slate-100 pb-4">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 ring-4 ring-rose-50">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <div>
          <h3 class="text-lg font-bold text-slate-900">
            {{ $t('backups.restore_modal.title') }}
          </h3>
          <p class="text-xs font-semibold text-rose-600">
            {{ $t('backups.restore_modal.danger_heading') }}
          </p>
        </div>
      </div>

      <!-- High-Risk Warning Callout -->
      <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-4 text-xs text-rose-800 leading-relaxed space-y-2">
        <p>
          {{ $t('backups.restore_modal.danger_text') }}
        </p>
        <div class="flex items-center gap-1.5 font-semibold text-[11px] text-rose-700">
          <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>A safety backup of current data will be created automatically before restore starts.</span>
        </div>
      </div>

      <!-- Selected Archive Info -->
      <div class="mt-4 rounded-xl border border-slate-100 bg-slate-50 p-3 space-y-1 text-xs">
        <div class="flex items-center justify-between">
          <span class="text-slate-500">{{ $t('backups.restore_modal.target_file') }}:</span>
          <span class="font-mono font-bold text-slate-900 truncate max-w-[220px]" :title="backupItem.file_name">
            {{ backupItem.file_name }}
          </span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-slate-500">{{ $t('backups.table.size') }}:</span>
          <span class="font-mono text-slate-700">{{ backupItem.formatted_size }}</span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-slate-500">{{ $t('backups.table.disk') }}:</span>
          <span class="font-semibold text-slate-700">{{ backupItem.disk }}</span>
        </div>
      </div>

      <!-- Form Inputs -->
      <form class="mt-4 space-y-4" @submit.prevent="handleSubmit">
        <!-- Admin Password Confirmation -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            {{ $t('backups.restore_modal.password_label') }} <span class="text-rose-500">*</span>
          </label>
          <input
            v-model="password"
            type="password"
            autocomplete="current-password"
            required
            :placeholder="$t('backups.restore_modal.password_ph')"
            class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20"
          />
        </div>

        <!-- Typed Phrase "RESTORE" -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            {{ $t('backups.restore_modal.phrase_instruction') }}
          </label>
          <input
            v-model="typedPhrase"
            type="text"
            required
            placeholder="RESTORE"
            class="w-full rounded-xl border font-mono tracking-wider text-xs px-3.5 py-2 focus:outline-none transition"
            :class="isPhraseValid
              ? 'border-emerald-500 bg-emerald-50/30 text-emerald-900 focus:ring-2 focus:ring-emerald-500/20'
              : 'border-slate-300 text-slate-900 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20'"
          />
        </div>

        <!-- Action Buttons -->
        <div class="mt-6 flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
          <button
            type="button"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50"
            :disabled="loading"
            @click="handleClose"
          >
            {{ $t('backups.actions.cancel') }}
          </button>
          <button
            type="submit"
            class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-rose-700 disabled:opacity-40 disabled:cursor-not-allowed"
            :disabled="!canSubmit"
          >
            <svg v-if="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>{{ $t('backups.restore_modal.button') }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
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

const emit = defineEmits(['close', 'confirm'])

const password = ref('')
const typedPhrase = ref('')

const isPhraseValid = computed(() => {
  return typedPhrase.value.trim() === 'RESTORE'
})

const canSubmit = computed(() => {
  return isPhraseValid.value && password.value.length > 0 && !props.loading
})

function handleClose() {
  if (props.loading) return
  password.value = ''
  typedPhrase.value = ''
  emit('close')
}

function handleSubmit() {
  if (!canSubmit.value) return
  emit('confirm', {
    backupItem: props.backupItem,
    password: password.value,
    phrase: typedPhrase.value.trim(),
  })
}
</script>
