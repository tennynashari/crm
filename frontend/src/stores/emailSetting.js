import { defineStore } from 'pinia'
import api from '@/api/axios'

export const useEmailSettingStore = defineStore('emailSetting', {
  state: () => ({
    settings: null,
    loading: false,
  }),

  actions: {
    async fetchSettings() {
      this.loading = true
      try {
        const response = await api.get('/email-settings')
        this.settings = response.data
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async saveSettings(data) {
      this.loading = true
      try {
        const response = await api.post('/email-settings', data)
        this.settings = response.data.setting
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateSettings(data) {
      this.loading = true
      try {
        const response = await api.put('/email-settings', data)
        this.settings = response.data.setting
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async sendEmail(data) {
      this.loading = true
      try {
        const response = await api.post('/send-email', data)
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async sendEmailWithAttachments(formData) {
      this.loading = true
      try {
        const response = await api.post('/send-email', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})
