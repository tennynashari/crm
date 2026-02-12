import { defineStore } from 'pinia'
import api from '@/api/axios'

export const useInvoiceStore = defineStore('invoice', {
  state: () => ({
    invoices: [],
    currentInvoice: null,
    loading: false,
  }),

  actions: {
    async createInvoice(data) {
      this.loading = true
      try {
        const response = await api.post('/invoices', data)
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchInvoices(params = {}) {
      this.loading = true
      try {
        const response = await api.get('/invoices', { params })
        this.invoices = response.data.data
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchInvoice(id) {
      this.loading = true
      try {
        const response = await api.get(`/invoices/${id}`)
        this.currentInvoice = response.data
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateInvoice(id, data) {
      this.loading = true
      try {
        const response = await api.put(`/invoices/${id}`, data)
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteInvoice(id) {
      this.loading = true
      try {
        const response = await api.delete(`/invoices/${id}`)
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})
