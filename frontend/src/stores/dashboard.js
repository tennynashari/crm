import { defineStore } from 'pinia'
import api from '@/api/axios'

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    stats: null,
    loading: false,
    todayActions: [],
    todayActionsLoading: false,
  }),

  actions: {
    async fetchStats() {
      this.loading = true
      try {
        const response = await api.get('/dashboard/stats')
        this.stats = response.data
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchTodayActions() {
      this.todayActionsLoading = true
      try {
        const response = await api.get('/dashboard/today-actions')
        this.todayActions = response.data
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.todayActionsLoading = false
      }
    },
  },
})
