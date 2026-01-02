<template>
  <div>
    <div class="flex items-center mb-6">
      <button @click="$router.back()" class="text-gray-600 hover:text-gray-900 mr-4">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
      </button>
      <h1 class="text-3xl font-bold text-gray-800">{{ isEditMode ? 'Edit Customer' : 'Add New Customer' }}</h1>
    </div>

    <div class="card max-w-3xl">
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="md:col-span-2">
            <label class="flex items-center">
              <input
                v-model="form.is_individual"
                type="checkbox"
                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
              />
              <span class="ml-2 text-sm text-gray-700">Individual Customer (not a company)</span>
            </label>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ form.is_individual ? 'Customer Name' : 'Company Name' }} <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.company"
              type="text"
              required
              class="input"
              :placeholder="form.is_individual ? 'e.g., John Doe' : 'e.g., PT Maju Jaya'"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Email
            </label>
            <input
              v-model="form.email"
              type="email"
              class="input"
              placeholder="email@example.com"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Phone
            </label>
            <input
              v-model="form.phone"
              type="text"
              class="input"
              placeholder="021-1234567"
            />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Address
            </label>
            <textarea
              v-model="form.address"
              rows="2"
              class="input"
              placeholder="Full address"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Area
            </label>
            <select v-model="form.area_id" class="input">
              <option :value="null">Select area</option>
              <option v-for="area in areas" :key="area.id" :value="area.id">
                {{ area.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Source <span class="text-red-500">*</span>
            </label>
            <select v-model="form.source" required class="input">
              <option value="inbound">Inbound</option>
              <option value="outbound">Outbound</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Lead Status
            </label>
            <select v-model="form.lead_status_id" class="input">
              <option :value="null">Select status</option>
              <option v-for="status in statuses" :key="status.id" :value="status.id">
                {{ status.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Assigned Sales
            </label>
            <select v-model="form.assigned_sales_id" class="input">
              <option :value="null">Select sales</option>
              <option v-for="user in salesUsers" :key="user.id" :value="user.id">
                {{ user.name }}
              </option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Notes
          </label>
          <textarea
            v-model="form.notes"
            rows="4"
            class="input"
            placeholder="Internal notes..."
          ></textarea>
        </div>

        <div v-if="error" class="text-red-600 text-sm">
          {{ error }}
        </div>

        <div class="flex space-x-4">
          <button
            type="submit"
            :disabled="loading"
            class="btn btn-primary flex-1"
          >
            {{ loading ? (isEditMode ? 'Updating...' : 'Creating...') : (isEditMode ? 'Update Customer' : 'Create Customer') }}
          </button>
          <button
            type="button"
            @click="$router.back()"
            class="btn btn-secondary"
          >
            Cancel
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useCustomerStore } from '@/stores/customer'
import { useAreaStore } from '@/stores/area'
import { useLeadStatusStore } from '@/stores/leadStatus'
import { useUserStore } from '@/stores/user'

const router = useRouter()
const route = useRoute()
const customerStore = useCustomerStore()
const areaStore = useAreaStore()
const leadStatusStore = useLeadStatusStore()
const userStore = useUserStore()

const isEditMode = computed(() => !!route.params.id)

const form = ref({
  company: '',
  is_individual: false,
  email: '',
  phone: '',
  address: '',
  area_id: null,
  source: 'inbound',
  lead_status_id: null,
  assigned_sales_id: null,
  notes: '',
})

const loading = ref(false)
const error = ref('')

const areas = ref([])
const statuses = ref([])
const salesUsers = ref([])

const handleSubmit = async () => {
  loading.value = true
  error.value = ''

  try {
    if (isEditMode.value) {
      const response = await customerStore.updateCustomer(route.params.id, form.value)
      alert('Customer updated successfully!')
      router.push(`/customers/${route.params.id}`)
    } else {
      const response = await customerStore.createCustomer(form.value)
      alert('Customer created successfully!')
      router.push(`/customers/${response.customer.id}`)
    }
  } catch (err) {
    error.value = err.response?.data?.message || `Failed to ${isEditMode.value ? 'update' : 'create'} customer`
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  areas.value = await areaStore.fetchAreas()
  statuses.value = await leadStatusStore.fetchStatuses()
  
  // Fetch sales users
  try {
    const users = await userStore.fetchSalesUsers()
    salesUsers.value = users
  } catch (error) {
    console.error('Failed to load sales users:', error)
  }

  // If edit mode, load customer data
  if (isEditMode.value) {
    try {
      const customer = await customerStore.fetchCustomer(route.params.id)
      form.value = {
        company: customer.company,
        is_individual: customer.is_individual || false,
        email: customer.email,
        phone: customer.phone,
        address: customer.address,
        area_id: customer.area_id,
        source: customer.source,
        lead_status_id: customer.lead_status_id,
        assigned_sales_id: customer.assigned_sales_id,
        notes: customer.notes,
      }
    } catch (error) {
      console.error('Failed to load customer:', error)
      alert('Failed to load customer data')
      router.back()
    }
  }
})
</script>
