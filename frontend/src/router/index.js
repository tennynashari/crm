import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login.vue'),
    meta: { guest: true },
  },
  {
    path: '/',
    component: () => import('@/layouts/MainLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('@/views/Dashboard.vue'),
      },
      {
        path: 'customers',
        name: 'Customers',
        component: () => import('@/views/Customers.vue'),
      },
      {
        path: 'customers/create',
        name: 'CustomerCreate',
        component: () => import('@/views/CustomerForm.vue'),
      },
      {
        path: 'customers/:id/edit',
        name: 'CustomerEdit',
        component: () => import('@/views/CustomerForm.vue'),
      },
      {
        path: 'customers/:id',
        name: 'CustomerDetail',
        component: () => import('@/views/CustomerDetail.vue'),
      },
      {
        path: 'areas',
        name: 'Areas',
        component: () => import('@/views/Areas.vue'),
      },
      {
        path: 'areas/create',
        name: 'AreaCreate',
        component: () => import('@/views/AreaForm.vue'),
      },
      {
        path: 'areas/:id/edit',
        name: 'AreaEdit',
        component: () => import('@/views/AreaForm.vue'),
      },
      {
        path: 'sales',
        name: 'Sales',
        component: () => import('@/views/Sales.vue'),
      },
      {
        path: 'sales/create',
        name: 'SalesCreate',
        component: () => import('@/views/SalesForm.vue'),
      },
      {
        path: 'sales/:id/edit',
        name: 'SalesEdit',
        component: () => import('@/views/SalesForm.vue'),
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth) {
    if (!authStore.isAuthenticated) {
      // Not authenticated, redirect to login
      next('/login')
    } else {
      // Already authenticated, proceed
      next()
    }
  } else if (to.meta.guest && authStore.isAuthenticated) {
    // Guest route but user is authenticated, redirect to home
    next('/')
  } else {
    next()
  }
})

export default router
