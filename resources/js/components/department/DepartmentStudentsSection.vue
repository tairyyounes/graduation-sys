<template>
  <section class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
      <a
        href="/department/students/template"
        class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2"
        download
      >
        <svg class="mr-2 h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        Download CSV Template
      </a>
      <button
        class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
        @click="openStudentModal"
      >
        <svg class="mr-2 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add student
      </button>
    </div>

    <!-- Skeleton Loader -->
    <div v-if="loading" class="animate-pulse space-y-4">
      <div class="h-32 w-full rounded-2xl bg-slate-200"></div>
      <div class="h-64 w-full rounded-2xl bg-slate-200"></div>
    </div>

    <div v-else class="space-y-5">
      <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">CSV Bulk Import</h2>
        <p class="mt-1 text-sm text-slate-500">Upload a CSV file to preview and import multiple students at once.</p>

        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center">
          <input
            ref="fileInput"
            type="file"
            accept=".csv,.txt"
            class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-teal-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-teal-700 hover:file:bg-teal-100 transition-colors"
            @change="onCsvChange"
          />
          <button
            class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
            :disabled="!selectedCsv || uploadingCsv"
            @click="uploadCsv"
          >
            {{ uploadingCsv ? 'Parsing...' : 'Preview CSV' }}
          </button>
        </div>
      </article>

      <!-- Staged Students Section -->
      <article v-if="stagedStudents.length > 0" class="rounded-2xl border border-teal-200 bg-teal-50/30 p-5 shadow-sm">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 class="text-lg font-semibold text-teal-900">Staged for Import</h2>
            <p class="text-sm text-teal-700 mt-1">Review the parsed students. Existing students are highlighted in red and will be ignored. You can edit names and emails before confirming.</p>
          </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-teal-100 bg-white shadow-sm">
          <table class="min-w-full text-left text-sm">
            <thead class="bg-teal-50 text-teal-700 border-b border-teal-100">
              <tr>
                <th class="px-4 py-3 font-semibold">Status</th>
                <th class="px-4 py-3 font-semibold">Student number</th>
                <th class="px-4 py-3 font-semibold">Full name</th>
                <th class="px-4 py-3 font-semibold">Email</th>
                <th class="px-4 py-3 font-semibold text-center">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-teal-50">
              <tr v-for="(student, index) in stagedStudents" :key="index" :class="student.exists ? 'bg-red-50/50' : 'hover:bg-slate-50'">
                <td class="px-4 py-3">
                  <span v-if="student.exists" class="inline-flex items-center rounded-md bg-red-100 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">Already Exists</span>
                  <span v-else class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Ready</span>
                </td>
                <td class="px-4 py-3">
                  <input v-if="!student.exists" v-model="student.student_number" type="text" class="w-28 rounded border-slate-300 px-2 py-1 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                  <span v-else class="text-slate-500 line-through">{{ student.student_number }}</span>
                </td>
                <td class="px-4 py-3">
                  <input v-if="!student.exists" v-model="student.full_name" type="text" class="w-full rounded border-slate-300 px-2 py-1 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                  <span v-else class="text-slate-500 line-through">{{ student.full_name }}</span>
                </td>
                <td class="px-4 py-3">
                  <input v-if="!student.exists" v-model="student.email" type="email" class="w-full rounded border-slate-300 px-2 py-1 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                  <span v-else class="text-slate-500 line-through">{{ student.email }}</span>
                </td>
                <td class="px-4 py-3 text-center">
                  <button @click="stagedStudents.splice(index, 1)" class="text-red-500 hover:text-red-700 transition" title="Remove row">
                    <svg class="h-4 w-4 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-5 flex justify-end">
          <button
            class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 transition disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 shadow-sm"
            :disabled="confirmingImport"
            @click="confirmImport"
          >
            {{ confirmingImport ? 'Saving...' : 'Confirm & Add Students' }}
          </button>
        </div>
      </article>

      <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <h2 class="text-lg font-semibold text-slate-900">Current students</h2>
          
          <!-- Search Bar -->
          <div class="relative w-full sm:w-72">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
              </svg>
            </div>
            <input
              v-model="searchQuery"
              type="text"
              class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm placeholder-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 transition"
              placeholder="Search students..."
            />
          </div>
        </div>
        
        <div v-if="students.length === 0" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 py-12 px-4 text-center">
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500 mb-3">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          </div>
          <h3 class="text-sm font-semibold text-slate-900">No students found</h3>
          <p class="mt-1 max-w-sm text-xs text-slate-500">Import a CSV or add students manually to populate this list.</p>
        </div>

        <div v-else class="overflow-x-auto rounded-xl border border-slate-200">
          <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
              <tr>
                <th class="px-5 py-3 font-semibold">Student number</th>
                <th class="px-5 py-3 font-semibold">Full name</th>
                <th class="px-5 py-3 font-semibold">Email</th>
                <th class="px-5 py-3 font-semibold">Semester</th>
                <th class="px-5 py-3 font-semibold">Status</th>
                <th class="px-5 py-3 font-semibold text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="paginatedStudents.length === 0">
                <td colspan="6" class="px-5 py-8 text-center text-slate-500">
                  No students match your search.
                </td>
              </tr>
              <tr v-for="student in paginatedStudents" :key="student.student_id" class="transition hover:bg-slate-50">
                <td class="px-5 py-4 font-medium text-slate-900">{{ student.student_number }}</td>
                <td class="px-5 py-4 text-slate-700">{{ student.full_name }}</td>
                <td class="px-5 py-4 text-slate-500">{{ student.official_email }}</td>
                <td class="px-5 py-4 text-slate-500">{{ student.semester }}</td>
                <td class="px-5 py-4">
                  <span
                    class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
                    :class="student.is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-slate-50 text-slate-600 ring-slate-500/10'"
                  >
                    {{ student.is_active ? 'Active' : 'Disabled' }}
                  </span>
                </td>
                <td class="px-5 py-4 text-right">
                  <div class="flex items-center justify-end gap-3">
                    <button class="text-slate-400 hover:text-teal-600 transition" @click="openEditModal(student)">
                      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button class="text-slate-400 hover:text-red-600 transition" @click="deleteStudent(student)">
                      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-5 py-3">
            <div class="text-sm text-slate-500">
              Showing <span class="font-medium text-slate-900">{{ (currentPage - 1) * itemsPerPage + 1 }}</span> to
              <span class="font-medium text-slate-900">{{ Math.min(currentPage * itemsPerPage, filteredStudents.length) }}</span> of
              <span class="font-medium text-slate-900">{{ filteredStudents.length }}</span> results
            </div>
            <div class="flex items-center gap-2">
              <button
                class="rounded-md border border-slate-300 bg-white px-3 py-1 text-sm font-medium text-slate-700 transition hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="currentPage === 1"
                @click="currentPage--"
              >
                Previous
              </button>
              <button
                class="rounded-md border border-slate-300 bg-white px-3 py-1 text-sm font-medium text-slate-700 transition hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="currentPage === totalPages"
                @click="currentPage++"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </article>
    </div>

    <DepartmentStudentModal
      :is-open="isStudentModalOpen"
      :is-editing="isEditingStudent"
      :submitting="submittingStudentForm"
      :form="studentForm"
      :errors="formErrors"
      @close="closeStudentModal"
      @submit="submitStudentForm"
      @clear="clearStudentForm"
    />

    <DeleteConfirmationModal
      :is-open="isDeleteModalOpen"
      :is-deleting="isDeleting"
      title="Delete Student"
      :message="`Are you sure you want to delete ${studentToDelete?.full_name}? This action cannot be undone.`"
      @close="closeDeleteModal"
      @confirm="confirmDelete"
    />
  </section>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useToast } from 'vue-toastification'
import DepartmentStudentModal from './DepartmentStudentModal.vue'
import DeleteConfirmationModal from '../common/DeleteConfirmationModal.vue'

const toast = useToast()

const students = ref([])
const loading = ref(true)
const fileInput = ref(null)
const selectedCsv = ref(null)
const uploadingCsv = ref(false)

const stagedStudents = ref([])
const confirmingImport = ref(false)

// Searching & Pagination
const searchQuery = ref('')
const currentPage = ref(1)
const itemsPerPage = 10

const filteredStudents = computed(() => {
  if (!searchQuery.value) return students.value
  const query = searchQuery.value.toLowerCase()
  return students.value.filter(student => 
    student.full_name.toLowerCase().includes(query) ||
    student.official_email.toLowerCase().includes(query) ||
    student.student_number.toLowerCase().includes(query)
  )
})

const totalPages = computed(() => Math.ceil(filteredStudents.value.length / itemsPerPage) || 1)

const paginatedStudents = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filteredStudents.value.slice(start, end)
})

watch(searchQuery, () => {
  currentPage.value = 1
})

const isStudentModalOpen = ref(false)
const isEditingStudent = ref(false)
const editingStudentId = ref(null)
const submittingStudentForm = ref(false)
const formErrors = ref({})

const isDeleteModalOpen = ref(false)
const isDeleting = ref(false)
const studentToDelete = ref(null)

const studentForm = reactive({
  student_number: '',
  full_name: '',
  email: '',
  semester: 8,
  is_active: true,
})

const getCsrfToken = () => {
  const tokenTag = document.querySelector('meta[name="csrf-token"]')
  return tokenTag ? tokenTag.getAttribute('content') : ''
}

const parseErrors = (payload) => {
  if (!payload || !payload.errors) {
    return { general: payload?.message || 'Unable to save data.' }
  }
  
  // Transform dot notation (students.0.email) back to row level if needed
  const flattened = {}
  for (const [key, msgs] of Object.entries(payload.errors)) {
      flattened[key] = msgs
  }
  return flattened
}

const loadStudents = async () => {
  loading.value = true
  try {
    const response = await fetch('/department/students', {
      headers: { Accept: 'application/json' },
    })
    const data = await response.json()
    if (!response.ok) {
      throw new Error(data.message || 'Failed to load students.')
    }
    students.value = data.students ?? []
  } catch (error) {
    toast.error(error.message || 'Failed to load students.')
  } finally {
    loading.value = false
  }
}

const onCsvChange = (event) => {
  selectedCsv.value = event.target.files?.[0] ?? null
}

const uploadCsv = async () => {
  if (!selectedCsv.value) return

  uploadingCsv.value = true
  stagedStudents.value = []

  const formData = new FormData()
  formData.append('file', selectedCsv.value)

  try {
    const response = await fetch('/department/students/import', {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: formData,
    })

    const data = await response.json()
    if (!response.ok) {
      throw new Error(data.message || 'Import parsing failed.')
    }

    stagedStudents.value = data.staged_students || []
    toast.info('CSV parsed. Please review the staged students before confirming.')
  } catch (error) {
    toast.error(error.message || 'Import parsing failed.')
  } finally {
    uploadingCsv.value = false
  }
}

const confirmImport = async () => {
  // Filter out any students marked as exists
  const validStudents = stagedStudents.value.filter(s => !s.exists)

  if (validStudents.length === 0) {
    toast.warning('No new students to import.')
    return
  }

  confirmingImport.value = true
  try {
    const response = await fetch('/department/students/import-confirm', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify({ students: validStudents }),
    })

    const data = await response.json()
    if (!response.ok) {
        if (data.errors) {
            toast.error('Validation failed for one or more students. Check the data.')
        } else {
            throw new Error(data.message || 'Import confirmation failed.')
        }
        return
    }

    toast.success(data.message || `${data.imported_count} students added successfully.`)
    stagedStudents.value = []
    selectedCsv.value = null
    if (fileInput.value) fileInput.value.value = ''
    await loadStudents()
  } catch (error) {
    toast.error(error.message || 'Import confirmation failed.')
  } finally {
    confirmingImport.value = false
  }
}

const clearStudentForm = () => {
  studentForm.student_number = ''
  studentForm.full_name = ''
  studentForm.email = ''
  studentForm.semester = 8
  studentForm.is_active = true
}

const openStudentModal = () => {
  clearStudentForm()
  formErrors.value = {}
  isEditingStudent.value = false
  editingStudentId.value = null
  isStudentModalOpen.value = true
}

const openEditModal = (student) => {
  formErrors.value = {}
  isEditingStudent.value = true
  editingStudentId.value = student.student_id
  studentForm.student_number = student.student_number
  studentForm.full_name = student.full_name
  studentForm.email = student.official_email
  studentForm.semester = student.semester
  studentForm.is_active = student.is_active
  isStudentModalOpen.value = true
}

const closeStudentModal = () => {
  isStudentModalOpen.value = false
}

const submitStudentForm = async () => {
  submittingStudentForm.value = true
  formErrors.value = {}

  // Inject required properties for AddingUserRequest
  const payload = {
      ...studentForm,
      role: 'student',
      password: studentForm.student_number // Use student number as default password
  }

  try {
    const url = isEditingStudent.value ? `/department/students/${editingStudentId.value}` : '/department/students'
    const method = isEditingStudent.value ? 'PUT' : 'POST'

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

    toast.success(data.message || (isEditingStudent.value ? 'Student updated successfully.' : 'Student added successfully.'))
    closeStudentModal()
    await loadStudents()
  } catch (error) {
    if (Object.keys(formErrors.value).length === 0) {
      toast.error('An unexpected error occurred.')
    }
  } finally {
    submittingStudentForm.value = false
  }
}

const deleteStudent = (student) => {
  studentToDelete.value = student
  isDeleteModalOpen.value = true
}

const closeDeleteModal = () => {
  isDeleteModalOpen.value = false
  studentToDelete.value = null
}

const confirmDelete = async () => {
  if (!studentToDelete.value) return
  
  isDeleting.value = true
  try {
    const response = await fetch(`/department/students/${studentToDelete.value.student_id}`, {
      method: 'DELETE',
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    })

    if (!response.ok) {
      throw new Error('Unable to delete student.')
    }

    students.value = students.value.filter((item) => item.student_id !== studentToDelete.value.student_id)
    
    if (paginatedStudents.value.length === 0 && currentPage.value > 1) {
      currentPage.value--
    }

    toast.success('Student deleted successfully.')
    closeDeleteModal()
  } catch (error) {
    toast.error(error.message || 'Unable to delete student.')
  } finally {
    isDeleting.value = false
  }
}

onMounted(() => {
  loadStudents()
})
</script>
