<template>
  <div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center space-x-4">
      <button @click="$router.back()" class="text-gray-600 hover:text-gray-900">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
      </button>
      <h1 class="text-2xl lg:text-3xl font-bold text-gray-800">
        {{ isEditMode ? 'Edit' : 'Add' }} Sales
      </h1>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
      <p class="mt-2 text-gray-600">Loading...</p>
    </div>

    <!-- Form -->
    <form v-else @submit.prevent="handleSubmit" class="card space-y-6">
      <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
          Full Name <span class="text-red-500">*</span>
        </label>
        <input
          id="name"
          v-model="form.name"
          type="text"
          required
          class="input"
          placeholder="e.g., John Doe"
        />
      </div>

      <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
          Email <span class="text-red-500">*</span>
        </label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          required
          class="input"
          placeholder="e.g., john@flowcrm.test"
        />
      </div>

      <div v-if="!isEditMode">
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
          Password <span class="text-red-500">*</span>
        </label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          required
          minlength="8"
          class="input"
          placeholder="Minimum 8 characters"
        />
      </div>

      <div v-else>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
          New Password <span class="text-gray-500">(leave blank to keep current)</span>
        </label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          minlength="8"
          class="input"
          placeholder="Minimum 8 characters"
        />
      </div>

      <div>
        <label class="flex items-center">
          <input
            v-model="form.is_active"
            type="checkbox"
            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
          />
          <span class="ml-2 text-sm text-gray-700">Active</span>
        </label>
      </div>

      <div class="flex space-x-3">
        <button type="submit" class="btn btn-primary flex-1" :disabled="submitting">
          {{ submitting ? 'Saving...' : (isEditMode ? 'Update' : 'Create') }} Sales
        </button>
        <button
          type="button"
          @click="$router.back()"
          class="btn btn-secondary"
          :disabled="submitting"
        >
          Cancel
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const loading = ref(false)
const submitting = ref(false)

const form = ref({
  name: '',
  email: '',
  password: '',
  role: 'sales',
  is_active: true,
})

const isEditMode = computed(() => !!route.params.id)

const handleSubmit = async () => {
  submitting.value = true
  try {
    const data = { ...form.value }
    
    // Remove password if empty in edit mode
    if (isEditMode.value && !data.password) {
      delete data.password
    }

    if (isEditMode.value) {
      await userStore.updateUser(route.params.id, data)
      alert('Sales user updated successfully')
    } else {
      await userStore.createUser(data)
      alert('Sales user created successfully')
    }
    router.push('/sales')
  } catch (error) {
    const errorMsg = error.response?.data?.message || `Failed to ${isEditMode.value ? 'update' : 'create'} sales user`
    alert(errorMsg)
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  if (isEditMode.value) {
    loading.value = true
    try {
      const user = await userStore.fetchUser(route.params.id)
      form.value = {
        name: user.name,
        email: user.email,
        password: '',
        role: user.role,
        is_active: user.is_active ?? true,
      }
    } catch (error) {
      alert('Failed to load sales user')
      router.push('/sales')
    } finally {
      loading.value = false
    }
  }
})
</script>
