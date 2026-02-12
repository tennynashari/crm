<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-800 mb-8">Broadcast Email History</h1>

    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
      <p class="mt-2 text-gray-600">Loading...</p>
    </div>

    <div v-else-if="history.length === 0" class="text-center py-12">
      <svg class="h-16 w-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
      </svg>
      <p class="text-gray-600 text-lg mb-4">No broadcast email history yet</p>
      <router-link to="/broadcast-email" class="btn btn-primary">
        Send Your First Broadcast
      </router-link>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="item in history"
        :key="item.id"
        class="card hover:shadow-lg transition-shadow"
      >
        <div class="flex items-start justify-between mb-3">
          <div class="flex-1">
            <div class="flex items-center space-x-2 mb-2">
              <h3 class="text-lg font-semibold text-gray-900">{{ item.subject }}</h3>
              <span v-if="item.has_attachments" class="text-gray-400" title="Has attachments">
                📎
              </span>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600 mb-3">
              <div class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                {{ item.user?.name || 'Unknown' }}
              </div>
              <span>•</span>
              <div class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                {{ item.recipient_count }} recipients
              </div>
              <span>•</span>
              <div class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span v-if="item.filter_type === 'area' && item.area">
                  {{ item.area.name }}
                </span>
                <span v-else>All Areas</span>
              </div>
            </div>
          </div>
          <div class="text-right text-sm text-gray-500">
            {{ formatDate(item.created_at) }}
          </div>
        </div>
        
        <!-- Email Body Preview -->
        <div class="border-t pt-3">
          <button
            @click="toggleExpand(item.id)"
            class="flex items-center text-sm text-primary-600 hover:text-primary-700 mb-2"
          >
            <svg
              class="w-4 h-4 mr-1 transition-transform"
              :class="{ 'rotate-180': expandedItems.includes(item.id) }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
            {{ expandedItems.includes(item.id) ? 'Hide' : 'Show' }} Email Content
          </button>
          
          <div v-show="expandedItems.includes(item.id)">
            <div class="bg-gray-50 p-4 rounded-lg">
              <div class="prose prose-sm max-w-none" v-html="item.body"></div>
            </div>
            
            <!-- Recipients List -->
            <div class="mt-3">
              <button
                @click="toggleRecipients(item.id)"
                class="text-sm text-gray-600 hover:text-gray-900"
              >
                {{ showRecipients.includes(item.id) ? 'Hide' : 'Show' }} Recipients ({{ item.recipient_count }})
              </button>
              
              <div v-show="showRecipients.includes(item.id)" class="mt-2 bg-white border rounded p-3 max-h-40 overflow-y-auto">
                <div class="flex flex-wrap gap-2">
                  <span
                    v-for="(email, index) in item.recipients"
                    :key="index"
                    class="inline-flex items-center px-2 py-1 bg-gray-100 text-xs text-gray-700 rounded"
                  >
                    {{ email }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api/axios'

const history = ref([])
const loading = ref(false)
const expandedItems = ref([])
const showRecipients = ref([])

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

const toggleExpand = (id) => {
  const index = expandedItems.value.indexOf(id)
  if (index > -1) {
    expandedItems.value.splice(index, 1)
  } else {
    expandedItems.value.push(id)
  }
}

const toggleRecipients = (id) => {
  const index = showRecipients.value.indexOf(id)
  if (index > -1) {
    showRecipients.value.splice(index, 1)
  } else {
    showRecipients.value.push(id)
  }
}

const fetchHistory = async () => {
  loading.value = true
  try {
    const response = await api.get('/broadcast-email/history')
    history.value = response.data
  } catch (error) {
    console.error('Failed to fetch broadcast history:', error)
    alert('Failed to load broadcast history')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchHistory()
})
</script>
