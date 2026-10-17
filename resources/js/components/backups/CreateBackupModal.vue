<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs transition-opacity"
    @click.self="$emit('close')"
  >
    <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl transition-all">
      <!-- Modal Header -->
      <div class="mb-5 flex items-start justify-between border-b border-slate-100 pb-4">
        <div>
          <h3 class="text-lg font-bold text-slate-900">
            {{ $t('backups.create_modal.title') }}
          </h3>
          <p class="mt-0.5 text-xs text-slate-500">
            {{ $t('backups.create_modal.subtitle') }}
          </p>
        </div>
        <button
          type="button"
          class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
          :disabled="loading"
          @click="$emit('close')"
        >
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Scope Selection Form -->
      <form @submit.prevent="handleSubmit">
        <div class="space-y-3">
          <!-- Option 1: Full -->
          <label
            class="flex items-start gap-3 rounded-xl border p-4 cursor-pointer transition"
            :class="selectedType === 'full'
              ? 'border-teal-500 bg-teal-50/40 ring-1 ring-teal-500'
              : 'border-slate-200 hover:bg-slate-50'"
          >
            <input
              v-model="selectedType"
              type="radio"
              value="full"
              name="backup_type"
              class="mt-1 text-teal-600 focus:ring-teal-500"
            />
            <div class="flex-1">
              <p class="text-sm font-bold text-slate-900">
                {{ $t('backups.create_modal.full_title') }}
              </p>
              <p class="mt-0.5 text-xs text-slate-500 leading-relaxed">
                {{ $t('backups.create_modal.full_desc') }}
              </p>
            </div>
          </label>

          <!-- Option 2: Database Only -->
          <label
            class="flex items-start gap-3 rounded-xl border p-4 cursor-pointer transition"
            :class="selectedType === 'db'
              ? 'border-teal-500 bg-teal-50/40 ring-1 ring-teal-500'
              : 'border-slate-200 hover:bg-slate-50'"
          >
            <input
              v-model="selectedType"
              type="radio"
              value="db"
              name="backup_type"
              class="mt-1 text-teal-600 focus:ring-teal-500"
            />
            <div class="flex-1">
              <p class="text-sm font-bold text-slate-900">
                {{ $t('backups.create_modal.db_title') }}
              </p>
              <p class="mt-0.5 text-xs text-slate-500 leading-relaxed">
                {{ $t('backups.create_modal.db_desc') }}
              </p>
            </div>
          </label>

          <!-- Option 3: Files Only -->
          <label
            class="flex items-start gap-3 rounded-xl border p-4 cursor-pointer transition"
            :class="selectedType === 'files'
              ? 'border-teal-500 bg-teal-50/40 ring-1 ring-teal-500'
              : 'border-slate-200 hover:bg-slate-50'"
          >
            <input
              v-model="selectedType"
              type="radio"
              value="files"
              name="backup_type"
              class="mt-1 text-teal-600 focus:ring-teal-500"
            />
            <div class="flex-1">
              <p class="text-sm font-bold text-slate-900">
                {{ $t('backups.create_modal.files_title') }}
              </p>
              <p class="mt-0.5 text-xs text-slate-500 leading-relaxed">
                {{ $t('backups.create_modal.files_desc') }}
              </p>
            </div>
          </label>
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
            type="submit"
            class="inline-flex items-center gap-1.5 rounded-xl bg-teal-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-teal-700 disabled:opacity-50"
            :disabled="loading"
          >
            <svg v-if="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>{{ $t('backups.create_modal.start_button') }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  disks: {
    type: Array,
    default: () => ['backups'],
  },
  selectedDisk: {
    type: String,
    default: 'backups',
  },
})

const emit = defineEmits(['close', 'submit', 'confirm'])

const selectedType = ref('full')
const activeDisk = ref(props.selectedDisk || (props.disks && props.disks[0]) || 'backups')

watch(() => props.selectedDisk, (val) => {
  if (val) activeDisk.value = val
})

function handleSubmit() {
  if (props.loading) return
  const payload = {
    type: selectedType.value,
    disk: activeDisk.value || 'backups',
  }
  emit('submit', payload)
}
</script>
