<template>
  <section class="space-y-5 sm:space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">{{ $t('departments.members') }}</h1>
      <button
        class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
        @click="openCreateModal"
      >
        <svg class="mr-2 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Member
      </button>
    </div>

    <!-- Skeleton Loader -->
    <div v-if="loading" class="animate-pulse space-y-4">
      <div class="h-12 w-full rounded-2xl bg-slate-200"></div>
      <div class="h-16 w-full rounded-2xl bg-slate-200"></div>
      <div class="h-16 w-full rounded-2xl bg-slate-200"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="members.length === 0" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 py-16 px-4 text-center">
      <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500 mb-4">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
      </div>
      <h3 class="text-lg font-semibold text-slate-900">{{ $t('messages.no_members_found') }}</h3>
      <p class="mt-1 max-w-sm text-sm text-slate-500">Get started by adding a new department member.</p>
      <button @click="openCreateModal" class="mt-5 rounded-lg bg-white border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
        Add new member
      </button>
    </div>

    <div v-else class="space-y-4">
      <!-- Search Bar -->
      <div class="flex items-center justify-between">
        <div class="relative w-full sm:w-96">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
            </svg>
          </div>
          <input
            v-model="searchQuery"
            type="text"
            class="block w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm placeholder-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 transition"
            placeholder="Search by name or email..."
          />
        </div>
      </div>

      <!-- Data Table -->
      <div class="hidden overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
        <table class="min-w-full text-left text-sm">
          <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
            <tr>
              <th class="px-6 py-4 font-semibold">{{ $t('common.full_name') }}</th>
              <th class="px-6 py-4 font-semibold">{{ $t('auth.email') }}</th>
              <th class="px-6 py-4 font-semibold">{{ $t('common.status') }}</th>
              <th class="px-6 py-4 font-semibold text-right">{{ $t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="paginatedMembers.length === 0">
              <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                No members match your search query.
              </td>
            </tr>
            <tr v-for="member in paginatedMembers" :key="member.id" class="transition hover:bg-slate-50">
              <td class="px-6 py-4 font-medium text-slate-900">{{ member.name }}</td>
              <td class="px-6 py-4 text-slate-500">{{ member.email }}</td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset" :class="member.status === 'Active' ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-slate-50 text-slate-600 ring-slate-500/10'">
                  {{ member.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-3">
                  <button class="text-slate-400 hover:text-teal-600 transition" @click="openEditModal(member)">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                  </button>
                  <button class="text-slate-400 hover:text-red-600 transition" @click="deleteMember(member)">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        
        <!-- Pagination -->
        <div v-if="totalPages > 1" class="flex items-center justify-between border-t border-slate-200 bg-white px-6 py-3">
          <div class="text-sm text-slate-500">
            Showing <span class="font-medium text-slate-900">{{ (currentPage - 1) * itemsPerPage + 1 }}</span> to
            <span class="font-medium text-slate-900">{{ Math.min(currentPage * itemsPerPage, filteredMembers.length) }}</span> of
            <span class="font-medium text-slate-900">{{ filteredMembers.length }}</span> results
          </div>
          <div class="flex items-center gap-2">
            <button
              class="rounded-md border border-slate-300 px-3 py-1 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="currentPage === 1"
              @click="currentPage--"
            >
              Previous
            </button>
            <button
              class="rounded-md border border-slate-300 px-3 py-1 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="currentPage === totalPages"
              @click="currentPage++"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Modal for Creating/Editing -->
  <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 px-4 py-8" @click.self="closeModal">
    <div class="w-full max-w-xl rounded-2xl bg-white p-5 shadow-xl sm:p-6">
      <div class="mb-5 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-900 sm:text-xl">
          {{ isEditing ? 'Edit member' : 'Add member' }}
        </h2>
        <button class="rounded-md p-1 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400" @click="closeModal">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form class="space-y-4" @submit.prevent="submitForm">
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t('common.full_name') }}</label>
          <input
            v-model="form.full_name"
            type="text"
            class="w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm outline-none transition focus:ring-2"
            :class="formErrors.full_name ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-teal-500 focus:ring-teal-500/20'"
            required
          />
          <p v-if="formErrors.full_name" class="mt-1 text-xs text-red-600">{{ formErrors.full_name[0] }}</p>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t('auth.email') }}</label>
          <input
            v-model="form.email"
            type="email"
            class="w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm outline-none transition focus:ring-2"
            :class="formErrors.email ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-teal-500 focus:ring-teal-500/20'"
            required
          />
          <p v-if="formErrors.email" class="mt-1 text-xs text-red-600">{{ formErrors.email[0] }}</p>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t('common.status') }}</label>
          <select
            v-model="form.is_active"
            class="w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm outline-none transition focus:ring-2"
            :class="formErrors.is_active ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-teal-500 focus:ring-teal-500/20'"
          >

            <option :value="false">{{ $t('common.disabled') }}</option>
            <option :value="true">{{ $t('common.active') }}</option>
            
          </select>
          <p v-if="formErrors.is_active" class="mt-1 text-xs text-red-600">{{ formErrors.is_active[0] }}</p>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">
            Password {{ isEditing ? '(leave empty to keep current)' : '' }}
          </label>
          <input
            v-model="form.password"
            type="password"
            class="w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm outline-none transition focus:ring-2"
            :class="formErrors.password ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-teal-500 focus:ring-teal-500/20'"
            :required="!isEditing"
            minlength="8"
          />
          <p v-if="formErrors.password" class="mt-1 text-xs text-red-600">{{ formErrors.password[0] }}</p>
        </div>

        <p v-if="formErrors.general" class="text-sm text-red-600">{{ formErrors.general }}</p>

        <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
          <button type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400" @click="closeModal">{{ $t('common.cancel') }}</button>
          <button v-if="!isEditing" type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400" @click="clearForm">{{ $t('common.clear') }}</button>
          <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 disabled:opacity-50" :disabled="submitting">
            {{ submitting ? 'Saving...' : isEditing ? 'Save changes' : 'Create member' }}
          </button>
        </div>
      </form>
    </div>
  </div>

  <DeleteConfirmationModal
    :is-open="isDeleteModalOpen"
    :is-deleting="isDeleting"
    title="Delete Member"
    :message="`Are you sure you want to delete ${memberToDelete?.name}? This action cannot be undone.`"
    @close="closeDeleteModal"
    @confirm="confirmDelete"
  />
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useToast } from 'vue-toastification'
import DeleteConfirmationModal from '../common/DeleteConfirmationModal.vue'

const toast = useToast()

const members = ref([])
const loading = ref(true)

// Searching & Pagination
const searchQuery = ref('')
const currentPage = ref(1)
const itemsPerPage = 10

const filteredMembers = computed(() => {
  if (!searchQuery.value) return members.value
  const query = searchQuery.value.toLowerCase()
  return members.value.filter(m => 
    m.name.toLowerCase().includes(query) ||
    m.email.toLowerCase().includes(query)
  )
})

const totalPages = computed(() => Math.ceil(filteredMembers.value.length / itemsPerPage) || 1)

const paginatedMembers = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filteredMembers.value.slice(start, end)
})

watch(searchQuery, () => {
  currentPage.value = 1
})

const isModalOpen = ref(false)
const isEditing = ref(false)
const editingId = ref(null)
const submitting = ref(false)
const formErrors = ref({})

const isDeleteModalOpen = ref(false)
const isDeleting = ref(false)
const memberToDelete = ref(null)

const form = reactive({
  full_name: '',
  email: '',
  is_active: true,
  password: '',
})

const getCsrfToken = () => {
  const tokenTag = document.querySelector('meta[name="csrf-token"]')
  return tokenTag ? tokenTag.getAttribute('content') : ''
}

const clearForm = () => {
  form.full_name = ''
  form.email = ''
  form.is_active = true
  form.password = ''
}

const openCreateModal = () => {
  clearForm()
  formErrors.value = {}
  isEditing.value = false
  editingId.value = null
  isModalOpen.value = true
}

const openEditModal = (member) => {
  formErrors.value = {}
  isEditing.value = true
  editingId.value = member.id
  form.full_name = member.name
  form.email = member.email
  form.is_active = member.isActive
  form.password = ''
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
}

const parseErrors = (payload) => {
  if (!payload || !payload.errors) {
    return { general: payload?.message || 'Unable to save member.' }
  }
  return payload.errors
}

const loadMembers = async () => {
  loading.value = true
  try {
    const response = await fetch('/department/members', {
      headers: { Accept: 'application/json' },
    })
    if (!response.ok) throw new Error('Failed to load members.')
    const data = await response.json()
    members.value = data.members ?? []
  } catch (error) {
    toast.error(error.message || 'Failed to load members.')
  } finally {
    loading.value = false
  }
}

const submitForm = async () => {
  submitting.value = true
  formErrors.value = {}

  try {
    const payload = { ...form }
    if (isEditing.value && !payload.password) {
      delete payload.password
    }

    const url = isEditing.value ? `/department/members/${editingId.value}` : '/department/members'
    const method = isEditing.value ? 'PUT' : 'POST'

    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify(payload),
    })

    const data = await response.json()

    if (!response.ok) {
      formErrors.value = parseErrors(data)
      throw new Error('Validation failed')
    }

    await loadMembers()
    closeModal()
    toast.success(isEditing.value ? 'Member updated successfully.' : 'Member created successfully.')
  } catch (error) {
    if (Object.keys(formErrors.value).length === 0) {
      toast.error('An unexpected error occurred.')
    }
  } finally {
    submitting.value = false
  }
}

const deleteMember = (member) => {
  memberToDelete.value = member
  isDeleteModalOpen.value = true
}

const closeDeleteModal = () => {
  isDeleteModalOpen.value = false
  memberToDelete.value = null
}

const confirmDelete = async () => {
  if (!memberToDelete.value) return
  
  isDeleting.value = true
  try {
    const response = await fetch(`/department/members/${memberToDelete.value.id}`, {
      method: 'DELETE',
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    })

    if (!response.ok) throw new Error('Unable to delete member.')

    members.value = members.value.filter((item) => item.id !== memberToDelete.value.id)
    
    if (paginatedMembers.value.length === 0 && currentPage.value > 1) {
      currentPage.value--
    }

    toast.success('Member deleted successfully.')
    closeDeleteModal()
  } catch (error) {
    toast.error(error.message || 'Unable to delete member.')
  } finally {
    isDeleting.value = false
  }
}

onMounted(() => {
  loadMembers()
})
</script>
