<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 px-4 py-8"
    @click.self="$emit('close')"
  >
    <div class="w-full max-w-xl rounded-2xl bg-white p-5 shadow-xl sm:p-6">
      <div class="mb-5 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-900 sm:text-xl">
          {{ isEditing ? $t('admin.user_modal.edit') : $t('admin.user_modal.add') }}
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
          <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t('dept.students.full_name') }}</label>
          <input
            v-model="form.full_name"
            type="text"
            maxlength="50"
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
            <span>{{ $t('dept.students.email') }}</span>
            <span v-if="isStudent" class="text-xs text-slate-400 font-normal">({{ $t('dept.student_modal.email_auto') }})</span>
          </label>
          <input
            v-if="isStudent"
            :value="form.email"
            type="email"
            readonly
            tabindex="-1"
            dir="ltr"
            class="w-full cursor-not-allowed rounded-lg border bg-slate-50 px-3 py-2.5 text-sm text-slate-600 shadow-sm outline-none"
            :class="fieldError('email') ? 'border-red-300' : 'border-slate-200'"
          />
          <input
            v-else
            v-model="form.email"
            type="email"
            class="w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm outline-none transition focus:ring-2"
            :class="fieldError('email') ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-teal-500 focus:ring-teal-500/20'"
            required
            @input="touch('email')"
            @blur="touch('email')"
          />
          <p v-if="fieldError('email')" class="mt-1 text-xs text-red-600">{{ fieldError('email') }}</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t('student.team.role') }}</label>
            <select
              v-model="form.role"
              class="w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm outline-none transition focus:ring-2"
              :class="errors.role ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-teal-500 focus:ring-teal-500/20'"
            >
              <option value="admin">{{ $t('roles.admin') }}</option>
              <option value="student">{{ $t('roles.student') }}</option>
              <option value="department_member">{{ $t('admin.user_modal.role_dept_member') }}</option>
              <option value="department_head">{{ $t('roles.department_head') }}</option>
            </select>
            <p v-if="errors.role" class="mt-1 text-xs text-red-600">{{ errors.role[0] }}</p>
          </div>

          <div v-if="form.role === 'student'">
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

          <div v-if="form.role !== 'admin'" :class="form.role === 'student' ? '' : 'sm:col-span-2'">
            <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t('fields.department') }}</label>
            <select
              v-model="form.department_id"
              class="w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm outline-none transition focus:ring-2"
              :class="errors.department_id ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-teal-500 focus:ring-teal-500/20'"
              :required="form.role !== 'admin'"
            >
              <option :value="null" disabled>{{ $t('admin.user_modal.no_department') }}</option>
              <option v-for="department in departments" :key="department.department_id" :value="department.department_id">
                {{ department.department_name }}
              </option>
            </select>
            <p v-if="errors.department_id" class="mt-1 text-xs text-red-600">{{ errors.department_id[0] }}</p>
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

        <p v-if="isStudent" class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">
          {{ isEditing ? $t('dept.student_modal.password_locked') : $t('dept.student_modal.password_default') }}
        </p>

        <div v-else>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">
            {{ $t('dept.members.password') }} {{ isEditing ? $t('dept.members.password_hint') : '' }}
          </label>
          <input
            v-model="form.password"
            type="password"
            class="w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm outline-none transition focus:ring-2"
            :class="errors.password ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-teal-500 focus:ring-teal-500/20'"
            :required="!isEditing"
            minlength="8"
            maxlength="32"
          />
          <p v-if="errors.password" class="mt-1 text-xs text-red-600">{{ errors.password[0] }}</p>
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
            class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 disabled:opacity-50"
            :disabled="submitting || hasVisibleErrors"
          >
            {{ submitting ? $t('common.saving') : isEditing ? $t('dept.student_modal.save_changes') : $t('admin.user_modal.create') }}
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
  departments: {
    type: Array,
    required: true,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['close', 'submit', 'clear'])

const { t } = useI18n()

const isStudent = computed(() => props.form.role === 'student')
const fields = computed(() => (isStudent.value ? ['student_number', 'full_name', 'email'] : ['full_name', 'email']))

const touched = ref({})
// Server errors are kept per field until that field is edited again.
const serverErrors = ref({})

const liveErrors = computed(() => validateStudent(props.form, t, fields.value))

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

const hasVisibleErrors = computed(() => fields.value.some((field) => fieldError(field)))

// A student's email is never typed: it always follows the student number.
const syncStudentEmail = () => {
  if (isStudent.value) props.form.email = studentEmailFor(props.form.student_number)
}

watch(() => [props.form.student_number, props.form.role], syncStudentEmail, { immediate: true })

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
      syncStudentEmail()
    }
  },
)

const onSubmit = () => {
  fields.value.forEach((field) => (touched.value[field] = true))
  if (Object.keys(liveErrors.value).length > 0) return
  emit('submit')
}

const onClear = () => {
  touched.value = {}
  emit('clear')
}
</script>
