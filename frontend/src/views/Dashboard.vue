<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-800 mb-8">{{ $t('dashboard.title') }}</h1>

    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
      <p class="mt-2 text-gray-600">{{ $t('dashboard.loading') }}</p>
    </div>

    <div v-else-if="stats" class="space-y-6">
      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
        <router-link to="/customers" class="card hover:shadow-lg transition-shadow cursor-pointer">
          <div class="flex items-center">
            <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
              <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">{{ $t('dashboard.totalCustomers') }}</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.total_customers }}</p>
            </div>
          </div>
        </router-link>

        <div class="card">
          <div class="flex items-center">
            <div class="flex-shrink-0 bg-orange-500 rounded-md p-3">
              <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">{{ $t('dashboard.upcomingMeeting') }}</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.meeting_count }}</p>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="flex items-center">
            <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
              <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">{{ $t('dashboard.hotLeads') }}</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.hot_leads }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Leads by Status -->
        <div class="card">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ $t('dashboard.leadsByStatus') }}</h3>
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
          <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ $t('dashboard.customersByArea') }}</h3>
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

      <!-- Next Action Today -->
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-800">{{ $t('dashboard.nextActionToday') }}</h3>
          <span v-if="todayActions.length > 0" class="badge bg-green-100 text-green-800">
            {{ todayActions.length }} {{ todayActions.length === 1 ? 'customer' : 'customers' }}
          </span>
        </div>

        <div v-if="todayActionsLoading" class="text-center py-8">
          <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600"></div>
        </div>

        <div v-else-if="todayActions.length === 0" class="text-center py-8 text-gray-500">
          {{ $t('dashboard.noActionsToday') }}
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">
                  {{ $t('customers.company') }}
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                  {{ $t('customers.area') }}
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                  {{ $t('customers.status') }}
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">
                  {{ $t('customers.source') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/5">
                  {{ $t('customers.nextAction') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">
                  {{ $t('customers.lastInteraction') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="customer in todayActions"
                :key="customer.id"
                @click="goToDetail(customer.id)"
                class="hover:bg-gray-50 cursor-pointer transition-colors"
              >
                <td class="px-4 py-3">
                  <div class="text-sm font-medium text-gray-900 truncate max-w-xs">{{ customer.company }}</div>
                  <div class="text-xs text-gray-500 truncate max-w-xs">{{ customer.email }}</div>
                  <div v-if="customer.phone" class="text-xs text-gray-500">📞 {{ customer.phone }}</div>
                </td>
                <td class="px-3 py-3 text-sm text-gray-900">
                  <div class="truncate max-w-24">{{ customer.area?.name || '-' }}</div>
                </td>
                <td class="px-3 py-3">
                  <span
                    v-if="customer.lead_status"
                    class="badge text-xs px-2 py-1 whitespace-nowrap"
                    :style="{
                      backgroundColor: customer.lead_status.color + '20',
                      color: customer.lead_status.color,
                    }"
                  >
                    {{ customer.lead_status.name }}
                  </span>
                </td>
                <td class="px-3 py-3">
                  <span
                    class="badge text-xs px-2 py-1 whitespace-nowrap"
                    :class="{
                      'bg-green-100 text-green-800': customer.source === 'inbound',
                      'bg-blue-100 text-blue-800': customer.source === 'outbound',
                    }"
                  >
                    {{ customer.source }}
                  </span>
                </td>
                <td class="px-4 py-3 text-sm">
                  <div v-if="customer.next_action_date">
                    <div class="text-gray-900 text-xs font-bold">{{ formatDate(customer.next_action_date) }}</div>
                    <div class="text-gray-500 text-xs truncate max-w-xs">{{ customer.next_action_plan }}</div>
                  </div>
                  <span v-else class="text-gray-400">-</span>
                </td>
                <td class="px-4 py-3 text-sm">
                  <div v-if="customer.interactions && customer.interactions.length > 0">
                    <div class="text-gray-900 text-xs font-medium">
                      {{ formatDateTime(customer.interactions[0].interaction_at) }}
                    </div>
                    <div class="text-gray-500 text-xs truncate max-w-xs">
                      {{ customer.interactions[0].summary || customer.interactions[0].content || '-' }}
                    </div>
                  </div>
                  <span v-else class="text-gray-400">{{ $t('customers.noHistory') }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useDashboardStore } from '@/stores/dashboard'

const router = useRouter()
const dashboardStore = useDashboardStore()

const stats = ref(null)
const loading = ref(false)
const todayActions = ref([])
const todayActionsLoading = ref(false)

const goToDetail = (id) => {
  router.push(`/customers/${id}`)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const formatDateTime = (datetime) => {
  return new Date(datetime).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

onMounted(async () => {
  loading.value = true
  todayActionsLoading.value = true
  try {
    const [statsData, actionsData] = await Promise.all([
      dashboardStore.fetchStats(),
      dashboardStore.fetchTodayActions(),
    ])
    stats.value = statsData
    todayActions.value = actionsData
  } catch (error) {
    console.error('Error loading dashboard:', error)
  } finally {
    loading.value = false
    todayActionsLoading.value = false
  }
})
</script>
