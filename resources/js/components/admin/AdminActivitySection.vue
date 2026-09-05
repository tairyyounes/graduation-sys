<template>
  <section class="space-y-6">
    <!-- Top Header & Global Actions -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
          {{ $t('admin.activity.title') }}
        </h1>
        <p class="mt-1 text-sm text-slate-500">
          {{ $t('admin.activity.subtitle') }}
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-400 sm:text-sm"
          :disabled="exporting"
          @click="exportCsv"
        >
          <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          {{ $t('admin.activity.export_csv') }}
        </button>

        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 disabled:opacity-60 sm:text-sm"
          :disabled="loading"
          @click="loadLogs(currentPage)"
        >
          <svg
            class="h-4 w-4 transition-transform"
            :class="{ 'animate-spin': loading }"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          {{ $t('admin.activity.refresh') }}
        </button>
      </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <!-- Total Events -->
      <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">{{ $t('admin.activity.kpi.total_events') }}</p>
            <h3 class="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">{{ stats.total.toLocaleString() }}</h3>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-teal-50 text-teal-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
        </div>
        <p class="mt-3 text-xs text-slate-500">{{ $t('admin.activity.kpi.total_events_desc') }}</p>
      </div>

      <!-- Today's Activity -->
      <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">{{ $t('admin.activity.kpi.today_actions') }}</p>
            <h3 class="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">{{ stats.today.toLocaleString() }}</h3>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <p class="mt-3 text-xs text-slate-500">{{ $t('admin.activity.kpi.today_actions_desc') }}</p>
      </div>

      <!-- Active Operators -->
      <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">{{ $t('admin.activity.kpi.active_users') }}</p>
            <h3 class="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">{{ stats.active_actors_today.toLocaleString() }}</h3>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
        </div>
        <p class="mt-3 text-xs text-slate-500">{{ $t('admin.activity.kpi.active_users_desc') }}</p>
      </div>

      <!-- Admin Actions -->
      <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">{{ $t('admin.activity.kpi.admin_actions') }}</p>
            <h3 class="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">{{ stats.admin_actions.toLocaleString() }}</h3>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          </div>
        </div>
        <p class="mt-3 text-xs text-slate-500">{{ $t('admin.activity.kpi.admin_actions_desc') }}</p>
      </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
      <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
        <!-- Search Input -->
        <div class="relative md:col-span-4">
          <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-slate-400">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <input
            v-model="filters.search"
            type="text"
            :placeholder="$t('admin.activity.search_ph')"
            class="w-full rounded-xl border border-slate-300 bg-white py-2 ps-10 pe-3 text-sm placeholder:text-slate-400 outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20"
            @input="onSearchInput"
          />
          <button
            v-if="filters.search"
            class="absolute inset-y-0 end-0 flex items-center pe-3 text-slate-400 hover:text-slate-600"
            @click="clearSearch"
          >
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>

        <!-- Category Dropdown -->
        <div class="md:col-span-3">
          <select
            v-model="filters.category"
            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20"
            @change="loadLogs(1)"
          >
            <option value="all">{{ $t('admin.activity.filter_all') }}</option>
            <option value="create">{{ $t('admin.activity.filter_create') }}</option>
            <option value="update">{{ $t('admin.activity.filter_update') }}</option>
            <option value="delete">{{ $t('admin.activity.filter_delete') }}</option>
            <option value="review">{{ $t('admin.activity.filter_review') }}</option>
            <option value="proposal">{{ $t('admin.activity.filter_proposal') }}</option>
          </select>
        </div>

        <!-- Entity Dropdown -->
        <div class="md:col-span-3">
          <select
            v-model="filters.entity"
            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20"
            @change="loadLogs(1)"
          >
            <option value="all">{{ $t('admin.activity.entity_all') }}</option>
            <option value="Proposal">{{ $t('admin.activity.entity_proposal') }}</option>
            <option value="User">{{ $t('admin.activity.entity_user') }}</option>
            <option value="Student">{{ $t('admin.activity.entity_student') }}</option>
            <option value="Department">{{ $t('admin.activity.entity_department') }}</option>
            <option value="ReviewCommittee">{{ $t('admin.activity.entity_committee') }}</option>
          </select>
        </div>

        <!-- Date Range Filter -->
        <div class="md:col-span-2">
          <select
            v-model="filters.date_range"
            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20"
            @change="loadLogs(1)"
          >
            <option value="all">{{ $t('admin.activity.range_all') }}</option>
            <option value="today">{{ $t('admin.activity.range_today') }}</option>
            <option value="7days">{{ $t('admin.activity.range_7days') }}</option>
            <option value="30days">{{ $t('admin.activity.range_30days') }}</option>
          </select>
        </div>
      </div>

      <!-- Active Filters Bar -->
      <div v-if="hasActiveFilters" class="mt-3 flex flex-wrap items-center gap-2 pt-3 border-t border-slate-100">
        <span class="text-xs text-slate-500">{{ $t('admin.activity.showing_records', { from: pagination.from || 0, to: pagination.to || 0, total: pagination.total || 0 }) }}</span>
        <button
          type="button"
          class="ms-auto inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 transition hover:bg-slate-200"
          @click="resetFilters"
        >
          <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
          {{ $t('admin.activity.clear_filters') }}
        </button>
      </div>
    </div>

    <!-- Skeleton Loading -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 5" :key="i" class="h-16 w-full animate-pulse rounded-2xl bg-slate-100"></div>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="activityLogs.length === 0"
      class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white py-16 px-4 text-center shadow-sm"
    >
      <div class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-50 text-slate-400 mb-4 ring-8 ring-slate-50/50">
        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>
      <h3 class="text-base font-semibold text-slate-900">{{ $t('admin.activity.none') }}</h3>
      <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $t('admin.activity.none_desc') }}</p>
      <button
        v-if="hasActiveFilters"
        type="button"
        class="mt-4 rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
        @click="resetFilters"
      >
        {{ $t('admin.activity.clear_filters') }}
      </button>
    </div>

    <!-- Rich Interactive Activity Table (Desktop) -->
    <div v-else class="space-y-4">
      <div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:block">
        <table class="min-w-full text-start text-sm">
          <thead class="bg-slate-50/80 text-xs uppercase tracking-wider text-slate-500 border-b border-slate-200/80">
            <tr>
              <th class="px-6 py-4 font-semibold text-start">{{ $t('admin.activity.actor') }}</th>
              <th class="px-6 py-4 font-semibold text-start">{{ $t('admin.activity.action') }}</th>
              <th class="px-6 py-4 font-semibold text-start">{{ $t('admin.activity.target') }}</th>
              <th class="px-6 py-4 font-semibold text-start">{{ $t('admin.activity.time') }}</th>
              <th class="px-6 py-4 font-semibold text-end">{{ $t('admin.activity.details') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="log in activityLogs"
              :key="log.id"
              class="group transition hover:bg-slate-50/70"
            >
              <!-- Actor Column -->
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl font-bold text-xs shadow-xs"
                    :class="getAvatarColorClass(log.actor.role)"
                  >
                    {{ log.actor.initials }}
                  </div>
                  <div class="min-w-0">
                    <div class="flex items-center gap-2">
                      <p class="font-semibold text-slate-900 truncate">{{ log.actor.name }}</p>
                      <span
                        class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider"
                        :class="getRoleBadgeClass(log.actor.role)"
                      >
                        {{ formatRoleName(log.actor.role) }}
                      </span>
                    </div>
                    <p v-if="log.actor.email" class="text-xs text-slate-400 truncate">{{ log.actor.email }}</p>
                  </div>
                </div>
              </td>

              <!-- Action Column -->
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <span
                    class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                    :class="getCategoryBadgeClass(log.category)"
                  >
                    <component :is="getCategoryIcon(log.category)" class="h-3.5 w-3.5 shrink-0" />
                    {{ log.action }}
                  </span>
                </div>
                <p class="mt-1 text-xs text-slate-500 max-w-md line-clamp-1">{{ log.description }}</p>
              </td>

              <!-- Target Column -->
              <td class="px-6 py-4">
                <div class="flex flex-col items-start gap-1">
                  <span
                    v-if="log.target.type && log.target.type !== 'General'"
                    class="inline-flex items-center rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600 uppercase tracking-wider"
                  >
                    {{ log.target.type }}
                  </span>
                  <p class="font-medium text-slate-800 text-xs max-w-xs truncate" :title="log.target.name">
                    {{ log.target.name }}
                  </p>
                </div>
              </td>

              <!-- Timestamp Column -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex flex-col">
                  <span class="font-medium text-slate-700 text-xs">{{ log.relative_time }}</span>
                  <span class="text-[11px] text-slate-400">{{ log.formatted_time }}</span>
                </div>
              </td>

              <!-- Inspect Action Column -->
              <td class="px-6 py-4 text-end whitespace-nowrap">
                <button
                  type="button"
                  class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-400"
                  @click="openInspectModal(log)"
                >
                  <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  {{ $t('admin.activity.details') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Table Footer / Pagination -->
        <div class="flex flex-col gap-3 border-t border-slate-200 bg-white px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-center gap-3 text-xs text-slate-500">
            <span>{{ $t('admin.activity.showing_records', { from: pagination.from || 0, to: pagination.to || 0, total: pagination.total || 0 }) }}</span>
            <span class="text-slate-300">|</span>
            <select
              v-model="perPage"
              class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs text-slate-600 outline-none focus:ring-1 focus:ring-teal-500"
              @change="loadLogs(1)"
            >
              <option :value="15">{{ $t('admin.activity.per_page', { count: 15 }) }}</option>
              <option :value="25">{{ $t('admin.activity.per_page', { count: 25 }) }}</option>
              <option :value="50">{{ $t('admin.activity.per_page', { count: 50 }) }}</option>
              <option :value="100">{{ $t('admin.activity.per_page', { count: 100 }) }}</option>
            </select>
          </div>

          <div v-if="pagination.last_page > 1" class="flex items-center gap-2">
            <button
              class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed"
              :disabled="currentPage === 1 || loading"
              @click="loadLogs(currentPage - 1)"
            >
              <svg class="h-3.5 w-3.5 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
              {{ $t('common.previous') }}
            </button>

            <span class="px-2 text-xs font-semibold text-slate-600">
              {{ $t('admin.activity.page_of', { current: currentPage, total: pagination.last_page }) }}
            </span>

            <button
              class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed"
              :disabled="currentPage === pagination.last_page || loading"
              @click="loadLogs(currentPage + 1)"
            >
              {{ $t('common.next') }}
              <svg class="h-3.5 w-3.5 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile Activity Cards View -->
      <div class="grid gap-3 lg:hidden">
        <article
          v-for="log in activityLogs"
          :key="`mobile-log-${log.id}`"
          class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-2.5">
              <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-xs font-bold shadow-xs"
                :class="getAvatarColorClass(log.actor.role)"
              >
                {{ log.actor.initials }}
              </div>
              <div>
                <p class="font-semibold text-sm text-slate-900">{{ log.actor.name }}</p>
                <span
                  class="inline-flex items-center rounded-md px-1.5 py-0.2 text-[10px] font-semibold uppercase tracking-wider"
                  :class="getRoleBadgeClass(log.actor.role)"
                >
                  {{ formatRoleName(log.actor.role) }}
                </span>
              </div>
            </div>

            <span class="text-[11px] text-slate-400 whitespace-nowrap">{{ log.relative_time }}</span>
          </div>

          <div class="mt-3 flex items-center gap-2">
            <span
              class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
              :class="getCategoryBadgeClass(log.category)"
            >
              <component :is="getCategoryIcon(log.category)" class="h-3.5 w-3.5 shrink-0" />
              {{ log.action }}
            </span>
          </div>

          <p class="mt-2 text-xs text-slate-600">{{ log.description }}</p>

          <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3">
            <div class="flex items-center gap-1 text-xs text-slate-500">
              <span class="font-medium text-slate-700">{{ log.target.type }}:</span>
              <span class="truncate max-w-[150px]">{{ log.target.name }}</span>
            </div>

            <button
              type="button"
              class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-xs font-semibold text-slate-700"
              @click="openInspectModal(log)"
            >
              {{ $t('admin.activity.details') }}
            </button>
          </div>
        </article>

        <!-- Mobile Pagination -->
        <div v-if="pagination.last_page > 1" class="flex items-center justify-between pt-2">
          <button
            class="rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs transition disabled:opacity-40"
            :disabled="currentPage === 1 || loading"
            @click="loadLogs(currentPage - 1)"
          >
            {{ $t('common.previous') }}
          </button>
          <span class="text-xs font-medium text-slate-500">{{ currentPage }} / {{ pagination.last_page }}</span>
          <button
            class="rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs transition disabled:opacity-40"
            :disabled="currentPage === pagination.last_page || loading"
            @click="loadLogs(currentPage + 1)"
          >
            {{ $t('common.next') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Log Inspection Modal -->
    <div
      v-if="selectedLog"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs transition-opacity"
      @click.self="closeInspectModal"
    >
      <div class="relative w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl transition-all">
        <!-- Modal Header -->
        <div class="mb-5 flex items-start justify-between border-b border-slate-100 pb-4">
          <div>
            <div class="flex items-center gap-2">
              <span
                class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                :class="getCategoryBadgeClass(selectedLog.category)"
              >
                <component :is="getCategoryIcon(selectedLog.category)" class="h-3.5 w-3.5 shrink-0" />
                {{ selectedLog.action }}
              </span>
              <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-mono text-slate-600">#{{ selectedLog.id }}</span>
            </div>
            <h3 class="mt-2 text-lg font-bold text-slate-900">{{ $t('admin.activity.modal.title') }}</h3>
            <p class="text-xs text-slate-500">{{ $t('admin.activity.modal.subtitle') }}</p>
          </div>

          <button
            type="button"
            class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
            @click="closeInspectModal"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Modal Body Content -->
        <div class="space-y-4 max-h-[70vh] overflow-y-auto pe-1">
          <!-- Operator Info -->
          <div class="rounded-xl bg-slate-50 p-3.5 border border-slate-100">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">{{ $t('admin.activity.modal.operator') }}</p>
            <div class="flex items-center gap-3">
              <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-xs font-bold"
                :class="getAvatarColorClass(selectedLog.actor.role)"
              >
                {{ selectedLog.actor.initials }}
              </div>
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                  <p class="font-bold text-sm text-slate-900">{{ selectedLog.actor.name }}</p>
                  <span
                    class="rounded-md px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider"
                    :class="getRoleBadgeClass(selectedLog.actor.role)"
                  >
                    {{ formatRoleName(selectedLog.actor.role) }}
                  </span>
                </div>
                <p v-if="selectedLog.actor.email" class="text-xs text-slate-500">{{ selectedLog.actor.email }}</p>
              </div>
            </div>
          </div>

          <!-- Event Details Grid -->
          <div class="grid grid-cols-2 gap-3">
            <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3">
              <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">{{ $t('admin.activity.modal.target_type') }}</p>
              <p class="mt-1 text-sm font-semibold text-slate-800">{{ selectedLog.target.type }}</p>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3">
              <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">{{ $t('admin.activity.modal.target_name') }}</p>
              <p class="mt-1 text-sm font-semibold text-slate-800 truncate" :title="selectedLog.target.name">{{ selectedLog.target.name }}</p>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3">
              <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">{{ $t('admin.activity.modal.timestamp') }}</p>
              <p class="mt-1 text-xs font-medium text-slate-800">{{ selectedLog.formatted_time }}</p>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3">
              <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">{{ $t('admin.activity.modal.category') }}</p>
              <p class="mt-1 text-xs font-bold uppercase tracking-wider text-slate-700">{{ selectedLog.category }}</p>
            </div>
          </div>

          <!-- Description -->
          <div>
            <p class="text-xs font-semibold text-slate-700 mb-1">{{ $t('admin.activity.modal.description') }}</p>
            <p class="rounded-xl border border-slate-200 bg-white p-3 text-xs leading-relaxed text-slate-700">
              {{ selectedLog.description }}
            </p>
          </div>

          <!-- Properties / Payload -->
          <div>
            <p class="text-xs font-semibold text-slate-700 mb-1">{{ $t('admin.activity.modal.properties') }}</p>
            <div
              v-if="selectedLog.properties && Object.keys(selectedLog.properties).length > 0"
              class="rounded-xl border border-slate-800 bg-slate-950 p-3.5 text-xs font-mono text-emerald-400 overflow-x-auto"
            >
              <pre class="whitespace-pre-wrap">{{ JSON.stringify(selectedLog.properties, null, 2) }}</pre>
            </div>
            <p v-else class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-center text-xs text-slate-400">
              {{ $t('admin.activity.modal.no_properties') }}
            </p>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="mt-6 flex justify-end border-t border-slate-100 pt-4">
          <button
            type="button"
            class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500"
            @click="closeInspectModal"
          >
            {{ $t('admin.activity.modal.close') }}
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, reactive, computed, onMounted, h } from 'vue'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'

const toast = useToast()
const { t } = useI18n()

// Data State
const activityLogs = ref([])
const loading = ref(true)
const exporting = ref(false)
const selectedLog = ref(null)

const stats = reactive({
  total: 0,
  today: 0,
  active_actors_today: 0,
  admin_actions: 0,
})

const currentPage = ref(1)
const perPage = ref(20)
const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
  from: 0,
  to: 0,
})

const filters = reactive({
  search: '',
  category: 'all',
  entity: 'all',
  date_range: 'all',
})

let searchDebounceTimer = null

const hasActiveFilters = computed(() => {
  return filters.search !== '' || filters.category !== 'all' || filters.entity !== 'all' || filters.date_range !== 'all'
})

// Fetch Activity Logs
const loadLogs = async (page = 1) => {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page: String(page),
      per_page: String(perPage.value),
    })

    if (filters.search) params.append('search', filters.search)
    if (filters.category !== 'all') params.append('category', filters.category)
    if (filters.entity !== 'all') params.append('entity', filters.entity)
    if (filters.date_range !== 'all') params.append('date_range', filters.date_range)

    const response = await fetch(`/admin/activity-logs?${params.toString()}`, {
      headers: { Accept: 'application/json' },
    })

    if (!response.ok) {
      throw new Error(t('admin.activity.toast.load_failed'))
    }

    const data = await response.json()
    activityLogs.value = data.logs ?? []

    if (data.stats) {
      stats.total = data.stats.total || 0
      stats.today = data.stats.today || 0
      stats.active_actors_today = data.stats.active_actors_today || 0
      stats.admin_actions = data.stats.admin_actions || 0
    }

    if (data.pagination) {
      currentPage.value = data.pagination.current_page
      pagination.current_page = data.pagination.current_page
      pagination.last_page = data.pagination.last_page
      pagination.total = data.pagination.total
      pagination.from = data.pagination.from
      pagination.to = data.pagination.to
    }
  } catch (error) {
    toast.error(error.message || t('admin.activity.toast.load_failed'))
  } finally {
    loading.value = false
  }
}

// Search debounce handler
const onSearchInput = () => {
  clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    loadLogs(1)
  }, 350)
}

const clearSearch = () => {
  filters.search = ''
  loadLogs(1)
}

const resetFilters = () => {
  filters.search = ''
  filters.category = 'all'
  filters.entity = 'all'
  filters.date_range = 'all'
  loadLogs(1)
}

// Modal inspection
const openInspectModal = (log) => {
  selectedLog.value = log
}

const closeInspectModal = () => {
  selectedLog.value = null
}

// CSV Exporter (UTF-8 with BOM for Arabic support)
const exportCsv = () => {
  if (activityLogs.value.length === 0) return

  exporting.value = true
  try {
    const headers = [
      'Log ID',
      'Timestamp',
      'Operator Name',
      'Operator Email',
      'Operator Role',
      'Action Category',
      'Action Name',
      'Target Type',
      'Target Name',
      'Full Description',
    ]

    const rows = activityLogs.value.map((log) => [
      log.id,
      log.formatted_time,
      `"${(log.actor.name || '').replace(/"/g, '""')}"`,
      `"${(log.actor.email || '').replace(/"/g, '""')}"`,
      log.actor.role || 'system',
      log.category,
      `"${(log.action || '').replace(/"/g, '""')}"`,
      log.target.type || 'General',
      `"${(log.target.name || '').replace(/"/g, '""')}"`,
      `"${(log.description || '').replace(/"/g, '""')}"`,
    ])

    const csvContent = '\uFEFF' + [headers.join(','), ...rows.map((e) => e.join(','))].join('\n')
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.setAttribute('href', url)
    link.setAttribute('download', `activity_logs_${new Date().toISOString().slice(0, 10)}.csv`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    toast.success(t('admin.activity.toast.exported'))
  } catch (err) {
    toast.error('Failed to export CSV')
  } finally {
    exporting.value = false
  }
}

// Visual Helpers
const getAvatarColorClass = (role) => {
  switch (role) {
    case 'admin':
      return 'bg-amber-100 text-amber-800'
    case 'department_head':
      return 'bg-indigo-100 text-indigo-800'
    case 'department_member':
      return 'bg-teal-100 text-teal-800'
    case 'student':
      return 'bg-sky-100 text-sky-800'
    default:
      return 'bg-slate-100 text-slate-700'
  }
}

const getRoleBadgeClass = (role) => {
  switch (role) {
    case 'admin':
      return 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20'
    case 'department_head':
      return 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-600/20'
    case 'department_member':
      return 'bg-teal-50 text-teal-700 ring-1 ring-teal-600/20'
    case 'student':
      return 'bg-sky-50 text-sky-700 ring-1 ring-sky-600/20'
    default:
      return 'bg-slate-100 text-slate-600 ring-1 ring-slate-400/20'
  }
}

const formatRoleName = (role) => {
  switch (role) {
    case 'admin':
      return 'Admin'
    case 'department_head':
      return 'Dept Head'
    case 'department_member':
      return 'Faculty'
    case 'student':
      return 'Student'
    default:
      return 'System'
  }
}

const getCategoryBadgeClass = (category) => {
  switch (category) {
    case 'create':
      return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
    case 'update':
      return 'bg-blue-50 text-blue-700 ring-blue-600/20'
    case 'delete':
      return 'bg-rose-50 text-rose-700 ring-rose-600/20'
    case 'review':
      return 'bg-purple-50 text-purple-700 ring-purple-600/20'
    case 'proposal':
      return 'bg-amber-50 text-amber-700 ring-amber-600/20'
    default:
      return 'bg-slate-50 text-slate-700 ring-slate-600/20'
  }
}

const getCategoryIcon = (category) => {
  switch (category) {
    case 'create':
      return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
        h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', strokeWidth: '2', d: 'M12 4v16m8-8H4' }),
      ])
    case 'update':
      return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
        h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', strokeWidth: '2', d: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' }),
      ])
    case 'delete':
      return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
        h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', strokeWidth: '2', d: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16' }),
      ])
    case 'review':
      return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
        h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', strokeWidth: '2', d: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' }),
      ])
    case 'proposal':
      return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
        h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', strokeWidth: '2', d: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' }),
      ])
    default:
      return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
        h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', strokeWidth: '2', d: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' }),
      ])
  }
}

onMounted(() => {
  loadLogs()
})
</script>
