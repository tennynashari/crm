<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-800 mb-8">Email Settings</h1>

    <div class="card max-w-2xl">
      <div v-if="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        <p class="mt-2 text-gray-600">Loading...</p>
      </div>

      <form v-else @submit.prevent="saveSettings" class="space-y-6">
        <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
          <p class="text-sm text-blue-800">
            <strong>Note:</strong> Configure your email settings to send emails directly from the CRM.
            These settings are private and only used for your account.
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Mail Server (SMTP Host) *
          </label>
          <input
            v-model="form.mail_host"
            type="text"
            placeholder="smtp.gmail.com"
            class="input"
            required
          />
          <p class="text-xs text-gray-500 mt-1">Example: smtp.gmail.com, smtp.office365.com</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Port *
            </label>
            <input
              v-model.number="form.mail_port"
              type="number"
              placeholder="587"
              class="input"
              required
            />
            <p class="text-xs text-gray-500 mt-1">Usually 587 for TLS</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Encryption *
            </label>
            <select v-model="form.mail_encryption" class="input" required>
              <option value="tls">TLS</option>
              <option value="ssl">SSL</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Email Username *
          </label>
          <input
            v-model="form.mail_username"
            type="text"
            placeholder="your-email@example.com"
            class="input"
            required
          />
          <p class="text-xs text-gray-500 mt-1">Your email address for SMTP authentication</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Email Password *
          </label>
          <input
            v-model="form.mail_password"
            type="password"
            placeholder="Enter your email password or app password"
            class="input"
            required
          />
          <p class="text-xs text-gray-500 mt-1">
            For Gmail, use App Password (not your regular password). 
            <a href="https://support.google.com/accounts/answer/185833" target="_blank" class="text-blue-600 hover:underline">Learn more</a>
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            From Email Address *
          </label>
          <input
            v-model="form.mail_from_address"
            type="email"
            placeholder="your-email@example.com"
            class="input"
            required
          />
          <p class="text-xs text-gray-500 mt-1">Email address shown as sender</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            From Name *
          </label>
          <input
            v-model="form.mail_from_name"
            type="text"
            placeholder="Your Name"
            class="input"
            required
          />
          <p class="text-xs text-gray-500 mt-1">Name shown as sender</p>
        </div>

        <div class="flex justify-end space-x-3 pt-4">
          <button type="submit" class="btn btn-primary" :disabled="loading">
            <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ settings ? 'Update Settings' : 'Save Settings' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useEmailSettingStore } from '@/stores/emailSetting'

const emailSettingStore = useEmailSettingStore()
const loading = ref(false)
const settings = ref(null)

const form = ref({
  mail_host: '',
  mail_port: 587,
  mail_username: '',
  mail_password: '',
  mail_encryption: 'tls',
  mail_from_address: '',
  mail_from_name: '',
})

const saveSettings = async () => {
  try {
    if (settings.value) {
      await emailSettingStore.updateSettings(form.value)
      alert('Email settings updated successfully!')
    } else {
      await emailSettingStore.saveSettings(form.value)
      alert('Email settings saved successfully!')
    }
    await loadSettings()
  } catch (error) {
    alert('Failed to save email settings: ' + (error.response?.data?.message || error.message))
  }
}

const loadSettings = async () => {
  loading.value = true
  try {
    const data = await emailSettingStore.fetchSettings()
    if (data) {
      settings.value = data
      form.value = {
        mail_host: data.mail_host || '',
        mail_port: data.mail_port || 587,
        mail_username: data.mail_username || '',
        mail_password: data.mail_password || '',
        mail_encryption: data.mail_encryption || 'tls',
        mail_from_address: data.mail_from_address || '',
        mail_from_name: data.mail_from_name || '',
      }
    }
  } catch (error) {
    // No settings yet
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadSettings()
})
</script>
