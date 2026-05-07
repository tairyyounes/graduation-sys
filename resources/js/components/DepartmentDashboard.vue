<template>
  <AppDashboardLayout
    :nav-items="navItems"
    nav-title="Dashboard"
    brand-title="ProposalGuard AI"
    brand-subtitle="College of Computer Technology - Tripoli"
    :user="{ initials: 'DA', name: 'Dr. Ahmed Salem', email: 'dep@ctc.ly' }"
  >
  </AppDashboardLayout>
</template>

<script setup>
import AppDashboardLayout from './layouts/AppDashboardLayout.vue'

const navItems = [
  { key: 'overview', routeName: 'DepartmentOverview', label: 'Overview', icon: '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12h7V4H4v8zm0 8h7v-6H4v6zm9 0h7V12h-7v8zm0-10h7V4h-7v6z"/></svg>' },
  { key: 'queue', routeName: 'DepartmentQueue', label: 'Review queue', icon: '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h6.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>' },
  { key: 'decisions', routeName: 'DepartmentDecisions', label: 'Decisions', icon: '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>' },
  { key: 'students', routeName: 'DepartmentStudents', label: 'Students', icon: '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0119 14.5c0 3.038-3.134 5.5-7 5.5s-7-2.462-7-5.5c0-1.61.626-3.082 1.84-3.922L12 14z"/></svg>' },
]
</script>
