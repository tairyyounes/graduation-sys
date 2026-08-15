<template>
  <AppDashboardLayout
    :nav-items="navItems"
    :nav-title="$t('common.dashboard')"
    brand-title="ProposalGuard AI"
    :brand-subtitle="$t('common.college')"
    :user="user"
  >
  </AppDashboardLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppDashboardLayout from './layouts/AppDashboardLayout.vue'

const { t } = useI18n()

const authUser = window.authUser || {}
const initials = (authUser.full_name || authUser.name || 'U').split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()

const user = ref({
  initials,
  name: authUser.full_name || authUser.name || 'Department User',
  email: authUser.email || ''
})

const allNavItems = [
  { key: 'overview', routeName: 'DepartmentOverview', labelKey: 'deptnav.overview', icon: '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12h7V4H4v8zm0 8h7v-6H4v6zm9 0h7V12h-7v8zm0-10h7V4h-7v6z"/></svg>' },
  { key: 'queue', routeName: 'DepartmentQueue', labelKey: 'deptnav.queue', icon: '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h6.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>' },
  { key: 'decisions', routeName: 'DepartmentDecisions', labelKey: 'deptnav.decisions', icon: '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>' },
  { key: 'students', routeName: 'DepartmentStudents', labelKey: 'deptnav.students', icon: '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0119 14.5c0 3.038-3.134 5.5-7 5.5s-7-2.462-7-5.5c0-1.61.626-3.082 1.84-3.922L12 14z"/></svg>' },
  { key: 'members', routeName: 'DepartmentMembers', labelKey: 'deptnav.members', icon: '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>' },
  { key: 'committees', routeName: 'DepartmentCommittees', labelKey: 'deptnav.committees', icon: '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>' },
  { key: 'previous-proposals', routeName: 'DepartmentPreviousProposals', labelKey: 'deptnav.previous_proposals', icon: '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
]

const navItems = computed(() => {
  const base = authUser.role === 'department_head'
    ? allNavItems
    : allNavItems.filter(item => ['overview', 'queue', 'decisions'].includes(item.key))
  return base.map(item => ({ ...item, label: t(item.labelKey) }))
})
</script>
