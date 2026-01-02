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
        {{ isEditMode ? 'Edit' : 'Add' }} Area
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
          Area Name <span class="text-red-500">*</span>
        </label>
        <input
          id="name"
          v-model="form.name"
          type="text"
          required
          class="input"
          placeholder="e.g., Jakarta Pusat, Surabaya"
        />
      </div>

      <div>
        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
          Description
        </label>
        <textarea
          id="description"
          v-model="form.description"
          rows="4"
          class="input"
          placeholder="Optional description about this area"
        ></textarea>
      </div>

      <div class="flex space-x-3">
        <button type="submit" class="btn btn-primary flex-1" :disabled="submitting">
          {{ submitting ? 'Saving...' : (isEditMode ? 'Update' : 'Create') }} Area
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
import { useAreaStore } from '@/stores/area'

const route = useRoute()
const router = useRouter()
const areaStore = useAreaStore()

const loading = ref(false)
const submitting = ref(false)

const form = ref({
  name: '',
  description: '',
})

const isEditMode = computed(() => !!route.params.id)

const handleSubmit = async () => {
  submitting.value = true
  try {
    if (isEditMode.value) {
      await areaStore.updateArea(route.params.id, form.value)
      alert('Area updated successfully')
    } else {
      await areaStore.createArea(form.value)
      alert('Area created successfully')
    }
    router.push('/areas')
  } catch (error) {
    alert(`Failed to ${isEditMode.value ? 'update' : 'create'} area`)
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  if (isEditMode.value) {
    loading.value = true
    try {
      const area = await areaStore.fetchArea(route.params.id)
      form.value = {
        name: area.name,
        description: area.description || '',
      }
    } catch (error) {
      alert('Failed to load area')
      router.push('/areas')
    } finally {
      loading.value = false
    }
  }
})
</script>
