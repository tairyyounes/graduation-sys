<template>
  <section class="space-y-5 sm:space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">{{ $t('departments.departments') }}</h1>
      <button 
        @click="openCreateModal"
        class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500"
      >
        + Add department
      </button>
    </div>

    <!-- Skeleton Loader -->
    <div v-if="loading" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <div v-for="i in 3" :key="i" class="h-48 rounded-2xl bg-slate-200 animate-pulse"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="departments.length === 0" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 py-16 px-4 text-center">
      <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500 mb-4">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
      </div>
      <h3 class="text-lg font-semibold text-slate-900">{{ $t('departments.nos_found') }}</h3>
      <p class="mt-1 max-w-sm text-sm text-slate-500">You haven't added any departments to the system yet.</p>
      <button @click="openCreateModal" class="mt-5 rounded-lg bg-white border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
        Add new department
      </button>
    </div>

    <!-- Department Cards -->
    <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <article
        v-for="dept in departments"
        :key="dept.id"
        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md flex flex-col h-full"
      >
        <div class="flex items-start justify-between">
          <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-cyan-100 text-cyan-700">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l8-4 6 3v15M9 9h.01M9 13h.01M9 17h.01M13 9h.01M13 13h.01M13 17h.01" />
            </svg>
          </div>
          <router-link
            :to="{ name: 'AdminDepartmentDetails', params: { id: dept.id } }"
            class="text-sm font-medium text-teal-600 hover:text-teal-800 flex items-center gap-1 group"
          >
            View details
            <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </router-link>
        </div>
        <h3 class="mt-4 text-xl font-semibold text-slate-900">{{ dept.name }}</h3>
        <div class="mt-4 grid grid-cols-3 gap-3 text-sm flex-grow">
          <p class="text-slate-500">
            Members
            <span class="mt-1 block text-2xl font-semibold text-slate-900">{{ dept.members }}</span>
          </p>
          <p class="text-slate-500">
            Students
            <span class="mt-1 block text-2xl font-semibold text-slate-900">{{ dept.students }}</span>
          </p>
          <p class="text-slate-500">
            Proposals
            <span class="mt-1 block text-2xl font-semibold text-slate-900">{{ dept.proposals }}</span>
          </p>
        </div>
        <div class="mt-6 flex gap-2 pt-4 border-t border-slate-100">
          <button 
            @click="openEditModal(dept)"
            class="flex-1 rounded-lg border border-slate-300 bg-white py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400"
          >
            Edit
          </button>
          <button 
            @click="deleteDepartment(dept)"
            class="flex-1 rounded-lg border border-red-200 bg-red-50 py-2 text-sm font-medium text-red-600 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-300"
          >
            Delete
          </button>
        </div>
      </article>
    </div>
  </section>

  <!-- Create/Edit Modal -->
  <AdminDepartmentModal
    :is-open="isModalOpen"
    :is-editing="isEditing"
    :submitting="submittingForm"
    :form="departmentForm"
    :errors="formErrors"
    @close="closeModal"
    @submit="submitForm"
  />

  <!-- Delete Confirmation Modal -->
  <DeleteConfirmationModal
    :is-open="isDeleteModalOpen"
    :is-deleting="isDeleting"
    title="Delete Department"
    :message="`Are you sure you want to delete the ${departmentToDelete?.name} department? This will affect all assigned users and students.`"
    @close="closeDeleteModal"
    @confirm="confirmDelete"
  />
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import AdminDepartmentModal from './AdminDepartmentModal.vue'
import DeleteConfirmationModal from '../common/DeleteConfirmationModal.vue'

const toast = useToast()

const departments = ref([])
const loading = ref(true)

// Modal State
const isModalOpen = ref(false)
const isEditing = ref(false)
const editingId = ref(null)
const submittingForm = ref(false)
const formErrors = ref({})

const isDeleteModalOpen = ref(false)
const isDeleting = ref(false)
const departmentToDelete = ref(null)

const departmentForm = reactive({
  department_name: '',
})

const getCsrfToken = () => {
  const tokenTag = document.querySelector('meta[name="csrf-token"]')
  return tokenTag ? tokenTag.getAttribute('content') : ''
}

const clearForm = () => {
  departmentForm.department_name = ''
}

const openCreateModal = () => {
  clearForm()
  formErrors.value = {}
  isEditing.value = false
  editingId.value = null
  isModalOpen.value = true
}

const openEditModal = (dept) => {
  formErrors.value = {}
  isEditing.value = true
  editingId.value = dept.id
  departmentForm.department_name = dept.name
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
}

const parseErrors = (payload) => {
  if (!payload || !payload.errors) {
    return { general: payload?.message || 'Unable to save department.' }
  }
  return payload.errors
}

const fetchDepartments = async () => {
  loading.value = true
  try {
    const response = await fetch('/admin/departments', {
      headers: {
        Accept: 'application/json',
      },
    })

    if (!response.ok) {
      throw new Error('Failed to load departments')
    }

    const data = await response.json()
    departments.value = data.departments ?? []
  } catch (error) {
    toast.error(error.message || 'Failed to load departments.')
  } finally {
    loading.value = false
  }
}

const submitForm = async () => {
  submittingForm.value = true
  formErrors.value = {}

  try {
    const payload = {
      department_name: departmentForm.department_name,
    }

    const url = isEditing.value ? `/admin/departments/${editingId.value}` : '/admin/departments'
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

    await fetchDepartments()
    closeModal()
    toast.success(isEditing.value ? 'Department updated successfully.' : 'Department created successfully.')
  } catch (error) {
    if (Object.keys(formErrors.value).length === 0) {
      toast.error('An unexpected error occurred.')
    }
  } finally {
    submittingForm.value = false
  }
}

const deleteDepartment = (dept) => {
  departmentToDelete.value = dept
  isDeleteModalOpen.value = true
}

const closeDeleteModal = () => {
  isDeleteModalOpen.value = false
  departmentToDelete.value = null
}

const confirmDelete = async () => {
  if (!departmentToDelete.value) return
  
  isDeleting.value = true
  try {
    const response = await fetch(`/admin/departments/${departmentToDelete.value.id}`, {
      method: 'DELETE',
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    })

    if (!response.ok) {
      throw new Error('Unable to delete department.')
    }

    departments.value = departments.value.filter((item) => item.id !== departmentToDelete.value.id)
    toast.success('Department deleted successfully.')
    closeDeleteModal()
  } catch (error) {
    toast.error(error.message || 'Unable to delete department.')
  } finally {
    isDeleting.value = false
  }
}

onMounted(() => {
  fetchDepartments()
})
</script>
