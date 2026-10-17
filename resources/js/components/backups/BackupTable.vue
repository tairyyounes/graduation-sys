<template>
  <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
    <!-- Header with Disk Selector & Refresh -->
    <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
      <div>
        <h2 class="text-base font-bold text-slate-900">
          {{ $t('backups.table.file_name') }}
        </h2>
        <p class="text-xs text-slate-500">
          {{ $t('admin.activity.showing_records', { from: pagination.from || 0, to: pagination.to || 0, total: pagination.total || 0 }) }}
        </p>
      </div>

      <div class="flex items-center gap-3">
        <!-- Disk Selector if multiple disks configured -->
        <div v-if="disks.length > 1" class="flex items-center gap-2">
          <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $t('backups.table.disk') }}:</label>
          <select
            :value="selectedDisk"
            class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 outline-none transition focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
            @change="$emit('change-disk', $event.target.value)"
          >
            <option v-for="d in disks" :key="d" :value="d">{{ d }}</option>
          </select>
        </div>

        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 disabled:opacity-50"
          :disabled="loading || isJobRunning"
          @click="$emit('refresh')"
        >
          <svg class="h-3.5 w-3.5" :class="{ 'animate-spin': loading }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          {{ $t('backups.actions.refresh') }}
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-if="!backups || backups.length === 0"
      class="flex flex-col items-center justify-center py-16 px-4 text-center"
    >
      <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 mb-3">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
        </svg>
      </div>
      <h3 class="text-sm font-bold text-slate-900">{{ $t('backups.table.empty') }}</h3>
      <p class="mt-1 max-w-sm text-xs text-slate-500">{{ $t('backups.table.empty_desc') }}</p>
    </div>

    <!-- Table of Backups -->
    <div v-else class="overflow-x-auto">
      <table class="min-w-full text-start text-sm">
        <thead class="bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
          <tr>
            <th class="px-6 py-3.5 text-start">{{ $t('backups.table.file_name') }}</th>
            <th class="px-6 py-3.5 text-start">{{ $t('backups.table.size') }}</th>
            <th class="px-6 py-3.5 text-start">{{ $t('backups.table.date') }}</th>
            <th class="px-6 py-3.5 text-start">{{ $t('backups.table.disk') }}</th>
            <th class="px-6 py-3.5 text-end">{{ $t('backups.table.actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-slate-700">
          <tr
            v-for="item in backups"
            :key="item.path"
            class="transition hover:bg-slate-50/70"
          >
            <!-- File Name -->
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                  <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <div class="min-w-0">
                  <p class="font-mono text-xs font-bold text-slate-900 truncate" :title="item.file_name">
                    {{ item.file_name }}
                  </p>
                  <span class="text-[11px] text-slate-400 font-sans">{{ item.relative_date }}</span>
                </div>
              </div>
            </td>

            <!-- Size -->
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold text-slate-700">
                {{ item.formatted_size }}
              </span>
            </td>

            <!-- Date -->
            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600">
              <div class="flex flex-col">
                <span class="font-medium text-slate-800">{{ formatTimestamp(item.timestamp) }}</span>
                <span class="text-[11px] text-slate-400 font-mono">{{ item.age_hours }}h ago</span>
              </div>
            </td>

            <!-- Disk -->
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="inline-flex items-center rounded-lg bg-teal-50 px-2 py-0.5 text-xs font-semibold text-teal-700 ring-1 ring-teal-600/20">
                {{ item.disk }}
              </span>
            </td>

            <!-- Actions -->
            <td class="px-6 py-4 text-end whitespace-nowrap">
              <div class="flex items-center justify-end gap-1.5">
                <!-- Download (Normal protected anchor per requirements) -->
                <a
                  :href="`/admin/backups/download?file_name=${encodeURIComponent(item.file_name)}&disk=${encodeURIComponent(item.disk)}`"
                  class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-300"
                  :title="$t('backups.actions.download')"
                >
                  <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                  </svg>
                  <span class="hidden sm:inline">{{ $t('backups.actions.download') }}</span>
                </a>

                <!-- Restore Button -->
                <button
                  type="button"
                  class="inline-flex items-center gap-1 rounded-xl border border-rose-200 bg-rose-50/60 px-2.5 py-1.5 text-xs font-semibold text-rose-700 shadow-2xs transition hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-400 disabled:opacity-40 disabled:cursor-not-allowed"
                  :disabled="isJobRunning"
                  @click="triggerRestore(item)"
                >
                  <svg class="h-3.5 w-3.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  </svg>
                  <span class="hidden sm:inline">{{ $t('backups.actions.restore') }}</span>
                </button>

                <!-- Delete Button -->
                <button
                  type="button"
                  class="inline-flex items-center justify-center rounded-xl p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-rose-600 focus:outline-none focus:ring-2 focus:ring-slate-300 disabled:opacity-40"
                  :disabled="isJobRunning"
                  :title="$t('backups.actions.delete')"
                  @click="triggerDelete(item)"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination Footer -->
    <div
      v-if="pagination.last_page > 1"
      class="flex items-center justify-between border-t border-slate-100 bg-white px-6 py-3 text-xs"
    >
      <button
        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1 font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 disabled:opacity-40"
        :disabled="pagination.current_page === 1 || loading"
        @click="$emit('page-change', pagination.current_page - 1)"
      >
        {{ $t('common.previous') }}
      </button>

      <span class="font-medium text-slate-500">
        {{ pagination.current_page }} / {{ pagination.last_page }}
      </span>

      <button
        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1 font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 disabled:opacity-40"
        :disabled="pagination.current_page === pagination.last_page || loading"
        @click="$emit('page-change', pagination.current_page + 1)"
      >
        {{ $t('common.next') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import dayjs from 'dayjs'

const props = defineProps({
  backups: {
    type: Array,
    required: true,
  },
  pagination: {
    type: Object,
    required: true,
  },
  disks: {
    type: Array,
    default: () => ['backups'],
  },
  selectedDisk: {
    type: String,
    default: 'backups',
  },
  loading: {
    type: Boolean,
    default: false,
  },
  isJobRunning: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['refresh', 'change-disk', 'page-change', 'open-restore', 'restore', 'open-delete', 'delete'])

function triggerRestore(item) {
  emit('open-restore', item)
  emit('restore', item)
}

function triggerDelete(item) {
  emit('open-delete', item)
  emit('delete', item)
}

function formatTimestamp(ts) {
  if (!ts) return ''
  return dayjs.unix(ts).format('YYYY-MM-DD HH:mm:ss')
}
</script>
