<template>
  <div>
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6">
      <h1 class="text-3xl font-bold text-gray-800 mb-4 lg:mb-0">Customers</h1>
      <router-link
        to="/customers/create"
        class="btn btn-primary inline-flex items-center justify-center"
      >
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add Customer
      </router-link>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <input
          v-model="filters.search"
          @input="handleSearch"
          type="text"
          placeholder="Search..."
          class="input"
        />

        <select v-model="filters.area_id" @change="applyFilters" class="input">
          <option :value="null">All Areas</option>
          <option v-for="area in areas" :key="area.id" :value="area.id">
            {{ area.name }}
          </option>
        </select>

        <select v-model="filters.lead_status_id" @change="applyFilters" class="input">
          <option :value="null">All Statuses</option>
          <option v-for="status in statuses" :key="status.id" :value="status.id">
            {{ status.name }}
          </option>
        </select>

        <select v-model="filters.source" @change="applyFilters" class="input">
          <option :value="null">All Sources</option>
          <option value="inbound">Inbound</option>
          <option value="outbound">Outbound</option>
        </select>

        <select v-model="filters.next_action_status" @change="applyFilters" class="input">
          <option :value="null">All Actions</option>
          <option value="today">Today</option>
          <option value="this_week">This Week</option>
          <option value="two_weeks">Two Weeks</option>
        </select>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
      <p class="mt-2 text-gray-600">Loading customers...</p>
    </div>

    <!-- Customer List -->
    <div v-else class="space-y-4">
      <!-- Mobile View -->
      <div class="lg:hidden space-y-4">
        <div
          v-for="customer in customers"
          :key="customer.id"
          @click="goToDetail(customer.id)"
          class="card cursor-pointer hover:shadow-lg transition-shadow"
        >
          <div class="flex justify-between items-start mb-2">
            <div>
              <h3 class="font-semibold text-gray-900">{{ customer.company }}</h3>
              <p v-if="customer.phone" class="text-xs text-gray-600">📞 {{ customer.phone }}</p>
              <p v-if="customer.is_individual" class="text-xs text-gray-500">Individual Customer</p>
            </div>
            <span
              v-if="customer.lead_status"
              class="badge text-xs px-2 py-1"
              :style="{
                backgroundColor: customer.lead_status.color + '20',
                color: customer.lead_status.color,
              }"
            >
              {{ customer.lead_status.name }}
            </span>
          </div>

          <div class="space-y-1 text-sm text-gray-600">
            <p v-if="customer.email">
              <span class="font-medium">Email:</span> {{ customer.email }}
            </p>
            <p v-if="customer.area">
              <span class="font-medium">Area:</span> {{ customer.area.name }}
            </p>
            <div v-if="customer.contacts && customer.contacts.length > 0">
              <p class="font-medium">PIC:</p>
              <p class="ml-2">{{ customer.contacts.find(c => c.is_primary)?.name || customer.contacts[0].name }}</p>
              <p v-if="(customer.contacts.find(c => c.is_primary) || customer.contacts[0]).email" class="ml-2 text-xs">
                ✉️ {{ (customer.contacts.find(c => c.is_primary) || customer.contacts[0]).email }}
              </p>
              <p v-if="(customer.contacts.find(c => c.is_primary) || customer.contacts[0]).whatsapp" class="ml-2 text-xs">
                💬 {{ (customer.contacts.find(c => c.is_primary) || customer.contacts[0]).whatsapp }}
              </p>
            </div>
            <p v-if="customer.next_action_date" class="text-orange-600 font-medium">
              Next: {{ formatDate(customer.next_action_date) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Desktop View -->
      <div class="hidden lg:block card overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">
                Company
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/5">
                PIC
              </th>
              <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                Area
              </th>
              <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                Status
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">
                Next Action
              </th>
              <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">
                Source
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="customer in customers"
              :key="customer.id"
              @click="goToDetail(customer.id)"
              class="hover:bg-gray-50 cursor-pointer transition-colors"
            >
              <td class="px-4 py-3">
                <div class="text-sm font-medium text-gray-900 truncate max-w-xs">{{ customer.company }}</div>
                <div class="text-xs text-gray-500 truncate max-w-xs">{{ customer.email }}</div>
                <div v-if="customer.phone" class="text-xs text-gray-500">📞 {{ customer.phone }}</div>
              </td>
              <td class="px-4 py-3">
                <div v-if="customer.contacts && customer.contacts.length > 0">
                  <div class="text-sm font-medium text-gray-900 truncate max-w-xs">
                    {{ customer.contacts.find(c => c.is_primary)?.name || customer.contacts[0].name }}
                  </div>
                  <div v-if="(customer.contacts.find(c => c.is_primary) || customer.contacts[0]).email" class="text-xs text-gray-500 truncate max-w-xs">
                    ✉️ {{ (customer.contacts.find(c => c.is_primary) || customer.contacts[0]).email }}
                  </div>
                  <div v-if="(customer.contacts.find(c => c.is_primary) || customer.contacts[0]).whatsapp" class="text-xs text-gray-500">
                    💬 {{ (customer.contacts.find(c => c.is_primary) || customer.contacts[0]).whatsapp }}
                  </div>
                </div>
                <span v-else class="text-sm text-gray-400">-</span>
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
              <td class="px-4 py-3 text-sm">
                <div v-if="customer.next_action_date">
                  <div class="text-gray-900 text-xs">{{ formatDate(customer.next_action_date) }}</div>
                  <div class="text-gray-500 text-xs truncate max-w-xs">{{ customer.next_action_plan }}</div>
                </div>
                <span v-else class="text-gray-400">-</span>
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
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex justify-center items-center space-x-2 mt-6">
        <button
          @click="changePage(pagination.current_page - 1)"
          :disabled="pagination.current_page === 1"
          class="btn btn-secondary disabled:opacity-50"
        >
          Previous
        </button>
        <span class="text-sm text-gray-600">
          Page {{ pagination.current_page }} of {{ pagination.last_page }}
        </span>
        <button
          @click="changePage(pagination.current_page + 1)"
          :disabled="pagination.current_page === pagination.last_page"
          class="btn btn-secondary disabled:opacity-50"
        >
          Next
        </button>
      </div>

      <!-- No data -->
      <div v-if="!loading && customers.length === 0" class="card text-center py-12">
        <p class="text-gray-500">No customers found.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useCustomerStore } from '@/stores/customer'
import { useAreaStore } from '@/stores/area'
import { useLeadStatusStore } from '@/stores/leadStatus'

const router = useRouter()
const route = useRoute()
const customerStore = useCustomerStore()
const areaStore = useAreaStore()
const leadStatusStore = useLeadStatusStore()

const customers = computed(() => customerStore.customers)
const pagination = computed(() => customerStore.pagination)
const loading = computed(() => customerStore.loading)

const areas = computed(() => areaStore.areas)
const statuses = computed(() => leadStatusStore.statuses)

const filters = ref({
  search: '',
  area_id: null,
  lead_status_id: null,
  source: null,
  next_action_status: null,
})

let searchTimeout = null

const handleSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 500)
}

const applyFilters = async () => {
  customerStore.setFilters(filters.value)
  await customerStore.fetchCustomers()
}

const changePage = async (page) => {
  await customerStore.fetchCustomers(page)
}

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

onMounted(async () => {
  try {
    // Load areas and statuses first
    await Promise.all([
      areaStore.fetchAreas(),
      leadStatusStore.fetchStatuses(),
    ])

    // Apply filters from URL query params
    if (route.query.lead_status_id) {
      filters.value.lead_status_id = parseInt(route.query.lead_status_id)
    }
    if (route.query.area_id) {
      filters.value.area_id = parseInt(route.query.area_id)
    }
    if (route.query.source) {
      filters.value.source = route.query.source
    }
    if (route.query.next_action_status) {
      filters.value.next_action_status = route.query.next_action_status
    }

    // Apply filters and fetch customers
    customerStore.setFilters(filters.value)
    await customerStore.fetchCustomers()
  } catch (error) {
    console.error('Error loading customers:', error)
  }
})
</script>
