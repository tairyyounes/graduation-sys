<template>
  <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
    <!-- Header -->
    <div class="border-b border-slate-100 p-4 sm:px-6">
      <h3 class="text-base font-bold text-slate-900">
        {{ $t('backups.logs.title') }}
      </h3>
      <p class="text-xs text-slate-500">
        {{ $t('backups.logs.subtitle') }}
      </p>
    </div>

    <!-- Empty State -->
    <div v-if="!logs || logs.length === 0" class="py-12 text-center text-xs text-slate-400">
      {{ $t('backups.logs.empty') }}
    </div>

    <!-- Logs Table -->
    <div v-else class="overflow-x-auto">
      <table class="min-w-full text-start text-xs">
        <thead class="bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
          <tr>
            <th class="px-6 py-3.5 text-start">{{ $t('backups.logs.type') }}</th>
            <th class="px-6 py-3.5 text-start">{{ $t('backups.logs.status') }}</th>
            <th class="px-6 py-3.5 text-start">{{ $t('backups.logs.details') }}</th>
            <th class="px-6 py-3.5 text-start">{{ $t('backups.logs.operator') }}</th>
            <th class="px-6 py-3.5 text-start">{{ $t('backups.logs.time') }}</th>
            <th class="px-6 py-3.5 text-end">{{ $t('backups.logs.duration') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-slate-700">
          <tr v-for="log in logs" :key="log.id" class="transition hover:bg-slate-50/70">
            <!-- Type -->
            <td class="px-6 py-3.5 whitespace-nowrap">
              <span
                class="inline-flex items-center rounded-lg px-2 py-0.5 font-bold uppercase tracking-wider text-[10px]"
                :class="getTypeBadgeClass(log.type)"
              >
                {{ $t(`backups.type_badge.${log.type}`) || log.type }}
              </span>
            </td>

            <!-- Status -->
            <td class="px-6 py-3.5 whitespace-nowrap">
              <span
                class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 font-semibold text-[11px]"
                :class="getStatusBadgeClass(log.status)"
              >
                <span
                  class="h-1.5 w-1.5 rounded-full"
                  :class="getStatusDotClass(log.status)"
                ></span>
                {{ $t(`backups.status_badge.${log.status}`) || log.status }}
              </span>
            </td>

            <!-- Details (File name, size, error) -->
            <td class="px-6 py-3.5">
              <div class="max-w-xs sm:max-w-md">
                <p v-if="log.file_name" class="font-mono text-slate-800 truncate" :title="log.file_name">
                  {{ log.file_name }}
                  <span v-if="log.size" class="text-slate-400 ms-1">({{ formatBytes(log.size) }})</span>
                </p>
                <p v-if="log.error_message" class="mt-0.5 text-[11px] text-rose-600 line-clamp-2" :title="log.error_message">
                  {{ log.error_message }}
                </p>
                <p v-if="!log.file_name && !log.error_message" class="text-slate-400 italic">
                  {{ log.disk ? `Disk: ${log.disk}` : '-' }}
                </p>
              </div>
            </td>

            <!-- Operator -->
            <td class="px-6 py-3.5 whitespace-nowrap">
              <span v-if="log.triggered_by && log.triggered_by.name" class="font-semibold text-slate-800">
                {{ log.triggered_by.name }}
              </span>
              <span v-else class="inline-flex items-center rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500">
                System / Scheduler
              </span>
            </td>

            <!-- Timestamp -->
            <td class="px-6 py-3.5 whitespace-nowrap text-slate-500">
              {{ formatDateTime(log.started_at || log.created_at) }}
            </td>

            <!-- Duration -->
            <td class="px-6 py-3.5 text-end whitespace-nowrap font-mono text-slate-500">
              {{ log.duration_in_seconds != null ? `${log.duration_in_seconds}s` : '-' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import dayjs from 'dayjs'

defineProps({
  logs: {
    type: Array,
    required: true,
  },
})

function formatDateTime(dateStr) {
  if (!dateStr) return '-'
  return dayjs(dateStr).format('YYYY-MM-DD HH:mm:ss')
}

function formatBytes(bytes) {
  if (!bytes) return ''
  const units = ['B', 'KB', 'MB', 'GB']
  const power = bytes > 0 ? Math.floor(Math.log(bytes) / Math.log(1024)) : 0
  return `${(bytes / Math.pow(1024, power)).toFixed(1)} ${units[power] || 'B'}`
}

function getTypeBadgeClass(type) {
  switch (type) {
    case 'backup':
      return 'bg-teal-50 text-teal-700 ring-1 ring-teal-600/20'
    case 'restore':
      return 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20'
    case 'cleanup':
      return 'bg-sky-50 text-sky-700 ring-1 ring-sky-600/20'
    case 'download':
      return 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-600/20'
    case 'delete':
      return 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20'
    default:
      return 'bg-slate-100 text-slate-700'
  }
}

function getStatusBadgeClass(status) {
  switch (status) {
    case 'success':
      return 'bg-emerald-50 text-emerald-700'
    case 'failed':
      return 'bg-rose-50 text-rose-700'
    case 'running':
      return 'bg-amber-50 text-amber-700'
    case 'pending':
    default:
      return 'bg-slate-100 text-slate-600'
  }
}

function getStatusDotClass(status) {
  switch (status) {
    case 'success':
      return 'bg-emerald-500'
    case 'failed':
      return 'bg-rose-500'
    case 'running':
      return 'bg-amber-500 animate-ping'
    case 'pending':
    default:
      return 'bg-slate-400'
  }
}
</script>
