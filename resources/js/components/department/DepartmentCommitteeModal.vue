<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 px-4 py-8" @click.self="$emit('close')">
    <div class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-xl sm:p-6">
      <div class="mb-5 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-900 sm:text-xl">
          {{ isEditing ? 'Edit review committee' : 'Add review committee' }}
        </h2>
        <button class="rounded-md p-1 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400" @click="$emit('close')">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form class="space-y-5" @submit.prevent="$emit('submit')">
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t('decisions.committee_name') }}</label>
          <input
            v-model="form.name"
            type="text"
            class="w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm outline-none transition focus:ring-2"
            :class="errors.name ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-teal-500 focus:ring-teal-500/20'"
            placeholder="e.g. 2026 Fall Review Committee"
            required
          />
          <p v-if="errors.name" class="mt-1 text-xs text-red-600">{{ errors.name[0] }}</p>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t('common.assign_members') }}</label>
          
          <div class="max-h-60 overflow-y-auto rounded-lg border border-slate-200 bg-slate-50 p-2">
            <div v-if="availableMembers.length === 0" class="p-3 text-center text-sm text-slate-500">
              No department members available to assign.
            </div>
            
            <div v-for="member in availableMembers" :key="member.id" class="flex items-center p-2 rounded-md hover:bg-slate-100 transition">
              <input 
                type="checkbox" 
                :id="`member-${member.id}`" 
                :value="member.id" 
                v-model="form.members"
                class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
              />
              <label :for="`member-${member.id}`" class="ml-3 flex flex-col cursor-pointer flex-grow">
                <span class="text-sm font-medium text-slate-900">{{ member.full_name }}</span>
                <span class="text-xs text-slate-500">{{ member.email }}</span>
              </label>
            </div>
          </div>
          <p v-if="errors.members" class="mt-1 text-xs text-red-600">{{ errors.members[0] }}</p>
        </div>

        <p v-if="errors.general" class="text-sm text-red-600">{{ errors.general }}</p>

        <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
          <button type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400" @click="$emit('close')">{{ $t('common.cancel') }}</button>
          <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 disabled:opacity-50" :disabled="submitting">
            {{ submitting ? 'Saving...' : isEditing ? 'Save changes' : 'Create committee' }}
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
    default: false,
  },
  submitting: {
    type: Boolean,
    default: false,
  },
  form: {
    type: Object,
    required: true,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
  availableMembers: {
    type: Array,
    default: () => [],
  }
})

defineEmits(['close', 'submit'])
</script>
