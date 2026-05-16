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
