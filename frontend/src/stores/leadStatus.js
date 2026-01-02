import { defineStore } from 'pinia'
import api from '@/api/axios'

export const useLeadStatusStore = defineStore('leadStatus', {
  state: () => ({
    statuses: [],
    loading: false,
  }),

  actions: {
    async fetchStatuses() {
      this.loading = true
      try {
        const response = await api.get('/api/lead-statuses')
        this.statuses = response.data
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})
