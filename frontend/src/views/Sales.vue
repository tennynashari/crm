<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <h1 class="text-2xl lg:text-3xl font-bold text-gray-800">Sales Team</h1>
      <router-link to="/sales/create" class="btn btn-primary">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add Sales
      </router-link>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
      <p class="mt-2 text-gray-600">Loading sales...</p>
    </div>

    <!-- Sales List -->
    <div v-else class="card">
      <div v-if="salesUsers.length > 0" class="space-y-3">
        <div
          v-for="user in salesUsers"
          :key="user.id"
          class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50"
        >
          <div>
            <h3 class="font-semibold text-gray-900">{{ user.name }}</h3>
            <p class="text-sm text-gray-600 mt-1">{{ user.email }}</p>
            <div class="flex items-center space-x-2 mt-2">
              <span class="badge bg-blue-100 text-blue-800 text-xs">
                {{ user.role }}
              </span>
              <span
                v-if="user.is_active"
                class="badge bg-green-100 text-green-800 text-xs"
              >
                Active
              </span>
              <span
                v-else
                class="badge bg-gray-100 text-gray-800 text-xs"
              >
                Inactive
              </span>
            </div>
          </div>
          <div class="flex space-x-2">
            <router-link
              :to="`/sales/${user.id}/edit`"
              class="text-blue-600 hover:text-blue-800 p-2"
              title="Edit sales"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </router-link>
            <button
              type="button"
              @click="handleDelete(user)"
              class="text-red-600 hover:text-red-800 p-2"
              title="Delete sales"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <div v-else class="text-center text-gray-500 py-8">
        No sales users yet. Add your first sales person to get started.
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, computed } from 'vue'
import { useUserStore } from '@/stores/user'

const userStore = useUserStore()

const salesUsers = computed(() => userStore.salesUsers)
const loading = computed(() => userStore.loading)

const handleDelete = async (user) => {
  if (!confirm(`Are you sure you want to delete "${user.name}"? This action cannot be undone.`)) {
    return
  }

  try {
    await userStore.deleteUser(user.id)
    alert('Sales user deleted successfully')
  } catch (error) {
    alert('Failed to delete sales user')
  }
}

onMounted(async () => {
  await userStore.fetchSalesUsers()
})
</script>
