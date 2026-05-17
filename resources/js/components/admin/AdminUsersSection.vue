<template>
  <section class="space-y-5 sm:space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
      <button
        class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
        @click="openCreateModal"
      >
        <svg class="mr-2 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add User
      </button>
    </div>

    <!-- Skeleton Loader -->
    <div v-if="loading" class="animate-pulse space-y-4">
      <div class="h-12 w-full rounded-2xl bg-slate-200"></div>
      <div class="h-16 w-full rounded-2xl bg-slate-200"></div>
      <div class="h-16 w-full rounded-2xl bg-slate-200"></div>
    </div>

    <!-- Empty State (No users at all) -->
    <div v-else-if="users.length === 0" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 py-16 px-4 text-center">
      <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500 mb-4">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
      </div>
      <h3 class="text-lg font-semibold text-slate-900">No users found</h3>
      <p class="mt-1 max-w-sm text-sm text-slate-500">Get started by creating a new user to grant them access to the platform.</p>
      <button @click="openCreateModal" class="mt-5 rounded-lg bg-white border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
        Add new user
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
            placeholder="Search by name, email, or department..."
          />
        </div>
      </div>

      <!-- Data Table -->
      <div class="hidden overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
        <table class="min-w-full text-left text-sm">
          <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
            <tr>
              <th class="px-6 py-4 font-semibold">Full name</th>
              <th class="px-6 py-4 font-semibold">Email</th>
              <th class="px-6 py-4 font-semibold">Role</th>
              <th class="px-6 py-4 font-semibold">Department</th>
              <th class="px-6 py-4 font-semibold">Status</th>
              <th class="px-6 py-4 font-semibold text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="paginatedUsers.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                No users match your search query.
              </td>
            </tr>
            <tr v-for="user in paginatedUsers" :key="user.email" class="transition hover:bg-slate-50">
              <td class="px-6 py-4 font-medium text-slate-900">{{ user.name }}</td>
              <td class="px-6 py-4 text-slate-500">{{ user.email }}</td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center rounded-md bg-teal-50 px-2 py-1 text-xs font-medium text-teal-700 ring-1 ring-inset ring-teal-700/10 capitalize">{{ roleLabel(user.role) }}</span>
              </td>
              <td class="px-6 py-4 text-slate-500">{{ user.department }}</td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset" :class="user.status === 'Active' ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-slate-50 text-slate-600 ring-slate-500/10'">
                  {{ user.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-3">
                  <button class="text-slate-400 hover:text-teal-600 transition" @click="openEditModal(user)">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                  </button>
                  <button class="text-slate-400 hover:text-red-600 transition" @click="deleteUser(user)">
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
            <span class="font-medium text-slate-900">{{ Math.min(currentPage * itemsPerPage, filteredUsers.length) }}</span> of
            <span class="font-medium text-slate-900">{{ filteredUsers.length }}</span> results
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

      <!-- Mobile Cards -->
      <div class="grid gap-3 md:hidden">
        <div v-if="paginatedUsers.length === 0" class="rounded-xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500 shadow-sm">
          No users match your search query.
        </div>
        <article v-for="user in paginatedUsers" :key="`mobile-${user.email}`" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-start justify-between gap-2">
            <div>
              <p class="text-base font-semibold text-slate-900">{{ user.name }}</p>
              <p class="text-sm text-slate-500">{{ user.email }}</p>
            </div>
            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset" :class="user.status === 'Active' ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-slate-50 text-slate-600 ring-slate-500/10'">
              {{ user.status }}
            </span>
          </div>
          <div class="mt-4 flex items-center justify-between text-sm">
            <span class="inline-flex items-center rounded-md bg-teal-50 px-2 py-1 font-medium text-teal-700 ring-1 ring-inset ring-teal-700/10 capitalize">{{ roleLabel(user.role) }}</span>
            <span class="text-slate-500">{{ user.department }}</span>
          </div>
          <div class="mt-5 flex gap-3">
            <button class="flex-1 rounded-xl border border-slate-200 bg-white py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50" @click="openEditModal(user)">Edit</button>
            <button class="flex-1 rounded-xl border border-red-200 bg-red-50 py-2.5 text-sm font-medium text-red-600 transition hover:bg-red-100" @click="deleteUser(user)">Delete</button>
          </div>
        </article>

        <!-- Mobile Pagination -->
        <div v-if="totalPages > 1" class="flex items-center justify-between pt-2">
          <button
            class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition disabled:opacity-50"
            :disabled="currentPage === 1"
            @click="currentPage--"
          >
            Previous
          </button>
          <span class="text-sm text-slate-500">{{ currentPage }} / {{ totalPages }}</span>
          <button
            class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition disabled:opacity-50"
            :disabled="currentPage === totalPages"
            @click="currentPage++"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </section>

  <AdminUserModal
    :is-open="isUserModalOpen"
    :is-editing="isEditingUser"
    :submitting="submittingUserForm"
    :form="userForm"
    :departments="departmentOptions"
    :errors="formErrors"
    @close="closeUserModal"
    @submit="submitUserForm"
  />

  <DeleteConfirmationModal
    :is-open="isDeleteModalOpen"
    :is-deleting="isDeleting"
    title="Delete User"
    :message="`Are you sure you want to delete ${userToDelete?.name}? This action cannot be undone.`"
    @close="closeDeleteModal"
    @confirm="confirmDelete"
  />
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useToast } from 'vue-toastification'
import AdminUserModal from './AdminUserModal.vue'
import DeleteConfirmationModal from '../common/DeleteConfirmationModal.vue'

const toast = useToast()

const users = ref([])
const departmentOptions = ref([])
const loading = ref(true)

// Searching & Pagination
const searchQuery = ref('')
const currentPage = ref(1)
const itemsPerPage = 10

const filteredUsers = computed(() => {
  if (!searchQuery.value) return users.value
  const query = searchQuery.value.toLowerCase()
  return users.value.filter(user => 
    user.name.toLowerCase().includes(query) ||
    user.email.toLowerCase().includes(query) ||
    (user.department && user.department.toLowerCase().includes(query))
  )
})

const totalPages = computed(() => Math.ceil(filteredUsers.value.length / itemsPerPage) || 1)

const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filteredUsers.value.slice(start, end)
})

watch(searchQuery, () => {
  currentPage.value = 1 // Reset to page 1 on search
})

const isUserModalOpen = ref(false)
const isEditingUser = ref(false)
const editingUserId = ref(null)
const submittingUserForm = ref(false)
const formErrors = ref({})

const isDeleteModalOpen = ref(false)
const isDeleting = ref(false)
const userToDelete = ref(null)

const userForm = reactive({
  full_name: '',
  email: '',
  role: 'student',
  department_id: null,
  student_number: '',
  is_active: true,
  password: '',
})

const roleLabel = (role) => (role === 'department_member' ? 'department' : role)

const getCsrfToken = () => {
  const tokenTag = document.querySelector('meta[name="csrf-token"]')
  return tokenTag ? tokenTag.getAttribute('content') : ''
}

const clearForm = () => {
  userForm.full_name = ''
  userForm.email = ''
  userForm.role = 'student'
  userForm.department_id = null
  userForm.student_number = ''
  userForm.is_active = true
  userForm.password = ''
}

const openCreateModal = () => {
  clearForm()
  formErrors.value = {}
  isEditingUser.value = false
  editingUserId.value = null
  isUserModalOpen.value = true
}

const openEditModal = (user) => {
  formErrors.value = {}
  isEditingUser.value = true
  editingUserId.value = user.id
  userForm.full_name = user.name
  userForm.email = user.email
  userForm.role = user.role
  userForm.department_id = user.departmentId ?? null
  userForm.student_number = user.studentNumber ?? ''
  userForm.is_active = user.isActive
  userForm.password = ''
  isUserModalOpen.value = true
}

const closeUserModal = () => {
  isUserModalOpen.value = false
}

const parseErrors = (payload) => {
  if (!payload || !payload.errors) {
    return { general: payload?.message || 'Unable to save user.' }
  }
  return payload.errors
}

const loadUsers = async () => {
  loading.value = true
  try {
    const response = await fetch('/admin/users', {
      headers: {
        Accept: 'application/json',
      },
    })

    if (!response.ok) {
      throw new Error('Failed to load users.')
    }

    const data = await response.json()
    users.value = data.users ?? []
    departmentOptions.value = data.departments ?? []
  } catch (error) {
    toast.error(error.message || 'Failed to load users.')
  } finally {
    loading.value = false
  }
}

const submitUserForm = async () => {
  submittingUserForm.value = true
  formErrors.value = {}

  try {
    const payload = {
      full_name: userForm.full_name,
      email: userForm.email,
      role: userForm.role,
      department_id: userForm.role === 'admin' ? null : userForm.department_id,
      student_number: userForm.role === 'student' ? userForm.student_number : null,
      is_active: userForm.is_active,
      password: userForm.password,
    }

    if (isEditingUser.value && !payload.password) {
      delete payload.password
    }

    const url = isEditingUser.value ? `/admin/users/${editingUserId.value}` : '/admin/users'
    const method = isEditingUser.value ? 'PUT' : 'POST'

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

    await loadUsers()
    closeUserModal()
    toast.success(isEditingUser.value ? 'User updated successfully.' : 'User created successfully.')
  } catch (error) {
    if (Object.keys(formErrors.value).length === 0) {
      toast.error('An unexpected error occurred.')
    }
  } finally {
    submittingUserForm.value = false
  }
}

const deleteUser = (user) => {
  userToDelete.value = user
  isDeleteModalOpen.value = true
}

const closeDeleteModal = () => {
  isDeleteModalOpen.value = false
  userToDelete.value = null
}

const confirmDelete = async () => {
  if (!userToDelete.value) return
  
  isDeleting.value = true
  try {
    const response = await fetch(`/admin/users/${userToDelete.value.id}`, {
      method: 'DELETE',
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    })

    if (!response.ok) {
      throw new Error('Unable to delete user.')
    }

    users.value = users.value.filter((item) => item.id !== userToDelete.value.id)
    
    // adjust pagination if last item on page deleted
    if (paginatedUsers.value.length === 0 && currentPage.value > 1) {
      currentPage.value--
    }

    toast.success('User deleted successfully.')
    closeDeleteModal()
  } catch (error) {
    toast.error(error.message || 'Unable to delete user.')
  } finally {
    isDeleting.value = false
  }
}

watch(
  () => userForm.role,
  (role) => {
    if (role === 'admin') {
      userForm.department_id = null
    }
  },
)

onMounted(() => {
  loadUsers()
})
</script>
