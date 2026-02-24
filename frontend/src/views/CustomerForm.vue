<template>
  <div>
    <div class="flex items-center mb-6">
      <button @click="$router.back()" class="text-gray-600 hover:text-gray-900 mr-4">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
      </button>
      <h1 class="text-3xl font-bold text-gray-800">{{ isEditMode ? $t('customerForm.editTitle') : $t('customerForm.addTitle') }}</h1>
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
              <span class="ml-2 text-sm text-gray-700">{{ $t('customerForm.individualLabel') }}</span>
            </label>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ form.is_individual ? $t('customerForm.customerName') : $t('customerForm.companyName') }} <span class="text-red-500">{{ $t('customerForm.required') }}</span>
            </label>
            <input
              v-model="form.company"
              type="text"
              required
              class="input"
              :placeholder="form.is_individual ? $t('customerForm.namePlaceholder') : $t('customerForm.companyPlaceholder')"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ $t('customerForm.email') }}
            </label>
            <input
              v-model="form.email"
              type="email"
              class="input"
              :placeholder="$t('customerForm.emailPlaceholder')"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ $t('customerForm.phone') }}
            </label>
            <input
              v-model="form.phone"
              type="text"
              class="input"
              :placeholder="$t('customerForm.phonePlaceholder')"
            />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ $t('customerForm.address') }}
            </label>
            <textarea
              v-model="form.address"
              rows="2"
              class="input"
              :placeholder="$t('customerForm.addressPlaceholder')"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ $t('customerForm.area') }}
            </label>
            <select v-model="form.area_id" class="input">
              <option :value="null">{{ $t('customerForm.selectArea') }}</option>
              <option v-for="area in areas" :key="area.id" :value="area.id">
                {{ area.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ $t('customerForm.source') }} <span class="text-red-500">{{ $t('customerForm.required') }}</span>
            </label>
            <select v-model="form.source" required class="input">
              <option value="inbound">{{ $t('customerForm.inbound') }}</option>
              <option value="outbound">{{ $t('customerForm.outbound') }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ $t('customerForm.leadStatus') }}
            </label>
            <select v-model="form.lead_status_id" class="input">
              <option :value="null">{{ $t('customerForm.selectStatus') }}</option>
              <option v-for="status in statuses" :key="status.id" :value="status.id">
                {{ status.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ $t('customerForm.assignedSales') }}
            </label>
            <select v-model="form.assigned_sales_id" class="input">
              <option :value="null">{{ $t('customerForm.selectSales') }}</option>
              <option v-for="user in salesUsers" :key="user.id" :value="user.id">
                {{ user.name }}
              </option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ $t('customerForm.notes') }}
          </label>
          <textarea
            v-model="form.notes"
            rows="4"
            class="input"
            :placeholder="$t('customerForm.notesPlaceholder')"
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
            {{ loading ? (isEditMode ? $t('customerForm.updating') : $t('customerForm.creating')) : (isEditMode ? $t('customerForm.updateCustomer') : $t('customerForm.createCustomer')) }}
          </button>
          <button
            type="button"
            @click="$router.back()"
            class="btn btn-secondary"
          >
            {{ $t('customerForm.cancel') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter, useRoute } from 'vue-router'
import { useCustomerStore } from '@/stores/customer'
import { useAreaStore } from '@/stores/area'
import { useLeadStatusStore } from '@/stores/leadStatus'
import { useUserStore } from '@/stores/user'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const customerStore = useCustomerStore()
const areaStore = useAreaStore()
const leadStatusStore = useLeadStatusStore()
const userStore = useUserStore()
const authStore = useAuthStore()

const currentUser = computed(() => authStore.user)
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
      alert(t('customerForm.updateSuccess'))
      router.push(`/customers/${route.params.id}`)
    } else {
      const response = await customerStore.createCustomer(form.value)
      alert(t('customerForm.createSuccess'))
      router.push('/customers')
    }
  } catch (err) {
    error.value = err.response?.data?.message || t('customerForm.saveError')
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
    // Filter berdasarkan role:
    // - Admin: lihat semua sales
    // - Sales: hanya lihat dirinya sendiri
    
    if (currentUser.value?.role === 'admin') {
      salesUsers.value = users
    } else {
      // User sales hanya bisa lihat dirinya sendiri
      salesUsers.value = users.filter(user => user.id === currentUser.value?.id)
    }
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
      alert(t('customerForm.loadError'))
      router.back()
    }
  }
})
</script>
