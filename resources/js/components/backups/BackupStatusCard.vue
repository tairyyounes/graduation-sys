<template>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Card 1: Health Status -->
    <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition hover:shadow-md">
      <div class="flex items-center justify-between">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
          {{ $t('backups.card.status') }}
        </p>
        <span
          class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold"
          :class="statusSummary.is_older_than_25_hours
            ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20'
            : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20'"
        >
          <span
            class="h-1.5 w-1.5 rounded-full"
            :class="statusSummary.is_older_than_25_hours ? 'bg-amber-500' : 'bg-emerald-500'"
          ></span>
          {{ statusSummary.is_older_than_25_hours ? $t('backups.card.warning') : $t('backups.card.healthy') }}
        </span>
      </div>

      <div class="mt-3">
        <h3 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
          {{ statusSummary.last_backup_relative || $t('backups.card.never') }}
        </h3>
        <p class="mt-1 text-xs text-slate-500">
          {{ statusSummary.last_backup_date ? formatDate(statusSummary.last_backup_date) : $t('backups.card.last_backup') }}
        </p>
      </div>

      <div class="absolute -end-3 -bottom-3 text-slate-100 pointer-events-none">
        <svg class="h-20 w-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>
      </div>
    </div>

    <!-- Card 2: Total Archives -->
    <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition hover:shadow-md">
      <div class="flex items-center justify-between">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
          {{ $t('backups.card.total_archives') }}
        </p>
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-teal-600">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
          </svg>
        </div>
      </div>
      <div class="mt-3">
        <h3 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
          {{ statusSummary.total_backups }}
        </h3>
        <p class="mt-1 text-xs text-slate-500">
          {{ $t('backups.card.destination_disk') }}: <span class="font-semibold text-slate-700 font-mono">{{ statusSummary.selected_disk }}</span>
        </p>
      </div>
    </div>

    <!-- Card 3: Storage Used -->
    <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition hover:shadow-md">
      <div class="flex items-center justify-between">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
          {{ $t('backups.card.storage_used') }}
        </p>
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
          </svg>
        </div>
      </div>
      <div class="mt-3">
        <h3 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
          {{ statusSummary.formatted_total_size || '0 B' }}
        </h3>
        <p class="mt-1 text-xs text-slate-500">
          {{ (statusSummary.total_size_bytes / 1048576).toFixed(1) }} MB
        </p>
      </div>
    </div>

    <!-- Card 4: Active Job / System Engine -->
    <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition hover:shadow-md">
      <div class="flex items-center justify-between">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
          {{ activeJob ? $t('backups.card.running_job') : $t('backups.card.job_type') }}
        </p>
        <div
          class="flex h-10 w-10 items-center justify-center rounded-xl"
          :class="activeJob ? 'bg-amber-100 text-amber-700 animate-pulse' : 'bg-slate-100 text-slate-600'"
        >
          <svg
            class="h-5 w-5"
            :class="{ 'animate-spin': activeJob }"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </div>
      </div>
      <div class="mt-3">
        <div v-if="activeJob" class="space-y-1">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center rounded-md bg-amber-100 px-2 py-0.5 text-xs font-bold uppercase tracking-wider text-amber-800">
              {{ activeJob.type }}
            </span>
            <span class="text-xs font-medium text-slate-600 capitalize">{{ activeJob.status }}</span>
          </div>
          <p class="text-xs text-slate-500">
            Job #{{ activeJob.id }}
          </p>
        </div>
        <div v-else>
          <h3 class="text-lg font-bold text-slate-800">
            PostgreSQL + Uploads
          </h3>
          <p class="mt-1 text-xs text-slate-500">
            Spatie Backup 10 & Restore
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import dayjs from 'dayjs'

const props = defineProps({
  statusSummary: {
    type: Object,
    required: true,
  },
  activeJob: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

function formatDate(dateStr) {
  if (!dateStr) return ''
  return dayjs(dateStr).format('YYYY-MM-DD HH:mm')
}
</script>
