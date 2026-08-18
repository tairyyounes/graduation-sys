<template>
  <AppDashboardLayout
    :nav-items="navItems"
    :nav-title="$t('adminnav.dashboard')"
    :brand-subtitle="$t('common.college')"
    :user="{ initials: 'SA', name: 'System Admin', email: 'admin@ctc.ly' }"
  />
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppDashboardLayout from './layouts/AppDashboardLayout.vue'

const { t } = useI18n()

const allNavItems = [
  { key: 'overview', routeName: 'AdminOverview', labelKey: 'adminnav.overview', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12h7V4H4v8zm0 8h7v-6H4v6zm9 0h7V12h-7v8zm0-10h7V4h-7v6z"/></svg>' },
  { key: 'users', routeName: 'AdminUsers', labelKey: 'adminnav.users', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm10 8v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>' },
  { key: 'departments', routeName: 'AdminDepartments', labelKey: 'adminnav.departments', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l8-4 6 3v15M9 9h.01M9 13h.01M9 17h.01M13 9h.01M13 13h.01M13 17h.01"/></svg>' },
  { key: 'activity', routeName: 'AdminActivity', labelKey: 'adminnav.activity', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>' },
  { key: 'previous-proposals', routeName: 'AdminPreviousProposals', labelKey: 'deptnav.previous_proposals', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
]

const navItems = computed(() => allNavItems.map(item => ({ ...item, label: t(item.labelKey) })))
</script>
