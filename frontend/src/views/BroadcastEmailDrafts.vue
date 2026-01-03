<template>
  <div>
    <div class="flex items-center justify-between mb-8">
      <h1 class="text-3xl font-bold text-gray-800">Email Drafts</h1>
      <router-link to="/broadcast-email" class="btn btn-primary">
        ✉️ New Broadcast
      </router-link>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
      <p class="mt-2 text-gray-600">Loading drafts...</p>
    </div>

    <div v-else-if="drafts.length === 0" class="text-center py-12">
      <svg class="h-16 w-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="text-gray-600 text-lg mb-4">No drafts yet</p>
      <router-link to="/broadcast-email" class="btn btn-primary">
        Create Your First Draft
      </router-link>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="draft in drafts"
        :key="draft.id"
        class="card hover:shadow-lg transition-shadow"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-center space-x-2 mb-2">
              <h3 class="text-lg font-semibold text-gray-900">
                {{ draft.subject || '(No Subject)' }}
              </h3>
              <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">
                Draft
              </span>
            </div>
            
            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600 mb-3">
              <div class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ formatDate(draft.updated_at) }}
              </div>
              <span>•</span>
              <div class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span v-if="draft.filter_type === 'area' && draft.area">
                  {{ draft.area.name }}
                </span>
                <span v-else>All Areas</span>
              </div>
            </div>

            <!-- Body Preview -->
            <div v-if="draft.body" class="text-sm text-gray-600 line-clamp-2" v-html="draft.body"></div>
            <div v-else class="text-sm text-gray-400 italic">No content</div>
          </div>

          <div class="flex items-center space-x-2 ml-4">
            <router-link
              :to="`/broadcast-email?draftId=${draft.id}`"
              class="p-2 text-primary-600 hover:bg-primary-50 rounded transition-colors"
              title="Edit Draft"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </router-link>
            <button
              @click="confirmDelete(draft.id)"
              class="p-2 text-red-600 hover:bg-red-50 rounded transition-colors"
              title="Delete Draft"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api/axios'

const drafts = ref([])
const loading = ref(false)

const formatDate = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffInHours = Math.floor((now - date) / (1000 * 60 * 60))
  
  if (diffInHours < 1) {
    return 'Just now'
  } else if (diffInHours < 24) {
    return `${diffInHours}h ago`
  } else if (diffInHours < 48) {
    return 'Yesterday'
  } else {
    return date.toLocaleDateString('id-ID', { 
      day: 'numeric', 
      month: 'short', 
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  }
}

const fetchDrafts = async () => {
  loading.value = true
  try {
    const response = await api.get('/broadcast-email/drafts')
    drafts.value = response.data
  } catch (error) {
    console.error('Failed to fetch drafts:', error)
    alert('Failed to load drafts')
  } finally {
    loading.value = false
  }
}

const confirmDelete = (draftId) => {
  if (confirm('Are you sure you want to delete this draft?')) {
    deleteDraft(draftId)
  }
}

const deleteDraft = async (draftId) => {
  try {
    await api.delete(`/broadcast-email/drafts/${draftId}`)
    drafts.value = drafts.value.filter(d => d.id !== draftId)
    alert('Draft deleted successfully')
  } catch (error) {
    console.error('Failed to delete draft:', error)
    alert('Failed to delete draft')
  }
}

onMounted(() => {
  fetchDrafts()
})
</script>
