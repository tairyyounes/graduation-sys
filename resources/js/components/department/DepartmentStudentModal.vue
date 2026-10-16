<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 px-4 py-8"
    @click.self="$emit('close')"
  >
    <div class="w-full max-w-xl rounded-2xl bg-white p-5 shadow-xl sm:p-6">
      <div class="mb-5 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-900 sm:text-xl">
          {{ isEditing ? $t('dept.student_modal.edit') : $t('dept.students.add_student') }}
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

      <form class="space-y-4" novalidate @submit.prevent="onSubmit">
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t('dept.students.student_number') }}</label>
          <input
            v-model="form.student_number"
            type="text"
            inputmode="numeric"
            maxlength="6"
            class="w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm outline-none transition focus:ring-2"
            :class="fieldError('student_number') ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-teal-500 focus:ring-teal-500/20'"
            required
            @input="touch('student_number')"
            @blur="touch('student_number')"
          />
          <p v-if="fieldError('student_number')" class="mt-1 text-xs text-red-600">{{ fieldError('student_number') }}</p>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t('dept.students.full_name') }}</label>
          <input
            v-model="form.full_name"
            type="text"
            class="w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm outline-none transition focus:ring-2"
            :class="fieldError('full_name') ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-teal-500 focus:ring-teal-500/20'"
            required
            @input="touch('full_name')"
            @blur="touch('full_name')"
          />
          <p v-if="fieldError('full_name')" class="mt-1 text-xs text-red-600">{{ fieldError('full_name') }}</p>
        </div>

        <div>
          <label class="mb-1.5 flex flex-wrap items-center justify-between text-sm font-medium text-slate-700">
            <span>{{ $t('dept.student_modal.official_email') }}</span>
            <span class="text-xs text-slate-400 font-normal">({{ $t('dept.student_modal.email_auto') }})</span>
          </label>
          <input
            :value="form.email"
            type="email"
            readonly
            tabindex="-1"
            dir="ltr"
            class="w-full cursor-not-allowed rounded-lg border bg-slate-50 px-3 py-2.5 text-sm text-slate-600 shadow-sm outline-none"
            :class="fieldError('email') ? 'border-red-300' : 'border-slate-200'"
          />
          <p v-if="fieldError('email')" class="mt-1 text-xs text-red-600">{{ fieldError('email') }}</p>
        </div>

        <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">
          {{ isEditing ? $t('dept.student_modal.password_locked') : $t('dept.student_modal.password_default') }}
        </p>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t('dept.students.semester') }}</label>
            <input
              v-model="form.semester"
              type="number"
              min="1"
              max="8"
              class="w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm outline-none transition focus:ring-2"
              :class="fieldError('semester') ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-teal-500 focus:ring-teal-500/20'"
              required
              @input="touch('semester')"
            />
            <p v-if="fieldError('semester')" class="mt-1 text-xs text-red-600">{{ fieldError('semester') }}</p>
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t('fields.status') }}</label>
            <select
              v-model="form.is_active"
              class="w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm outline-none transition focus:ring-2"
              :class="errors.is_active ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-teal-500 focus:ring-teal-500/20'"
            >
              <option :value="true">{{ $t('status.active') }}</option>
              <option :value="false">{{ $t('dept.students.disabled') }}</option>
            </select>
            <p v-if="errors.is_active" class="mt-1 text-xs text-red-600">{{ errors.is_active[0] }}</p>
          </div>
        </div>

        <p v-if="errors.general" class="text-sm text-red-600">{{ errors.general }}</p>

        <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
          <button
            type="button"
            class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400"
            @click="$emit('close')"
          >
            {{ $t('common.cancel') }}
          </button>
          <button
            v-if="!isEditing"
            type="button"
            class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400"
            @click="onClear"
          >
            {{ $t('dept.student_modal.clear') }}
          </button>
          <button
            type="submit"
            class="rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 disabled:opacity-50"
            :disabled="submitting || hasVisibleErrors"
          >
            {{ submitting ? $t('dept.students.saving') : isEditing ? $t('dept.student_modal.save_changes') : $t('dept.students.add_student') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { studentEmailFor, validateStudent } from '../../validation/studentValidation'

const props = defineProps({
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

const emit = defineEmits(['close', 'submit', 'clear'])

const { t } = useI18n()

const FIELDS = ['student_number', 'full_name', 'email', 'semester']
const touched = ref({})
// Server errors are kept per field until that field is edited again.
const serverErrors = ref({})

const liveErrors = computed(() => validateStudent(props.form, t))
const hasErrors = computed(() => Object.keys(liveErrors.value).length > 0)

const touch = (field) => {
  touched.value[field] = true
  delete serverErrors.value[field]
  if (field === 'student_number') {
    touched.value.email = true
    delete serverErrors.value.email
  }
}

const fieldError = (field) => {
  if (touched.value[field] && liveErrors.value[field]) return liveErrors.value[field][0]
  return serverErrors.value[field]?.[0] ?? null
}

const hasVisibleErrors = computed(() => FIELDS.some((field) => fieldError(field)))

// The email is never typed: it always follows the student number.
watch(
  () => props.form.student_number,
  (number) => {
    props.form.email = studentEmailFor(number)
  },
  { immediate: true },
)

watch(
  () => props.errors,
  (errors) => {
    serverErrors.value = { ...errors }
  },
  { immediate: true },
)

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      touched.value = {}
      props.form.email = studentEmailFor(props.form.student_number)
    }
  },
)

const onSubmit = () => {
  FIELDS.forEach((field) => (touched.value[field] = true))
  if (hasErrors.value) return
  emit('submit')
}

const onClear = () => {
  touched.value = {}
  emit('clear')
}
</script>
