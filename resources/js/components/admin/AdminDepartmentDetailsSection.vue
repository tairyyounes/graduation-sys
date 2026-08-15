<template>
  <section class="space-y-5 sm:space-y-6">
    <!-- Header with Back button -->
    <div class="flex items-center gap-4">
      <router-link
        :to="{ name: 'AdminDepartments' }"
        class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-400"
      >
        <svg class="h-5 w-5 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </router-link>

      <div v-if="!loading && department">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">{{ department.name }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $t('admin.dept_details.subtitle') }}</p>
      </div>
      <div v-else-if="loading" class="h-8 w-64 rounded bg-slate-200 animate-pulse"></div>
    </div>

    <!-- Skeleton Loader -->
    <div v-if="loading" class="animate-pulse space-y-6">
      <!-- Tabs skeleton -->
      <div class="h-12 w-full max-w-md rounded-lg bg-slate-200"></div>
      <!-- Stats skeleton -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="h-24 rounded-2xl bg-slate-200"></div>
        <div class="h-24 rounded-2xl bg-slate-200"></div>
        <div class="h-24 rounded-2xl bg-slate-200"></div>
      </div>
      <!-- Table skeleton -->
      <div class="h-64 rounded-2xl bg-slate-200"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="!department" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 py-16 px-4 text-center">
      <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500 mb-4">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      </div>
      <h3 class="text-lg font-semibold text-slate-900">{{ $t('admin.dept_details.not_found') }}</h3>
      <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $t('admin.dept_details.not_found_desc') }}</p>
      <router-link :to="{ name: 'AdminDepartments' }" class="mt-5 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800">
        {{ $t('admin.dept_details.go_back') }}
      </router-link>
    </div>

    <!-- Content -->
    <div v-else class="space-y-6">
      <!-- Stats Overview -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-sm font-medium text-slate-500">{{ $t('deptnav.members') }}</p>
          <p class="mt-2 text-3xl font-semibold text-slate-900">{{ department.members_count }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-sm font-medium text-slate-500">{{ $t('admin.dept_details.enrolled_students') }}</p>
          <p class="mt-2 text-3xl font-semibold text-slate-900">{{ students.length }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-sm font-medium text-slate-500">{{ $t('admin.dept_details.active_proposals') }}</p>
          <p class="mt-2 text-3xl font-semibold text-slate-900">{{ department.proposals_count }}</p>
        </div>
      </div>

      <!-- Tabs Navigation -->
      <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-6 rtl:space-x-reverse" aria-label="Tabs">
          <button
            @click="activeTab = 'members'"
            :class="[
              activeTab === 'members'
                ? 'border-teal-500 text-teal-600'
                : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700',
              'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors'
            ]"
          >
            {{ $t('deptnav.members') }}
          </button>
          <button
            @click="activeTab = 'students'"
            :class="[
              activeTab === 'students'
                ? 'border-teal-500 text-teal-600'
                : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700',
              'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors'
            ]"
          >
            {{ $t('deptnav.students') }}
          </button>
          <button
            @click="activeTab = 'proposals'"
            :class="[
              activeTab === 'proposals'
                ? 'border-teal-500 text-teal-600'
                : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700',
              'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors'
            ]"
          >
            {{ $t('admin.departments.proposals') }}
          </button>
        </nav>
      </div>

      <!-- Members Tab -->
      <div v-if="activeTab === 'members'" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-start text-sm">
          <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
            <tr>
              <th class="px-6 py-4 font-semibold">{{ $t('student.team.name') }}</th>
              <th class="px-6 py-4 font-semibold">{{ $t('dept.students.email') }}</th>
              <th class="px-6 py-4 font-semibold">{{ $t('fields.status') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="members.length === 0">
              <td colspan="3" class="px-6 py-12 text-center text-slate-500">
                {{ $t('admin.dept_details.no_members') }}
              </td>
            </tr>
            <tr v-for="member in members" :key="member.id" class="transition hover:bg-slate-50">
              <td class="px-6 py-4 font-medium text-slate-900">{{ member.name }}</td>
              <td class="px-6 py-4 text-slate-500">{{ member.email }}</td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset" :class="member.status === 'Active' ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-slate-50 text-slate-600 ring-slate-500/10'">
                  {{ member.status === 'Active' ? $t('status.active') : $t('dept.students.disabled') }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Students Tab -->
      <div v-if="activeTab === 'students'" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-start text-sm">
          <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
            <tr>
              <th class="px-6 py-4 font-semibold">{{ $t('admin.dept_details.student_no') }}</th>
              <th class="px-6 py-4 font-semibold">{{ $t('student.team.name') }}</th>
              <th class="px-6 py-4 font-semibold">{{ $t('dept.students.email') }}</th>
              <th class="px-6 py-4 font-semibold">{{ $t('dept.students.semester') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="students.length === 0">
              <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                {{ $t('admin.dept_details.no_students') }}
              </td>
            </tr>
            <tr v-for="student in students" :key="student.id" class="transition hover:bg-slate-50">
              <td class="px-6 py-4 text-slate-500">{{ student.student_number }}</td>
              <td class="px-6 py-4 font-medium text-slate-900">{{ student.name }}</td>
              <td class="px-6 py-4 text-slate-500">{{ student.email }}</td>
              <td class="px-6 py-4 text-slate-500">{{ student.semester }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Proposals Tab -->
      <div v-if="activeTab === 'proposals'" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 py-16 px-4 text-center">
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500 mb-4">
          <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900">{{ $t('admin.dept_details.coming_soon') }}</h3>
        <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $t('admin.dept_details.coming_desc') }}</p>
      </div>

    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'

const route = useRoute()
const toast = useToast()
const { t } = useI18n()

const activeTab = ref('members')
const loading = ref(true)

const department = ref(null)
const members = ref([])
const students = ref([])

const fetchDepartmentData = async () => {
  loading.value = true
  try {
    const departmentId = route.params.id
    const response = await fetch(`/admin/departments/${departmentId}`, {
      headers: {
        Accept: 'application/json',
      },
    })

    if (!response.ok) {
      if (response.status === 404) {
        department.value = null
        return
      }
      throw new Error(t('admin.dept_details.toast.load_failed'))
    }

    const data = await response.json()
    department.value = data.department
    members.value = data.members ?? []
    students.value = data.students ?? []
  } catch (error) {
    toast.error(error.message || t('admin.dept_details.toast.load_failed'))
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchDepartmentData()
})
</script>
