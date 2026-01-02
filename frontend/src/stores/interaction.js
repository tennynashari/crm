import { defineStore } from 'pinia'
import api from '@/api/axios'

export const useInteractionStore = defineStore('interaction', {
  state: () => ({
    interactions: [],
    loading: false,
  }),

  actions: {
    async createInteraction(data) {
      this.loading = true
      try {
        const response = await api.post('/api/interactions', data)
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchInteractions(params = {}) {
      this.loading = true
      try {
        const response = await api.get('/api/interactions', { params })
        this.interactions = response.data.data
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteInteraction(id) {
      this.loading = true
      try {
        const response = await api.delete(`/api/interactions/${id}`)
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateInteraction(id, data) {
      this.loading = true
      try {
        const response = await api.put(`/api/interactions/${id}`, data)
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})
