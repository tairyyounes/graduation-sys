<template>
  <section class="space-y-5 sm:space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">{{ $t('deptnav.committees') }}</h1>
      <button
        @click="openCreateModal"
        class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500"
      >
        <svg class="me-2 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        {{ $t('dept.committees.add') }}
      </button>
    </div>

    <!-- Skeleton Loader -->
    <div v-if="loading" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <div v-for="i in 3" :key="i" class="h-48 rounded-2xl bg-slate-200 animate-pulse"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="committees.length === 0" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 py-16 px-4 text-center">
      <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500 mb-4">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      </div>
      <h3 class="text-lg font-semibold text-slate-900">{{ $t('dept.committees.none_found') }}</h3>
      <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $t('dept.committees.none_desc') }}</p>
      <button @click="openCreateModal" class="mt-5 rounded-lg bg-white border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
        {{ $t('dept.committees.add_new') }}
      </button>
    </div>

    <!-- Committee Cards -->
    <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <article
        v-for="committee in committees"
        :key="committee.id"
        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md flex flex-col h-full"
      >
        <div class="flex items-start justify-between">
          <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-teal-100 text-teal-700">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </div>
          <span class="text-xs font-medium text-slate-500 bg-slate-100 px-2 py-1 rounded-md">
            {{ formatDate(committee.created_at) }}
          </span>
        </div>
        
        <h3 class="mt-4 text-xl font-semibold text-slate-900">{{ committee.name }}</h3>
        
        <div class="mt-4 flex-grow">
          <p class="text-sm font-medium text-slate-500 mb-2">
            {{ $t('dept.committees.members_count', { count: committee.users ? committee.users.length : 0 }) }}
          </p>
          <div v-if="committee.users && committee.users.length > 0" class="flex -space-x-2 overflow-hidden">
            <div 
              v-for="(user, index) in committee.users.slice(0, 5)" 
              :key="user.id" 
              class="inline-block h-8 w-8 rounded-full ring-2 ring-white bg-teal-600 flex items-center justify-center text-xs font-medium text-white"
              :title="user.full_name"
            >
              {{ getInitials(user.full_name) }}
            </div>
            <div v-if="committee.users.length > 5" class="inline-block h-8 w-8 rounded-full ring-2 ring-white bg-slate-100 flex items-center justify-center text-xs font-medium text-slate-600">
              +{{ committee.users.length - 5 }}
            </div>
          </div>
          <p v-else class="text-sm text-slate-400 italic">{{ $t('dept.committees.no_members') }}</p>
        </div>

        <div class="mt-6 flex gap-2 pt-4 border-t border-slate-100">
          <button 
            @click="openEditModal(committee)"
            class="flex-1 rounded-lg border border-slate-300 bg-white py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400"
          >
            {{ $t('common.edit') }}
          </button>
          <button
            @click="deleteCommittee(committee)"
            class="flex-1 rounded-lg border border-red-200 bg-red-50 py-2 text-sm font-medium text-red-600 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-300"
          >
            {{ $t('common.delete') }}
          </button>
        </div>
      </article>
    </div>
  </section>

  <!-- Create/Edit Modal -->
  <DepartmentCommitteeModal
    :is-open="isModalOpen"
    :is-editing="isEditing"
    :submitting="submittingForm"
    :form="committeeForm"
    :errors="formErrors"
    :available-members="availableMembers"
    @close="closeModal"
    @submit="submitForm"
  />

  <!-- Delete Confirmation Modal -->
  <DeleteConfirmationModal
    :is-open="isDeleteModalOpen"
    :is-deleting="isDeleting"
    :title="$t('dept.committees.delete_title')"
    :message="$t('dept.committees.delete_message', { name: committeeToDelete?.name })"
    @close="closeDeleteModal"
    @confirm="confirmDelete"
  />
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import DepartmentCommitteeModal from './DepartmentCommitteeModal.vue'
import DeleteConfirmationModal from '../common/DeleteConfirmationModal.vue'
import { usePersistedForm } from '../../composables/usePersistedForm'

const toast = useToast()
const { t, locale } = useI18n()

const committees = ref([])
const availableMembers = ref([])
const loading = ref(true)

// Modal State
const isModalOpen = ref(false)
const isEditing = ref(false)
const editingId = ref(null)
const submittingForm = ref(false)
const formErrors = ref({})

const isDeleteModalOpen = ref(false)
const isDeleting = ref(false)
const committeeToDelete = ref(null)

const committeeForm = reactive({
  name: '',
  members: [],
})

const { clearPersistedForm } = usePersistedForm('dept_committee_form', committeeForm)

const getCsrfToken = () => {
  const tokenTag = document.querySelector('meta[name="csrf-token"]')
  return tokenTag ? tokenTag.getAttribute('content') : ''
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  const loc = locale.value === 'ar' ? 'ar' : 'en-US'
  return new Intl.DateTimeFormat(loc, { month: 'short', day: 'numeric', year: 'numeric' }).format(date)
}

const getInitials = (name) => {
  if (!name) return 'U'
  return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
}

const clearForm = () => {
  committeeForm.name = ''
  committeeForm.members = []
  clearPersistedForm()
}

const openCreateModal = () => {
  if (!localStorage.getItem('dept_committee_form')) {
    clearForm()
  }
  formErrors.value = {}
  isEditing.value = false
  editingId.value = null
  isModalOpen.value = true
}

const openEditModal = (committee) => {
  formErrors.value = {}
  isEditing.value = true
  editingId.value = committee.id
  committeeForm.name = committee.name
  committeeForm.members = committee.users ? committee.users.map(u => u.id) : []
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
}

const parseErrors = (payload) => {
  if (!payload || !payload.errors) {
    return { general: payload?.message || t('dept.committees.toast.save_failed') }
  }
  return payload.errors
}

const fetchCommittees = async () => {
  loading.value = true
  try {
    const response = await fetch('/department/committees', {
      headers: {
        Accept: 'application/json',
      },
    })

    if (!response.ok) {
      throw new Error(t('dept.committees.toast.load_failed'))
    }

    const data = await response.json()
    committees.value = data.committees ?? []
    availableMembers.value = data.available_members ?? []
  } catch (error) {
    toast.error(error.message || t('dept.committees.toast.load_failed'))
  } finally {
    loading.value = false
  }
}

const submitForm = async () => {
  submittingForm.value = true
  formErrors.value = {}

  try {
    const payload = {
      name: committeeForm.name,
      members: committeeForm.members,
    }

    const url = isEditing.value ? `/department/committees/${editingId.value}` : '/department/committees'
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

    await fetchCommittees()
    closeModal()
    clearPersistedForm()
    toast.success(isEditing.value ? t('dept.committees.toast.updated') : t('dept.committees.toast.created'))
  } catch (error) {
    if (Object.keys(formErrors.value).length === 0) {
      toast.error(t('dept.committees.toast.unexpected'))
    }
  } finally {
    submittingForm.value = false
  }
}

const deleteCommittee = (committee) => {
  committeeToDelete.value = committee
  isDeleteModalOpen.value = true
}

const closeDeleteModal = () => {
  isDeleteModalOpen.value = false
  committeeToDelete.value = null
}

const confirmDelete = async () => {
  if (!committeeToDelete.value) return
  
  isDeleting.value = true
  try {
    const response = await fetch(`/department/committees/${committeeToDelete.value.id}`, {
      method: 'DELETE',
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    })

    if (!response.ok) {
      throw new Error(t('dept.committees.toast.delete_failed'))
    }

    committees.value = committees.value.filter((item) => item.id !== committeeToDelete.value.id)
    toast.success(t('dept.committees.toast.deleted'))
    closeDeleteModal()
  } catch (error) {
    toast.error(error.message || t('dept.committees.toast.delete_failed'))
  } finally {
    isDeleting.value = false
  }
}

onMounted(() => {
  fetchCommittees()
})
</script>
