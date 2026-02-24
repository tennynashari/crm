<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <h1 class="text-2xl lg:text-3xl font-bold text-gray-800">{{ $t('areas.title') }}</h1>
      <router-link to="/areas/create" class="btn btn-primary">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ $t('areas.addArea') }}
      </router-link>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
      <p class="mt-2 text-gray-600">{{ $t('areas.loadingAreas') }}</p>
    </div>

    <!-- Areas List -->
    <div v-else class="card">
      <div v-if="areas.length > 0" class="space-y-3">
        <div
          v-for="area in areas"
          :key="area.id"
          class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50"
        >
          <div>
            <h3 class="font-semibold text-gray-900">{{ area.name }}</h3>
            <p v-if="area.description" class="text-sm text-gray-600 mt-1">
              {{ area.description }}
            </p>
          </div>
          <div class="flex space-x-2">
            <router-link
              :to="`/areas/${area.id}/edit`"
              class="text-blue-600 hover:text-blue-800 p-2"
              :title="$t('areas.editArea')"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </router-link>
            <button
              type="button"
              @click="handleDelete(area)"
              class="text-red-600 hover:text-red-800 p-2"
              :title="$t('areas.deleteArea')"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <div v-else class="text-center text-gray-500 py-8">
        {{ $t('areas.noAreas') }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAreaStore } from '@/stores/area'

const { t } = useI18n()
const areaStore = useAreaStore()

const areas = computed(() => areaStore.areas)
const loading = computed(() => areaStore.loading)

const handleDelete = async (area) => {
  if (!confirm(t('areas.confirmDelete', { name: area.name }))) {
    return
  }

  try {
    await areaStore.deleteArea(area.id)
    alert(t('areas.deleteSuccess'))
  } catch (error) {
    alert(t('areas.deleteError'))
  }
}

onMounted(async () => {
  await areaStore.fetchAreas()
})
</script>
