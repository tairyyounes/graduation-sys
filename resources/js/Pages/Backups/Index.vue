<template>
  <AppDashboardLayout
    :nav-items="navItems"
    :nav-title="$t('adminnav.dashboard')"
    :brand-subtitle="$t('common.college')"
    :user="layoutUser"
    active-page-key="backups"
  >
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

    <!-- Main Page Content -->
    <div class="space-y-6">
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
          <!-- Refresh -->
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 disabled:opacity-50 sm:text-sm"
            :disabled="loading || isJobRunning"
            @click="reloadPage"
          >
            <svg class="h-4 w-4" :class="{ 'animate-spin': loading }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            {{ $t('backups.actions.refresh') }}
          </button>

          <!-- Create Manual Backup -->
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-xl bg-teal-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 disabled:opacity-50 sm:text-sm"
            :disabled="loading || isJobRunning"
            @click="isCreateModalOpen = true"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            {{ $t('backups.actions.create') }}
          </button>
        </div>
      </div>

      <!-- Status KPI Cards -->
      <BackupStatusCard
        :status-summary="statusSummary"
        :active-job="activeJob"
        :loading="loading"
      />

      <!-- Backups Table -->
      <BackupTable
        :backups="backups.data || []"
        :pagination="backups"
        :disks="disks"
        :selected-disk="statusSummary.selected_disk"
        :loading="loading"
        :is-job-running="isJobRunning"
        @refresh="reloadPage"
        @change-disk="handleDiskChange"
        @page-change="handlePageChange"
        @open-restore="openRestoreModal"
        @open-delete="openDeleteModal"
      />

      <!-- Audit Logs Table -->
      <BackupLogsTable
        :logs="recentLogs.data || []"
      />
    </div>

    <!-- Modals -->
    <CreateBackupModal
      :is-open="isCreateModalOpen"
      :disks="disks"
      :selected-disk="statusSummary.selected_disk"
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
  </AppDashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import { useIntervalFn } from '@vueuse/core'
import axios from 'axios'
import AppDashboardLayout from '../../components/layouts/AppDashboardLayout.vue'
import BackupStatusCard from '../../components/backups/BackupStatusCard.vue'
import BackupTable from '../../components/backups/BackupTable.vue'
import CreateBackupModal from '../../components/backups/CreateBackupModal.vue'
import ConfirmDeleteModal from '../../components/backups/ConfirmDeleteModal.vue'
import RestoreModal from '../../components/backups/RestoreModal.vue'
import BackupLogsTable from '../../components/backups/BackupLogsTable.vue'

const props = defineProps({
  backups: {
    type: Object,
    required: true,
  },
  statusSummary: {
    type: Object,
    required: true,
  },
  recentLogs: {
    type: Object,
    required: true,
  },
  disks: {
    type: Array,
    default: () => ['backups'],
  },
  canManageBackups: {
    type: Boolean,
    default: true,
  },
})

const { t } = useI18n()
const toast = useToast()
const page = usePage()

const loading = ref(false)
const isSubmitting = ref(false)
const isRestoring = ref(false)
const retryCount = ref(0)

const isCreateModalOpen = ref(false)
const isDeleteModalOpen = ref(false)
const isRestoreModalOpen = ref(false)
const selectedBackupItem = ref(null)

// Current Polling Job
const activeJob = ref(props.statusSummary.active_job || null)
const isJobRunning = computed(() => {
  return activeJob.value !== null && (activeJob.value.status === 'pending' || activeJob.value.status === 'running')
})

// Layout Navigation
const allNavItems = [
  { key: 'overview', routeName: 'AdminOverview', href: '/admin/dashboard', labelKey: 'adminnav.overview', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12h7V4H4v8zm0 8h7v-6H4v6zm9 0h7V12h-7v8zm0-10h7V4h-7v6z"/></svg>' },
  { key: 'users', routeName: 'AdminUsers', href: '/admin/dashboard/users', labelKey: 'adminnav.users', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm10 8v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>' },
  { key: 'departments', routeName: 'AdminDepartments', href: '/admin/dashboard/departments', labelKey: 'adminnav.departments', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l8-4 6 3v15M9 9h.01M9 13h.01M9 17h.01M13 9h.01M13 13h.01M13 17h.01"/></svg>' },
  { key: 'activity', routeName: 'AdminActivity', href: '/admin/dashboard/activity', labelKey: 'adminnav.activity', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>' },
  { key: 'previous-proposals', routeName: 'AdminPreviousProposals', href: '/admin/dashboard/previous-proposals', labelKey: 'deptnav.previous_proposals', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
  { key: 'backups', routeName: 'AdminBackups', href: '/admin/backups', labelKey: 'adminnav.backups', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>' },
]

const navItems = computed(() => allNavItems.map(item => ({ ...item, label: t(item.labelKey) })))

const layoutUser = computed(() => {
  const u = page.props.auth?.user || {}
  const name = u.name || 'System Admin'
  const initials = name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) || 'SA'
  return {
    initials,
    name,
    email: u.email || 'admin@ctc.ly',
  }
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
        reloadPage()
      } else if (data.status === 'failed') {
        stopPolling()
        activeJob.value = null
        if (data.type === 'restore') {
          isRestoring.value = false
          toast.error(t('backups.toast.restore_failed', { error: data.error_message || '' }))
        } else {
          toast.error(t('backups.toast.backup_failed', { error: data.error_message || '' }))
        }
        reloadPage()
      }
    } catch (err) {
      // In maintenance mode during restore, server may return 503
      if (err.response && err.response.status === 503) {
        isRestoring.value = true
        retryCount.value++
      }
    }
  },
  2500,
  { immediate: false }
)

// Auto-start polling if page loaded with an ongoing job
onMounted(() => {
  if (props.statusSummary.active_job) {
    activeJob.value = props.statusSummary.active_job
    startPolling()
  }
})

function reloadPage() {
  loading.value = true
  router.reload({
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
    },
  })
}

function handleDiskChange(disk) {
  router.get('/admin/backups', { disk }, { preserveState: true, preserveScroll: true })
}

function handlePageChange(newPage) {
  router.get('/admin/backups', { page: newPage, disk: props.statusSummary.selected_disk }, { preserveState: true, preserveScroll: true })
}

// Create Backup Handler
async function handleCreateBackup(payload) {
  if (isSubmitting.value) return
  isSubmitting.value = true
  isCreateModalOpen.value = false

  try {
    const type = typeof payload === 'string' ? payload : (payload?.type || 'full')
    const disk = (typeof payload === 'object' && payload?.disk) ? payload.disk : props.statusSummary.selected_disk

    const response = await axios.post('/admin/backups', { type, disk }, {
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
    reloadPage()
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
    } catch (passwordErr) {
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
    isRestoring.value = true
    toast.warning(t('backups.toast.restore_started'))

    activeJob.value = {
      id: response.data.log_id,
      type: 'restore',
      status: 'pending',
    }
    startPolling()
  } catch (err) {
    const message = err.response?.data?.message || 'Restore initiation failed.'
    toast.error(message)
  } finally {
    isSubmitting.value = false
  }
}

// Periodic check for maintenance mode recovery during restore
async function checkServerHealth() {
  retryCount.value++
  try {
    const res = await fetch('/up', { method: 'GET' })
    if (res.ok) {
      isRestoring.value = false
      toast.success(t('backups.toast.restore_completed'))
      window.location.reload()
    }
  } catch {
    // Still in maintenance mode
  }
}
</script>
