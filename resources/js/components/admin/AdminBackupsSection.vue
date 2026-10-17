<template>
  <div class="space-y-6">
    <!-- Maintenance / Restore in Progress Screen -->
    <div
      v-if="isRestoring"
      class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-slate-900/90 p-6 text-center text-white backdrop-blur-md"
    >
      <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-500/20 text-amber-400 mb-4 ring-8 ring-amber-500/10">
        <svg class="h-8 w-8 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
      </div>
      <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
        {{ $t('backups.maintenance.title') }}
      </h2>
      <p class="mt-2 max-w-md text-sm text-slate-300">
        {{ $t('backups.maintenance.subtitle') }}
      </p>

      <div class="mt-6 flex flex-col items-center gap-3">
        <div class="inline-flex items-center gap-2 rounded-full bg-slate-800 px-4 py-1.5 text-xs text-amber-300 font-mono">
          <span class="h-2 w-2 rounded-full bg-amber-400 animate-ping"></span>
          {{ $t('backups.maintenance.retrying', { attempt: retryCount }) }}
        </div>

        <button
          type="button"
          class="rounded-xl border border-slate-700 bg-slate-800 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-slate-700 transition"
          @click="checkServerHealth"
        >
          {{ $t('backups.maintenance.retry_now') }}
        </button>
      </div>
    </div>

    <!-- Page Header & Main Actions -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
          {{ $t('backups.title') }}
        </h1>
        <p class="mt-1 text-sm text-slate-500">
          {{ $t('backups.subtitle') }}
        </p>
      </div>

      <div class="flex items-center gap-2">
        <!-- Refresh Button -->
        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 disabled:opacity-50 sm:text-sm"
          :disabled="loading || isJobRunning"
          @click="fetchBackups(currentPage, selectedDisk)"
        >
          <svg
            class="h-4 w-4"
            :class="{ 'animate-spin': loading }"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          {{ $t('backups.actions.refresh') }}
        </button>

        <!-- Create Backup Button -->
        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-xl bg-teal-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 disabled:opacity-50 sm:text-sm"
          :disabled="isJobRunning"
          @click="isCreateModalOpen = true"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          {{ $t('backups.actions.create_backup') }}
        </button>
      </div>
    </div>

    <!-- Active Job Progress Banner -->
    <div
      v-if="isJobRunning"
      class="flex items-center gap-3 rounded-2xl border border-teal-200 bg-teal-50/70 p-4 text-teal-900 shadow-xs"
    >
      <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-500 text-white shadow-xs">
        <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
      </div>
      <div class="flex-1">
        <h4 class="text-sm font-bold">
          {{ activeJob.type === 'restore' ? $t('backups.status.running_restore') : $t('backups.status.running_backup') }}
        </h4>
        <p class="text-xs text-teal-700">
          {{ $t('backups.status.in_progress_desc') }}
        </p>
      </div>
      <span class="rounded-full bg-teal-100 px-3 py-1 text-xs font-semibold text-teal-800 uppercase tracking-wider">
        {{ activeJob.status }}
      </span>
    </div>

    <!-- KPI & Health Overview Cards -->
    <BackupStatusCard :status-summary="statusSummary" />

    <!-- Backups List Table -->
    <BackupTable
      :backups="backupList"
      :disks="disks"
      :selected-disk="selectedDisk"
      :pagination="pagination"
      :loading="loading"
      :is-job-running="isJobRunning"
      @change-disk="handleDiskChange"
      @page-change="handlePageChange"
      @change-page="handlePageChange"
      @refresh="fetchBackups(currentPage, selectedDisk)"
      @download="handleDownloadBackup"
      @open-restore="openRestoreModal"
      @restore="openRestoreModal"
      @open-delete="openDeleteModal"
      @delete="openDeleteModal"
    />

    <!-- Audit Logs Section -->
    <BackupLogsTable
      :logs="recentLogs.data || []"
      :pagination="recentLogs"
      @page-change="handleLogsPageChange"
    />

    <!-- Modals -->
    <CreateBackupModal
      :is-open="isCreateModalOpen"
      :disks="disks"
      :selected-disk="selectedDisk"
      :loading="isSubmitting"
      @close="isCreateModalOpen = false"
      @submit="handleCreateBackup"
    />

    <ConfirmDeleteModal
      :is-open="isDeleteModalOpen"
      :backup-item="selectedBackupItem"
      :loading="isSubmitting"
      @close="isDeleteModalOpen = false"
      @confirm="handleDeleteBackup"
    />

    <RestoreModal
      :is-open="isRestoreModalOpen"
      :backup-item="selectedBackupItem"
      :loading="isSubmitting"
      @close="isRestoreModalOpen = false"
      @confirm="handleRestoreBackup"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import { useIntervalFn } from '@vueuse/core'
import axios from 'axios'

import BackupStatusCard from '../backups/BackupStatusCard.vue'
import BackupTable from '../backups/BackupTable.vue'
import CreateBackupModal from '../backups/CreateBackupModal.vue'
import ConfirmDeleteModal from '../backups/ConfirmDeleteModal.vue'
import RestoreModal from '../backups/RestoreModal.vue'
import BackupLogsTable from '../backups/BackupLogsTable.vue'

const { t } = useI18n()
const toast = useToast()

const loading = ref(false)
const isSubmitting = ref(false)
const isRestoring = ref(false)
const retryCount = ref(0)

const isCreateModalOpen = ref(false)
const isDeleteModalOpen = ref(false)
const isRestoreModalOpen = ref(false)
const selectedBackupItem = ref(null)

const disks = ref(['backups'])
const selectedDisk = ref('backups')
const backupList = ref([])
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  from: 0,
  to: 0,
  per_page: 10,
})
const currentPage = ref(1)

const statusSummary = ref({
  total_backups: 0,
  total_size_bytes: 0,
  formatted_total_size: '0 B',
  last_backup_date: null,
  last_backup_relative: null,
  last_backup_age_hours: null,
  is_older_than_25_hours: true,
  active_disks: ['backups'],
  selected_disk: 'backups',
  active_job: null,
})

const recentLogs = ref({
  data: [],
  current_page: 1,
  last_page: 1,
})

// Current Polling Job
const activeJob = ref(null)
const isJobRunning = computed(() => {
  return activeJob.value !== null && (activeJob.value.status === 'pending' || activeJob.value.status === 'running')
})

// Polling interval using useIntervalFn from @vueuse/core
const { pause: stopPolling, resume: startPolling } = useIntervalFn(
  async () => {
    if (!activeJob.value || !activeJob.value.id) {
      stopPolling()
      return
    }

    try {
      const response = await axios.get(`/admin/backups/status/${activeJob.value.id}`, {
        headers: { Accept: 'application/json' },
      })
      const data = response.data

      activeJob.value = data

      if (data.status === 'success') {
        stopPolling()
        activeJob.value = null
        if (data.type === 'restore') {
          isRestoring.value = false
          toast.success(t('backups.toast.restore_completed'))
        } else {
          toast.success(t('backups.toast.backup_completed', { file: data.file_name || '' }))
        }
        fetchBackups(currentPage.value, selectedDisk.value)
      } else if (data.status === 'failed') {
        stopPolling()
        activeJob.value = null
        if (data.type === 'restore') {
          isRestoring.value = false
          toast.error(t('backups.toast.restore_failed', { error: data.error_message || '' }))
        } else {
          toast.error(t('backups.toast.backup_failed', { error: data.error_message || '' }))
        }
        fetchBackups(currentPage.value, selectedDisk.value)
      }
    } catch (err) {
      if (err.response && err.response.status === 503) {
        isRestoring.value = true
        retryCount.value++
      } else if (err.response && err.response.status === 401) {
        stopPolling()
        window.location.href = '/login'
      }
    }
  },
  3000,
  { immediate: false }
)

// Fetch all backup data via JSON API
async function fetchBackups(page = 1, disk = null) {
  loading.value = true
  try {
    const targetDisk = disk || selectedDisk.value
    const response = await axios.get('/admin/backups', {
      params: {
        page,
        disk: targetDisk,
      },
      headers: { Accept: 'application/json' },
    })

    const data = response.data
    if (data.backups) {
      backupList.value = data.backups.data || []
      pagination.value = {
        current_page: data.backups.current_page || 1,
        last_page: data.backups.last_page || 1,
        total: data.backups.total || 0,
        from: data.backups.from || 0,
        to: data.backups.to || 0,
        per_page: data.backups.per_page || 10,
      }
      currentPage.value = data.backups.current_page || 1
    }

    if (data.statusSummary) {
      statusSummary.value = data.statusSummary
      if (data.statusSummary.active_job) {
        activeJob.value = data.statusSummary.active_job
        startPolling()
      }
    }

    if (data.recentLogs) {
      recentLogs.value = data.recentLogs
    }

    if (data.disks) {
      disks.value = data.disks
    }

    if (data.statusSummary?.selected_disk) {
      selectedDisk.value = data.statusSummary.selected_disk
    }
  } catch (err) {
    if (err.response && err.response.status === 401) {
      window.location.href = '/login'
      return
    }
    toast.error(err.response?.data?.message || t('backups.toast.failed_to_load'))
  } finally {
    loading.value = false
  }
}

function handleDiskChange(newDisk) {
  selectedDisk.value = newDisk
  currentPage.value = 1
  fetchBackups(1, newDisk)
}

function handlePageChange(newPage) {
  currentPage.value = newPage
  fetchBackups(newPage, selectedDisk.value)
}

async function handleLogsPageChange(urlOrPage) {
  try {
    let page = urlOrPage
    if (typeof urlOrPage === 'string') {
      const match = urlOrPage.match(/[?&]page=(\d+)/)
      page = match ? parseInt(match[1]) : 1
    }
    const response = await axios.get('/admin/backups', {
      params: { page: currentPage.value, disk: selectedDisk.value, logs_page: page },
      headers: { Accept: 'application/json' },
    })
    if (response.data.recentLogs) {
      recentLogs.value = response.data.recentLogs
    }
  } catch {
    // silent catch
  }
}

// Create Backup Handler
async function handleCreateBackup(payload = {}) {
  if (isSubmitting.value) return
  isSubmitting.value = true
  isCreateModalOpen.value = false

  try {
    const type = typeof payload === 'string' ? payload : (payload?.type || 'full')
    const disk = (typeof payload === 'object' && payload?.disk) ? payload.disk : selectedDisk.value

    const response = await axios.post('/admin/backups', {
      type,
      disk,
    }, {
      headers: { Accept: 'application/json' },
    })

    toast.info(t('backups.toast.backup_started'))

    activeJob.value = {
      id: response.data.log_id,
      type: response.data.type || type,
      status: 'pending',
    }
    startPolling()
  } catch (err) {
    const message = err.response?.data?.message || 'Failed to start backup.'
    toast.error(message)
  } finally {
    isSubmitting.value = false
  }
}

// Delete Backup Handlers
function openDeleteModal(item) {
  selectedBackupItem.value = item
  isDeleteModalOpen.value = true
}

async function handleDeleteBackup(item) {
  isSubmitting.value = true
  try {
    await axios.delete('/admin/backups', {
      data: {
        file_name: item.file_name,
        disk: item.disk,
      },
      headers: { Accept: 'application/json' },
    })

    isDeleteModalOpen.value = false
    selectedBackupItem.value = null
    toast.success(t('backups.toast.delete_success'))
    fetchBackups(currentPage.value, selectedDisk.value)
  } catch (err) {
    if (err.response && err.response.status === 423) {
      window.location.href = '/confirm-password'
      return
    }
    const message = err.response?.data?.message || t('backups.toast.delete_failed')
    toast.error(message)
  } finally {
    isSubmitting.value = false
  }
}

// Restore Backup Handlers
function openRestoreModal(item) {
  selectedBackupItem.value = item
  isRestoreModalOpen.value = true
}

async function handleRestoreBackup({ backupItem, password, phrase }) {
  isSubmitting.value = true
  try {
    // 1. Verify password via /confirm-password first
    try {
      await axios.post('/confirm-password', { password }, {
        headers: { Accept: 'application/json' },
      })
    } catch {
      toast.error(t('backups.toast.password_incorrect'))
      isSubmitting.value = false
      return
    }

    // 2. Dispatch restore request
    const response = await axios.post('/admin/backups/restore', {
      file_name: backupItem.file_name,
      disk: backupItem.disk,
      confirm_phrase: phrase,
    }, {
      headers: { Accept: 'application/json' },
    })

    isRestoreModalOpen.value = false
    selectedBackupItem.value = null
    toast.warning(t('backups.toast.restore_started'))

    activeJob.value = {
      id: response.data.log_id,
      type: 'restore',
      status: 'running',
    }
    isRestoring.value = true
    retryCount.value = 0
    startPolling()
  } catch (err) {
    const message = err.response?.data?.message || t('backups.toast.restore_dispatch_failed')
    toast.error(message)
  } finally {
    isSubmitting.value = false
  }
}

// Download Backup
function handleDownloadBackup(item) {
  window.location.href = `/admin/backups/download?file_name=${encodeURIComponent(item.file_name)}&disk=${encodeURIComponent(item.disk)}`
}

// Recovery Poll during maintenance mode
async function checkServerHealth() {
  try {
    const res = await axios.get('/up', { timeout: 3000 })
    if (res.status === 200) {
      isRestoring.value = false
      activeJob.value = null
      toast.success(t('backups.toast.restore_completed'))
      fetchBackups(currentPage.value, selectedDisk.value)
    }
  } catch {
    retryCount.value++
    toast.info(t('backups.toast.server_recovering'))
  }
}

onMounted(() => {
  fetchBackups(1)
})

onUnmounted(() => {
  stopPolling()
})
</script>
