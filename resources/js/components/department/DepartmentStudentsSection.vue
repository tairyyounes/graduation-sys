<template>
  <section class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-4xl">Students</h1>
        <p class="mt-1 text-sm text-slate-500">Upload CSV or manually add students into your department database records.</p>
      </div>
      <button
        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
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
        <h2 class="text-lg font-semibold text-slate-900">CSV upload</h2>
        <p class="mt-1 text-sm text-slate-500">Required columns: <code class="bg-slate-100 px-1 py-0.5 rounded text-indigo-600 text-xs">student_number,full_name,official_email,semester</code>. Optional: <code class="bg-slate-100 px-1 py-0.5 rounded text-indigo-600 text-xs">is_active</code></p>

        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center">
          <input
            ref="fileInput"
            type="file"
            accept=".csv,.txt"
            class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 transition-colors"
            @change="onCsvChange"
          />
          <button
            class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
            :disabled="!selectedCsv || uploadingCsv"
            @click="uploadCsv"
          >
            {{ uploadingCsv ? 'Importing...' : 'Import CSV' }}
          </button>
        </div>
      </article>

      <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <h2 class="text-lg font-semibold text-slate-900">Imported students</h2>
          
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
              class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition"
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
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="paginatedStudents.length === 0">
                <td colspan="5" class="px-5 py-8 text-center text-slate-500">
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
    />
  </section>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useToast } from 'vue-toastification'
import DepartmentStudentModal from './DepartmentStudentModal.vue'

const toast = useToast()

const students = ref([])
const loading = ref(true)
const fileInput = ref(null)
const selectedCsv = ref(null)
const uploadingCsv = ref(false)

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
const submittingStudentForm = ref(false)
const formErrors = ref({})

const studentForm = reactive({
  student_number: '',
  full_name: '',
  official_email: '',
  semester: 8,
  is_active: true,
})

const getCsrfToken = () => {
  const tokenTag = document.querySelector('meta[name="csrf-token"]')
  return tokenTag ? tokenTag.getAttribute('content') : ''
}

const parseErrors = (payload) => {
  if (!payload || !payload.errors) {
    return { general: payload?.message || 'Unable to save student.' }
  }
  return payload.errors
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
      throw new Error(data.message || 'Import failed.')
    }

    toast.success(`${data.imported_count} students imported successfully.`)
    selectedCsv.value = null
    if (fileInput.value) fileInput.value.value = ''
    await loadStudents()
  } catch (error) {
    toast.error(error.message || 'Import failed.')
  } finally {
    uploadingCsv.value = false
  }
}

const clearStudentForm = () => {
  studentForm.student_number = ''
  studentForm.full_name = ''
  studentForm.official_email = ''
  studentForm.semester = 8
  studentForm.is_active = true
}

const openStudentModal = () => {
  clearStudentForm()
  formErrors.value = {}
  isEditingStudent.value = false
  isStudentModalOpen.value = true
}

const closeStudentModal = () => {
  isStudentModalOpen.value = false
}

const submitStudentForm = async () => {
  submittingStudentForm.value = true
  formErrors.value = {}

  try {
    const response = await fetch('/department/students', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify(studentForm),
    })

    const data = await response.json()

    if (!response.ok) {
      formErrors.value = parseErrors(data)
      throw new Error('Validation failed')
    }

    toast.success(data.message || 'Student added successfully.')
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

onMounted(() => {
  loadStudents()
})
</script>
