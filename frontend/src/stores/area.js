import { defineStore } from 'pinia'
import api from '@/api/axios'

export const useAreaStore = defineStore('area', {
  state: () => ({
    areas: [],
    loading: false,
  }),

  actions: {
    async fetchAreas() {
      this.loading = true
      try {
        const response = await api.get('/api/areas')
        this.areas = response.data
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchArea(id) {
      this.loading = true
      try {
        const response = await api.get(`/api/areas/${id}`)
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async createArea(data) {
      this.loading = true
      try {
        const response = await api.post('/api/areas', data)
        this.areas.push(response.data)
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateArea(id, data) {
      this.loading = true
      try {
        const response = await api.put(`/api/areas/${id}`, data)
        const index = this.areas.findIndex(a => a.id === id)
        if (index !== -1) {
          this.areas[index] = response.data
        }
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteArea(id) {
      this.loading = true
      try {
        await api.delete(`/api/areas/${id}`)
        this.areas = this.areas.filter(a => a.id !== id)
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})
