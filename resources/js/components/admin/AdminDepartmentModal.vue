<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 px-4 py-8 backdrop-blur-sm transition-opacity"
    @click.self="$emit('close')"
  >
    <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl sm:p-6 transform transition-all">
      <div class="mb-5 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-900 sm:text-xl">
          {{ isEditing ? $t('admin.dept_modal.edit') : $t('admin.dept_modal.add') }}
        </h2>
        <button
          class="rounded-md p-1 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400"
          @click="$emit('close')"
        >
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form class="space-y-4" @submit.prevent="$emit('submit')">
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t('admin.dept_modal.name') }}</label>
          <input
            v-model="form.department_name"
            type="text"
            :placeholder="$t('admin.dept_modal.name_ph')"
            class="w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm outline-none transition focus:ring-2"
            :class="errors.department_name ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-teal-500 focus:ring-teal-500/20'"
            required
            autofocus
          />
          <p v-if="errors.department_name" class="mt-1 text-xs text-red-600">{{ errors.department_name[0] }}</p>
        </div>

        <p v-if="errors.general" class="text-sm text-red-600">{{ errors.general }}</p>

        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
          <button
            type="button"
            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400"
            @click="$emit('close')"
          >
            {{ $t('common.cancel') }}
          </button>
          <button
            type="submit"
            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 disabled:opacity-50"
            :disabled="submitting"
          >
            {{ submitting ? $t('common.saving') : isEditing ? $t('dept.student_modal.save_changes') : $t('admin.dept_modal.create') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
defineProps({
  isOpen: {
    type: Boolean,
    required: true,
  },
  isEditing: {
    type: Boolean,
    required: true,
  },
  submitting: {
    type: Boolean,
    required: true,
  },
  form: {
    type: Object,
    required: true,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
})

defineEmits(['close', 'submit'])
</script>
