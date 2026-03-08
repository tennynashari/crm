<template>
  <div>
    <h1 class="text-2xl lg:text-3xl font-bold text-gray-800 mb-6 lg:mb-8">{{ $t('dashboard.title') }}</h1>

    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
      <p class="mt-2 text-gray-600">{{ $t('dashboard.loading') }}</p>
    </div>

    <div v-else-if="stats" class="space-y-6">
      <!-- Next Action Today -->
      <div class="card">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-2">
          <h3 class="text-base lg:text-lg font-semibold text-gray-800">{{ $t('dashboard.nextActionToday') }}</h3>
          <span v-if="todayActionsPagination.total > 0" class="badge bg-green-100 text-green-800 text-xs lg:text-sm">
            {{ todayActionsPagination.total }} {{ todayActionsPagination.total === 1 ? 'customer' : 'customers' }}
          </span>
        </div>

        <div v-if="todayActionsLoading" class="text-center py-8">
          <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600"></div>
        </div>

        <div v-else-if="todayActions.length === 0" class="text-center py-8 text-gray-500">
          {{ $t('dashboard.noActionsToday') }}
        </div>

        <!-- Mobile View -->
        <div v-else class="lg:hidden space-y-3">
          <div
            v-for="customer in todayActions"
            :key="customer.id"
            @click="goToDetail(customer.id)"
            class="bg-gray-50 p-3 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors"
          >
            <div class="flex justify-between items-start mb-2">
              <div class="flex-1">
                <h4 class="font-semibold text-gray-900 text-sm">{{ customer.company }}</h4>
                <p class="text-xs text-gray-600">{{ customer.email }}</p>
                <p v-if="customer.phone" class="text-xs text-gray-600">📞 {{ customer.phone }}</p>
              </div>
              <div class="flex flex-col items-end space-y-1 ml-2">
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
                <span
                  class="badge text-xs px-2 py-1 whitespace-nowrap"
                  :class="{
                    'bg-green-100 text-green-800': customer.source === 'inbound',
                    'bg-blue-100 text-blue-800': customer.source === 'outbound',
                  }"
                >
                  {{ customer.source }}
                </span>
              </div>
            </div>
            <div class="space-y-1 text-xs text-gray-600">
              <p v-if="customer.area">
                <span class="font-medium">{{ $t('customers.area') }}:</span> {{ customer.area.name }}
              </p>
              <p v-if="customer.next_action_date" class="text-orange-600 font-medium">
                <span class="font-bold">{{ $t('customers.next') }}:</span> {{ formatDate(customer.next_action_date) }}
                <span v-if="customer.next_action_plan" class="block text-gray-600 font-normal mt-1">{{ customer.next_action_plan }}</span>
              </p>
              <div v-if="customer.interactions && customer.interactions.length > 0" class="pt-2 border-t">
                <p class="font-medium text-gray-700">{{ $t('customers.lastInteraction') }}:</p>
                <p class="text-xs text-gray-600">{{ formatDateTime(customer.interactions[0].interaction_at) }}</p>
                <p class="text-xs text-gray-500 italic">{{ customer.interactions[0].summary || customer.interactions[0].content || '-' }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Desktop View -->
        <div v-else class="hidden lg:block overflow-x-auto">
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

        <!-- Pagination -->
        <div v-if="todayActionsPagination.last_page > 1" class="flex flex-col sm:flex-row justify-center items-center gap-2 sm:space-x-2 mt-4">
          <button
            @click="changeTodayActionsPage(todayActionsPagination.current_page - 1)"
            :disabled="todayActionsPagination.current_page === 1"
            class="w-full sm:w-auto px-3 py-2 text-sm border rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Previous
          </button>
          <span class="text-sm text-gray-700">
            Page {{ todayActionsPagination.current_page }} of {{ todayActionsPagination.last_page }}
          </span>
          <button
            @click="changeTodayActionsPage(todayActionsPagination.current_page + 1)"
            :disabled="todayActionsPagination.current_page === todayActionsPagination.last_page"
            class="w-full sm:w-auto px-3 py-2 text-sm border rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Next
          </button>
        </div>
      </div>

      <!-- This Week Meetings -->
      <div class="card">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-2">
          <h3 class="text-base lg:text-lg font-semibold text-gray-800">{{ $t('dashboard.thisWeekMeetings') }}</h3>
          <span v-if="weekMeetingsPagination.total > 0" class="badge bg-orange-100 text-orange-800 text-xs lg:text-sm">
            {{ weekMeetingsPagination.total }} {{ weekMeetingsPagination.total === 1 ? 'customer' : 'customers' }}
          </span>
        </div>

        <div v-if="weekMeetingsLoading" class="text-center py-8">
          <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600"></div>
        </div>

        <div v-else-if="weekMeetings.length === 0" class="text-center py-8 text-gray-500">
          {{ $t('dashboard.noMeetingsThisWeek') }}
        </div>

        <!-- Mobile View -->
        <div v-else class="lg:hidden space-y-3">
          <div
            v-for="customer in weekMeetings"
            :key="customer.id"
            @click="goToDetail(customer.id)"
            class="bg-gray-50 p-3 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors"
          >
            <div class="flex justify-between items-start mb-2">
              <div class="flex-1">
                <h4 class="font-semibold text-gray-900 text-sm">{{ customer.company }}</h4>
                <p class="text-xs text-gray-600">{{ customer.email }}</p>
                <p v-if="customer.phone" class="text-xs text-gray-600">📞 {{ customer.phone }}</p>
              </div>
              <div class="flex flex-col items-end space-y-1 ml-2">
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
                <span
                  class="badge text-xs px-2 py-1 whitespace-nowrap"
                  :class="{
                    'bg-green-100 text-green-800': customer.source === 'inbound',
                    'bg-blue-100 text-blue-800': customer.source === 'outbound',
                  }"
                >
                  {{ customer.source }}
                </span>
              </div>
            </div>
            <div class="space-y-1 text-xs text-gray-600">
              <p v-if="customer.area">
                <span class="font-medium">{{ $t('customers.area') }}:</span> {{ customer.area.name }}
              </p>
              <p v-if="customer.next_action_date" class="text-orange-600 font-medium">
                <span class="font-bold">{{ $t('customers.next') }}:</span> {{ formatDate(customer.next_action_date) }}
                <span v-if="customer.next_action_plan" class="block text-gray-600 font-normal mt-1">{{ customer.next_action_plan }}</span>
              </p>
              <div v-if="customer.interactions && customer.interactions.length > 0" class="pt-2 border-t">
                <p class="font-medium text-gray-700">{{ $t('customers.lastInteraction') }}:</p>
                <p class="text-xs text-gray-600">{{ formatDateTime(customer.interactions[0].interaction_at) }}</p>
                <p class="text-xs text-gray-500 italic">{{ customer.interactions[0].summary || customer.interactions[0].content || '-' }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Desktop View -->
        <div v-else class="hidden lg:block overflow-x-auto">
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
                v-for="customer in weekMeetings"
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

        <!-- Pagination -->
        <div v-if="weekMeetingsPagination.last_page > 1" class="flex flex-col sm:flex-row justify-center items-center gap-2 sm:space-x-2 mt-4">
          <button
            @click="changeWeekMeetingsPage(weekMeetingsPagination.current_page - 1)"
            :disabled="weekMeetingsPagination.current_page === 1"
            class="w-full sm:w-auto px-3 py-2 text-sm border rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Previous
          </button>
          <span class="text-sm text-gray-700">
            Page {{ weekMeetingsPagination.current_page }} of {{ weekMeetingsPagination.last_page }}
          </span>
          <button
            @click="changeWeekMeetingsPage(weekMeetingsPagination.current_page + 1)"
            :disabled="weekMeetingsPagination.current_page === weekMeetingsPagination.last_page"
            class="w-full sm:w-auto px-3 py-2 text-sm border rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Next
          </button>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6">
        <!-- Leads by Status -->
        <div class="card">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-2">
            <h3 class="text-base lg:text-lg font-semibold text-gray-800">{{ $t('dashboard.leadsByStatus') }}</h3>
            <span class="badge bg-red-100 text-red-800 text-xs lg:text-sm">
              🔥 {{ stats.hot_leads }} {{ $t('dashboard.hotLeads').replace('🔥 ', '') }}
            </span>
          </div>
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
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-2">
            <h3 class="text-base lg:text-lg font-semibold text-gray-800">{{ $t('dashboard.customersByArea') }}</h3>
            <span class="badge bg-blue-100 text-blue-800 text-xs lg:text-sm">
              👥 {{ stats.total_customers }} {{ $t('dashboard.totalCustomers') }}
            </span>
          </div>
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
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useDashboardStore } from '@/stores/dashboard'

const router = useRouter()
const dashboardStore = useDashboardStore()

const stats = ref(null)
const loading = ref(false)
const todayActions = computed(() => dashboardStore.todayActions)
const todayActionsLoading = computed(() => dashboardStore.todayActionsLoading)
const todayActionsPagination = computed(() => dashboardStore.todayActionsPagination)

const weekMeetings = computed(() => dashboardStore.weekMeetings)
const weekMeetingsLoading = computed(() => dashboardStore.weekMeetingsLoading)
const weekMeetingsPagination = computed(() => dashboardStore.weekMeetingsPagination)

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

const changeTodayActionsPage = async (page) => {
  if (page < 1 || page > todayActionsPagination.value.last_page) return
  await dashboardStore.fetchTodayActions(page)
}

const changeWeekMeetingsPage = async (page) => {
  if (page < 1 || page > weekMeetingsPagination.value.last_page) return
  await dashboardStore.fetchWeekMeetings(page)
}

onMounted(async () => {
  loading.value = true
  try {
    const [statsData] = await Promise.all([
      dashboardStore.fetchStats(),
      dashboardStore.fetchTodayActions(),
      dashboardStore.fetchWeekMeetings(),
    ])
    stats.value = statsData
  } catch (error) {
    console.error('Error loading dashboard:', error)
  } finally {
    loading.value = false
  }
})
</script>
