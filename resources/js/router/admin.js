import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/admin/dashboard',
    name: 'AdminOverview',
    component: () => import('../components/admin/AdminOverviewSection.vue'),
    meta: { page: 'overview' }
  },
  {
    path: '/admin/dashboard/users',
    name: 'AdminUsers',
    component: () => import('../components/admin/AdminUsersSection.vue'),
    meta: { page: 'users' }
  },
  {
    path: '/admin/dashboard/departments',
    name: 'AdminDepartments',
    component: () => import('../components/admin/AdminDepartmentsSection.vue'),
    meta: { page: 'departments' }
  },
  {
    path: '/admin/dashboard/departments/:id',
    name: 'AdminDepartmentDetails',
    component: () => import('../components/admin/AdminDepartmentDetailsSection.vue'),
    meta: { page: 'departments' }
  },
  {
    path: '/admin/dashboard/activity',
    name: 'AdminActivity',
    component: () => import('../components/admin/AdminActivitySection.vue'),
    meta: { page: 'activity' }
  },
  // Catch all unmatched routes and redirect to overview
  {
    path: '/admin/dashboard/:pathMatch(.*)*',
    redirect: '/admin/dashboard'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
