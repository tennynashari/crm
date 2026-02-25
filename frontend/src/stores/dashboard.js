import { defineStore } from 'pinia'
import api from '@/api/axios'

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    stats: null,
    loading: false,
    todayActions: [],
    todayActionsLoading: false,
    todayActionsPagination: {
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
    },
    weekMeetings: [],
    weekMeetingsLoading: false,
    weekMeetingsPagination: {
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
    },
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

    async fetchTodayActions(page = 1, perPage = 10) {
      this.todayActionsLoading = true
      try {
        const response = await api.get('/dashboard/today-actions', {
          params: { page, per_page: perPage }
        })
        this.todayActions = response.data.data
        this.todayActionsPagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          per_page: response.data.per_page,
          total: response.data.total,
        }
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.todayActionsLoading = false
      }
    },

    async fetchWeekMeetings(page = 1, perPage = 10) {
      this.weekMeetingsLoading = true
      try {
        const response = await api.get('/dashboard/week-meetings', {
          params: { page, per_page: perPage }
        })
        this.weekMeetings = response.data.data
        this.weekMeetingsPagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          per_page: response.data.per_page,
          total: response.data.total,
        }
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.weekMeetingsLoading = false
      }
    },
  },
})
