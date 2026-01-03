<template>
  <div class="email-editor">
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-1">To *</label>
      <input
        :value="modelValue.to"
        @input="$emit('update:modelValue', { ...modelValue, to: $event.target.value })"
        type="text"
        class="input"
        placeholder="recipient@example.com (comma-separated for multiple)"
        required
      />
    </div>

    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
      <input
        :value="modelValue.subject"
        @input="$emit('update:modelValue', { ...modelValue, subject: $event.target.value })"
        type="text"
        class="input"
        placeholder="Email subject"
        required
      />
    </div>

    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
      <QuillEditor
        :content="modelValue.body"
        @update:content="updateBody"
        theme="snow"
        toolbar="full"
        contentType="html"
        :style="{ minHeight: '300px' }"
      />
    </div>

    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-1">Attachments</label>
      <input
        ref="fileInput"
        type="file"
        multiple
        @change="handleFileSelect"
        class="hidden"
      />
      <button
        type="button"
        @click="$refs.fileInput.click()"
        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
      >
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
        </svg>
        Attach Files
      </button>
      <p class="text-xs text-gray-500 mt-1">Max 10MB per file</p>
      
      <!-- File list -->
      <div v-if="files.length > 0" class="mt-3 space-y-2">
        <div
          v-for="(file, index) in files"
          :key="index"
          class="flex items-center justify-between bg-gray-50 p-2 rounded"
        >
          <div class="flex items-center text-sm">
            <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span class="text-gray-700">{{ file.name }}</span>
            <span class="text-gray-500 ml-2">({{ formatFileSize(file.size) }})</span>
          </div>
          <button
            type="button"
            @click="removeFile(index)"
            class="text-red-600 hover:text-red-800"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'

const props = defineProps({
  modelValue: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update:modelValue', 'update:files'])

const files = ref([])
const fileInput = ref(null)

const updateBody = (content) => {
  emit('update:modelValue', { ...props.modelValue, body: content })
}

const handleFileSelect = (event) => {
  const selectedFiles = Array.from(event.target.files)
  
  // Check file size (max 10MB)
  const validFiles = selectedFiles.filter(file => {
    if (file.size > 10 * 1024 * 1024) {
      alert(`File "${file.name}" exceeds 10MB limit`)
      return false
    }
    return true
  })
  
  files.value.push(...validFiles)
  emit('update:files', files.value)
  
  // Reset input
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const removeFile = (index) => {
  files.value.splice(index, 1)
  emit('update:files', files.value)
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}
</script>

<style>
.ql-editor {
  min-height: 250px;
}
</style>
