<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 px-4 py-8"
    @click.self="$emit('close')"
  >
    <div class="w-full max-w-xl rounded-2xl bg-white p-5 shadow-xl sm:p-6">
      <div class="mb-5 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-900 sm:text-xl">
          {{ isEditing ? 'Edit user' : 'Add user' }}
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
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Full name</label>
          <input
            v-model="form.full_name"
            type="text"
            class="w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm outline-none transition focus:ring-2"
            :class="errors.full_name ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/20'"
            required
          />
          <p v-if="errors.full_name" class="mt-1 text-xs text-red-600">{{ errors.full_name[0] }}</p>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
          <input
            v-model="form.email"
            type="email"
            class="w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm outline-none transition focus:ring-2"
            :class="errors.email ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/20'"
            required
          />
          <p v-if="errors.email" class="mt-1 text-xs text-red-600">{{ errors.email[0] }}</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Role</label>
            <select
              v-model="form.role"
              class="w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm outline-none transition focus:ring-2"
              :class="errors.role ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/20'"
            >
              <option value="admin">Admin</option>
              <option value="student">Student</option>
              <option value="department_member">Department member</option>
            </select>
            <p v-if="errors.role" class="mt-1 text-xs text-red-600">{{ errors.role[0] }}</p>
          </div>
          <div v-if="form.role === 'student'">
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Student Number</label>
            <input
              v-model="form.student_number"
              type="text"
              class="w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm outline-none transition focus:ring-2"
              :class="errors.student_number ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/20'"
              required
            />
            <p v-if="errors.student_number" class="mt-1 text-xs text-red-600">{{ errors.student_number[0] }}</p>
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
            <select
              v-model="form.is_active"
              class="w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm outline-none transition focus:ring-2"
              :class="errors.is_active ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/20'"
            >
              <option :value="true">Active</option>
              <option :value="false">Disabled</option>
            </select>
            <p v-if="errors.is_active" class="mt-1 text-xs text-red-600">{{ errors.is_active[0] }}</p>
          </div>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Department</label>
          <select
            v-model="form.department_id"
            :disabled="form.role === 'admin'"
            class="w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm outline-none transition focus:ring-2 disabled:cursor-not-allowed disabled:bg-slate-50"
            :class="errors.department_id ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/20'"
          >
            <option :value="null">No department</option>
            <option v-for="department in departments" :key="department.department_id" :value="department.department_id">
              {{ department.department_name }}
            </option>
          </select>
          <p v-if="errors.department_id" class="mt-1 text-xs text-red-600">{{ errors.department_id[0] }}</p>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">
            Password {{ isEditing ? '(leave empty to keep current)' : '' }}
          </label>
          <input
            v-model="form.password"
            type="password"
            class="w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm outline-none transition focus:ring-2"
            :class="errors.password ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/20'"
            :required="!isEditing"
            minlength="8"
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
            Cancel
          </button>
          <button
            type="submit"
            class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 disabled:opacity-50"
            :disabled="submitting"
          >
            {{ submitting ? 'Saving...' : isEditing ? 'Save changes' : 'Create user' }}
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
  departments: {
    type: Array,
    required: true,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
})

defineEmits(['close', 'submit'])
</script>
