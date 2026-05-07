<template>
  <AppDashboardLayout
    :nav-items="navItems"
    nav-title="Admin dashboard"
    :user="{ initials: 'SA', name: 'System Admin', email: 'admin@ctc.ly' }"
  >
    <template #footerActions>
      <div class="flex items-center justify-between">
        <button
          type="button"
          class="rounded-md px-2 py-1 text-sm text-slate-700 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-400"
        >
          العربية
        </button>
        <button
          type="button"
          class="rounded-md p-1.5 text-slate-700 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-400"
        >
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2v-1" />
          </svg>
        </button>
      </div>
    </template>
  </AppDashboardLayout>
</template>

<script setup>
import AppDashboardLayout from './layouts/AppDashboardLayout.vue'

const navItems = [
  { key: 'overview', routeName: 'AdminOverview', label: 'Overview', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12h7V4H4v8zm0 8h7v-6H4v6zm9 0h7V12h-7v8zm0-10h7V4h-7v6z"/></svg>' },
  { key: 'users', routeName: 'AdminUsers', label: 'Users', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm10 8v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>' },
  { key: 'departments', routeName: 'AdminDepartments', label: 'Departments', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l8-4 6 3v15M9 9h.01M9 13h.01M9 17h.01M13 9h.01M13 13h.01M13 17h.01"/></svg>' },
  { key: 'activity', routeName: 'AdminActivity', label: 'Activity logs', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>' },
]
</script>
