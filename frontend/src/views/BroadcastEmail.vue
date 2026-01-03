<template>
  <div class="max-w-5xl mx-auto">
    <h1 class="text-2xl font-semibold text-gray-900 mb-6">Broadcast Email</h1>

    <div class="bg-white rounded-lg shadow-sm p-6">
      <!-- Filter Section -->
      <div class="mb-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Select Recipients</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Filter Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Filter By
            </label>
            <select
              v-model="filterType"
              @change="handleFilterChange"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
              <option value="all">All Companies</option>
              <option value="area">By Area</option>
            </select>
          </div>

          <!-- Area Selection -->
          <div v-if="filterType === 'area'">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Select Area
            </label>
            <select
              v-model="selectedAreaId"
              @change="loadRecipients"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
              <option value="">-- Select Area --</option>
              <option v-for="area in areas" :key="area.id" :value="area.id">
                {{ area.name }}
              </option>
            </select>
          </div>
        </div>

        <!-- Load Recipients Button -->
        <button
          @click="loadRecipients"
          :disabled="loadingRecipients || (filterType === 'area' && !selectedAreaId)"
          class="mt-4 btn btn-secondary"
        >
          <span v-if="loadingRecipients">Loading...</span>
          <span v-else>Load Recipients ({{ recipientCount }})</span>
        </button>
      </div>

      <!-- Recipients Preview -->
      <div v-if="recipients.length > 0" class="mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
          <h3 class="text-sm font-medium text-blue-900 mb-2">
            Recipients Preview ({{ recipientCount }} emails)
          </h3>
          <div class="max-h-40 overflow-y-auto">
            <div v-for="(recipient, index) in recipients.slice(0, 20)" :key="index" class="text-sm text-blue-800">
              {{ recipient.email }} - {{ recipient.type }}
            </div>
            <div v-if="recipients.length > 20" class="text-sm text-blue-600 italic mt-2">
              ... and {{ recipients.length - 20 }} more
            </div>
          </div>
        </div>
      </div>

      <!-- Email Editor -->
      <div class="space-y-4">
        <h2 class="text-lg font-medium text-gray-900">Compose Email</h2>

        <!-- Remove To field since it's broadcast, only show Subject and Body with editor -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Subject <span class="text-red-500">*</span>
          </label>
          <input
            v-model="emailForm.subject"
            type="text"
            required
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            placeholder="Enter email subject"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Message <span class="text-red-500">*</span>
          </label>
          <div class="border rounded-md">
            <QuillEditor
              v-model:content="emailEditorData.body"
              theme="snow"
              toolbar="full"
              contentType="html"
              :style="{ minHeight: '300px' }"
            />
          </div>
        </div>

        <!-- File Attachments -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Attachments (Optional)
          </label>
          <input
            type="file"
            ref="fileInput"
            @change="handleFileSelect"
            multiple
            class="hidden"
          />
          <button
            type="button"
            @click="fileInput?.click()"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
          >
            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
            </svg>
            Attach Files
          </button>
          <p class="mt-1 text-xs text-gray-500">Maximum 10MB per file</p>
          
          <!-- File List -->
          <div v-if="emailFiles.length > 0" class="mt-3 space-y-2">
            <div
              v-for="(file, index) in emailFiles"
              :key="index"
              class="flex items-center justify-between p-2 bg-gray-50 rounded border"
            >
              <div class="flex items-center space-x-2">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="text-sm text-gray-700">{{ file.name }}</span>
                <span class="text-xs text-gray-500">({{ formatFileSize(file.size) }})</span>
              </div>
              <button
                type="button"
                @click="removeFile(index)"
                class="text-red-600 hover:text-red-800"
              >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Send Button -->
        <div class="flex justify-end space-x-3">
          <button
            @click="saveDraft"
            :disabled="saving || (!emailForm.subject && !emailEditorData.body)"
            class="btn btn-secondary"
          >
            <span v-if="saving">
              <svg class="animate-spin h-4 w-4 inline mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Saving...
            </span>
            <span v-else>💾 Save as Draft</span>
          </button>
          <button
            @click="sendBroadcast"
            :disabled="sending || recipients.length === 0 || !emailForm.subject || !emailEditorData.body"
            class="btn btn-primary"
          >
            <span v-if="sending">
              <svg class="animate-spin h-4 w-4 inline mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Sending...
            </span>
            <span v-else>Send Broadcast Email ({{ recipientCount }})</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAreaStore } from '@/stores/area'
import api from '@/api/axios'
import EmailEditor from '@/components/EmailEditor.vue'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'

const route = useRoute()
const router = useRouter()
const areaStore = useAreaStore()
const areas = computed(() => areaStore.areas)

const filterType = ref('all')
const selectedAreaId = ref('')
const recipients = ref([])
const loadingRecipients = ref(false)
const sending = ref(false)
const saving = ref(false)
const editingDraftId = ref(null)

const emailForm = ref({
  subject: '',
  body: ''
})

const emailEditorData = ref({
  to: '',
  subject: '',
  body: ''
})

const emailFiles = ref([])

const recipientCount = computed(() => recipients.value.length)

const handleFilterChange = () => {
  recipients.value = []
  if (filterType.value === 'all') {
    loadRecipients()
  }
}

const loadRecipients = async () => {
  if (filterType.value === 'area' && !selectedAreaId.value) {
    return
  }

  loadingRecipients.value = true
  try {
    const response = await api.post('/broadcast-email/recipients', {
      filter_type: filterType.value,
      area_id: selectedAreaId.value || null
    })
    recipients.value = response.data.recipients
  } catch (error) {
    console.error('Failed to load recipients:', error)
    alert('Failed to load recipients. Please try again.')
  } finally {
    loadingRecipients.value = false
  }
}

const sendBroadcast = async () => {
  if (!emailForm.value.subject || !emailEditorData.value.body) {
    alert('Please fill in subject and message')
    return
  }

  if (recipients.value.length === 0) {
    alert('Please load recipients first')
    return
  }

  if (!confirm(`Are you sure you want to send this email to ${recipientCount.value} recipients?`)) {
    return
  }

  sending.value = true
  try {
    // Create FormData for file upload support
    const formData = new FormData()
    formData.append('filter_type', filterType.value)
    if (selectedAreaId.value) {
      formData.append('area_id', selectedAreaId.value)
    }
    formData.append('subject', emailForm.value.subject)
    formData.append('body', emailEditorData.value.body)
    
    // Add attachments
    emailFiles.value.forEach((file, index) => {
      formData.append(`attachments[${index}]`, file)
    })
    
    const response = await api.post('/broadcast-email/send', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    alert(`✅ Broadcast sent successfully!\n\nSent: ${response.data.sent_count} emails\nFailed: ${response.data.failed_count} emails`)
    
    // Reset form
    emailForm.value = {
      subject: '',
      body: ''
    }
    emailEditorData.value = {
      to: '',
      subject: '',
      body: ''
    }
    emailFiles.value = []
    recipients.value = []
    editingDraftId.value = null
  } catch (error) {
    console.error('Failed to send broadcast:', error)
    if (error.response?.status === 400) {
      if (confirm('Email settings not configured. Go to Settings page?')) {
        window.location.href = '/#/settings'
      }
    } else {
      alert('Failed to send broadcast email. Please try again.')
    }
  } finally {
    sending.value = false
  }
}

const saveDraft = async () => {
  if (!emailForm.value.subject && !emailEditorData.value.body) {
    alert('Please enter at least a subject or message before saving')
    return
  }

  saving.value = true
  try {
    const draftData = {
      subject: emailForm.value.subject,
      body: emailEditorData.value.body,
      filter_type: filterType.value,
      area_id: selectedAreaId.value || null
    }

    if (editingDraftId.value) {
      // Update existing draft
      await api.put(`/broadcast-email/drafts/${editingDraftId.value}`, draftData)
      alert('Draft updated successfully!')
    } else {
      // Create new draft
      await api.post('/broadcast-email/drafts', draftData)
      alert('Draft saved successfully!')
    }
    
    router.push('/broadcast-email/drafts')
  } catch (error) {
    console.error('Failed to save draft:', error)
    alert('Failed to save draft. Please try again.')
  } finally {
    saving.value = false
  }
}

const loadDraft = async (draftId) => {
  try {
    const response = await api.get(`/broadcast-email/drafts/${draftId}`)
    const draft = response.data
    
    emailForm.value.subject = draft.subject || ''
    emailEditorData.value.body = draft.body || ''
    filterType.value = draft.filter_type || 'all'
    selectedAreaId.value = draft.area_id || ''
    editingDraftId.value = draft.id
    
    if (filterType.value !== 'all' && selectedAreaId.value) {
      await loadRecipients()
    }
  } catch (error) {
    console.error('Failed to load draft:', error)
    alert('Failed to load draft')
  }
}

const fileInput = ref(null)

const handleFileSelect = (event) => {
  const selectedFiles = Array.from(event.target.files)
  
  const validFiles = selectedFiles.filter(file => {
    if (file.size > 10 * 1024 * 1024) {
      alert(`File "${file.name}" exceeds 10MB limit`)
      return false
    }
    return true
  })
  
  emailFiles.value.push(...validFiles)
  
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const removeFile = (index) => {
  emailFiles.value.splice(index, 1)
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

onMounted(async () => {
  await areaStore.fetchAreas()
  
  // Check if loading a draft
  if (route.query.draftId) {
    await loadDraft(route.query.draftId)
  }
})
</script>
