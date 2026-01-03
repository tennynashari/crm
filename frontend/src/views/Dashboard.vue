<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-800 mb-8">Dashboard</h1>

    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
      <p class="mt-2 text-gray-600">Loading...</p>
    </div>

    <div v-else-if="stats" class="space-y-6">
      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
        <router-link to="/customers" class="card hover:shadow-lg transition-shadow cursor-pointer">
          <div class="flex items-center">
            <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
              <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Total Customers</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.total_customers }}</p>
            </div>
          </div>
        </router-link>

        <router-link to="/customers?lead_status_id=7" class="card hover:shadow-lg transition-shadow cursor-pointer">
          <div class="flex items-center">
            <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
              <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">🔥 Hot Leads</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.hot_leads }}</p>
            </div>
          </div>
        </router-link>

        <router-link to="/customers?lead_status_id=6" class="card hover:shadow-lg transition-shadow cursor-pointer">
          <div class="flex items-center">
            <div class="flex-shrink-0 bg-orange-500 rounded-md p-3">
              <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">🔥 Warm Lead</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.warm_leads }}</p>
            </div>
          </div>
        </router-link>

        <router-link to="/customers?next_action_status=today" class="card hover:shadow-lg transition-shadow cursor-pointer">
          <div class="flex items-center">
            <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
              <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">⏰ Action Today</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.action_today }}</p>
            </div>
          </div>
        </router-link>
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Leads by Status -->
        <div class="card">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Leads by Status</h3>
          <div class="space-y-3">
            <div
              v-for="status in stats.leads_by_status"
              :key="status.status"
              class="flex items-center justify-between"
            >
              <div class="flex items-center">
                <div
                  class="w-3 h-3 rounded-full mr-3"
                  :style="{ backgroundColor: status.color }"
                ></div>
                <span class="text-sm text-gray-700">{{ status.status }}</span>
              </div>
              <span class="text-sm font-semibold text-gray-900">{{ status.total }}</span>
            </div>
          </div>
        </div>

        <!-- Customers by Area -->
        <div class="card">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Customers by Area</h3>
          <div class="space-y-3">
            <div
              v-for="area in stats.customers_by_area"
              :key="area.area"
              class="flex items-center justify-between"
            >
              <span class="text-sm text-gray-700">{{ area.area }}</span>
              <span class="text-sm font-semibold text-gray-900">{{ area.total }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Additional Stats -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="card">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">💤 Dormant Leads</p>
              <p class="text-2xl font-semibold text-gray-900 mt-1">{{ stats.dormant_leads }}</p>
              <p class="text-xs text-gray-500 mt-1">No interaction in 30 days</p>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">📥 New Inbound Today</p>
              <p class="text-2xl font-semibold text-gray-900 mt-1">{{ stats.new_inbound_today }}</p>
              <p class="text-xs text-gray-500 mt-1">Email only</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'

const dashboardStore = useDashboardStore()

const stats = ref(null)
const loading = ref(false)

onMounted(async () => {
  loading.value = true
  try {
    stats.value = await dashboardStore.fetchStats()
  } catch (error) {
    console.error('Error loading dashboard:', error)
  } finally {
    loading.value = false
  }
})
</script>
