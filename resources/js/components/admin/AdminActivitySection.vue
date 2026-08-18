<template>
  <section class="space-y-5 sm:space-y-6">
    <!-- Skeleton Loader -->
    <div v-if="loading" class="animate-pulse space-y-4">
      <div class="h-12 w-full rounded-2xl bg-slate-200"></div>
      <div class="h-16 w-full rounded-2xl bg-slate-200"></div>
      <div class="h-16 w-full rounded-2xl bg-slate-200"></div>
      <div class="h-16 w-full rounded-2xl bg-slate-200"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="activityLogs.length === 0" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 py-16 px-4 text-center">
      <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500 mb-4">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <h3 class="text-lg font-semibold text-slate-900">{{ $t('messages.no_activity_yet') }}</h3>
      <p class="mt-1 max-w-sm text-sm text-slate-500">Actions taken by administrators and users will appear here automatically.</p>
    </div>

    <div v-else class="space-y-4">
      <!-- Data Table -->
      <div class="hidden overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
        <table class="min-w-full text-left text-sm">
          <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
            <tr>
              <th class="px-6 py-4 font-semibold">{{ $t('common.time') }}</th>
              <th class="px-6 py-4 font-semibold">{{ $t('common.actor') }}</th>
              <th class="px-6 py-4 font-semibold">{{ $t('common.action') }}</th>
              <th class="px-6 py-4 font-semibold">{{ $t('common.target') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(log, index) in activityLogs" :key="index" class="transition hover:bg-slate-50">
              <td class="px-6 py-4 text-slate-500 whitespace-nowrap">{{ log.time }}</td>
              <td class="px-6 py-4 font-medium text-slate-900">{{ log.actor }}</td>
              <td class="px-6 py-4 text-slate-700">
                <span class="inline-flex items-center rounded-md bg-teal-50 px-2 py-1 text-xs font-medium text-teal-700 ring-1 ring-inset ring-teal-700/10">{{ log.action }}</span>
              </td>
              <td class="px-6 py-4 text-slate-500">{{ log.target }}</td>
            </tr>
          </tbody>
        </table>
        
        <!-- Pagination -->
        <div v-if="totalPages > 1" class="flex items-center justify-between border-t border-slate-200 bg-white px-6 py-3">
          <div class="text-sm text-slate-500">
            Page <span class="font-medium text-slate-900">{{ currentPage }}</span> of
            <span class="font-medium text-slate-900">{{ totalPages }}</span>
          </div>
          <div class="flex items-center gap-2">
            <button
              class="rounded-md border border-slate-300 px-3 py-1 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="currentPage === 1"
              @click="loadLogs(currentPage - 1)"
            >
              Previous
            </button>
            <button
              class="rounded-md border border-slate-300 px-3 py-1 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="currentPage === totalPages"
              @click="loadLogs(currentPage + 1)"
            >
              Next
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile Cards -->
      <div class="grid gap-3 md:hidden">
        <article v-for="(log, index) in activityLogs" :key="`mobile-log-${index}`" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-start justify-between gap-2">
            <div>
              <p class="text-base font-semibold text-slate-900">{{ log.actor }}</p>
              <p class="text-xs text-slate-500">{{ log.time }}</p>
            </div>
          </div>
          <div class="mt-3">
            <span class="inline-flex items-center rounded-md bg-teal-50 px-2 py-1 text-xs font-medium text-teal-700 ring-1 ring-inset ring-teal-700/10">{{ log.action }}</span>
          </div>
          <p class="mt-2 text-sm text-slate-600">{{ log.target }}</p>
        </article>

        <!-- Mobile Pagination -->
        <div v-if="totalPages > 1" class="flex items-center justify-between pt-2">
          <button
            class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition disabled:opacity-50"
            :disabled="currentPage === 1"
            @click="loadLogs(currentPage - 1)"
          >
            Previous
          </button>
          <span class="text-sm text-slate-500">{{ currentPage }} / {{ totalPages }}</span>
          <button
            class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition disabled:opacity-50"
            :disabled="currentPage === totalPages"
            @click="loadLogs(currentPage + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'

const toast = useToast()

const activityLogs = ref([])
const loading = ref(true)

const currentPage = ref(1)
const totalPages = ref(1)

const loadLogs = async (page = 1) => {
  loading.value = true
  try {
    const response = await fetch(`/admin/activity-logs?page=${page}`, {
      headers: {
        Accept: 'application/json',
      },
    })

    if (!response.ok) {
      throw new Error('Failed to load activity logs.')
    }

    const data = await response.json()
    activityLogs.value = data.logs ?? []
    
    if (data.pagination) {
      currentPage.value = data.pagination.current_page
      totalPages.value = data.pagination.last_page
    }
  } catch (error) {
    toast.error(error.message || 'Failed to load activity logs.')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadLogs()
})
</script>
