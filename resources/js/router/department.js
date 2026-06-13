import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/department/dashboard',
    name: 'DepartmentOverview',
    component: () => import('../components/department/DepartmentOverviewSection.vue'),
    meta: { page: 'overview' }
  },
  {
    path: '/department/dashboard/queue',
    name: 'DepartmentQueue',
    component: () => import('../components/department/DepartmentQueueSection.vue'),
    meta: { page: 'queue' }
  },
  {
    path: '/department/dashboard/proposal/:id',
    name: 'DepartmentProposal',
    component: () => import('../components/department/DepartmentProposalSection.vue'),
    meta: { page: 'proposal' }
  },
  {
    path: '/department/dashboard/compare/:id?',
    name: 'DepartmentCompare',
    component: () => import('../components/department/DepartmentCompareSection.vue'),
    meta: { page: 'proposal' }
  },
  {
    path: '/department/dashboard/decisions',
    name: 'DepartmentDecisions',
    component: () => import('../components/department/DepartmentDecisionsSection.vue'),
    meta: { page: 'decisions' }
  },
  {
    path: '/department/dashboard/students',
    name: 'DepartmentStudents',
    component: () => import('../components/department/DepartmentStudentsSection.vue'),
    meta: { page: 'students' }
  },
  {
    path: '/department/dashboard/members',
    name: 'DepartmentMembers',
    component: () => import('../components/department/DepartmentMembersSection.vue'),
    meta: { page: 'members' }
  },
  {
    path: '/department/dashboard/committees',
    name: 'DepartmentCommittees',
    component: () => import('../components/department/DepartmentCommitteesSection.vue'),
    meta: { page: 'committees' }
  },
  {
    path: '/department/dashboard/previous-proposals',
    name: 'DepartmentPreviousProposals',
    component: () => import('../components/common/HistoricalProposalsManager.vue'),
    meta: { page: 'previous-proposals' }
  },
  // Catch all unmatched routes and redirect to overview
  {
    path: '/department/dashboard/:pathMatch(.*)*',
    redirect: '/department/dashboard'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
