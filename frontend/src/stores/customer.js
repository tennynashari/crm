import { defineStore } from 'pinia'
import api from '@/api/axios'

export const useCustomerStore = defineStore('customer', {
  state: () => ({
    customers: [],
    currentCustomer: null,
    loading: false,
    pagination: {
      current_page: 1,
      per_page: 15,
      total: 0,
    },
    filters: {
      search: '',
      area_id: null,
      lead_status_id: null,
      assigned_sales_id: null,
      source: null,
      sort_by: 'next_action_date',
      sort_order: 'asc',
    },
  }),

  actions: {
    async fetchCustomers(page = 1) {
      this.loading = true
      try {
        const params = {
          page,
          per_page: this.pagination.per_page,
          ...this.filters,
        }
        const response = await api.get('/customers', { params })
        this.customers = response.data.data
        this.pagination = {
          current_page: response.data.current_page,
          per_page: response.data.per_page,
          total: response.data.total,
          last_page: response.data.last_page,
        }
      } catch (error) {
        if (error.response?.status === 401) {
          // Session expired, redirect to login
          localStorage.removeItem('user')
          window.location.href = '/login'
        }
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchCustomer(id) {
      this.loading = true
      try {
        const response = await api.get(`/customers/${id}`)
        this.currentCustomer = response.data
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async createCustomer(data) {
      this.loading = true
      try {
        const response = await api.post('/customers', data)
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateCustomer(id, data) {
      this.loading = true
      try {
        const response = await api.put(`/customers/${id}`, data)
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteCustomer(id) {
      this.loading = true
      try {
        const response = await api.delete(`/customers/${id}`)
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateNextAction(id, data) {
      this.loading = true
      try {
        const response = await api.post(`/customers/${id}/next-action`, data)
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    setFilters(filters) {
      this.filters = { ...this.filters, ...filters }
    },

    resetFilters() {
      this.filters = {
        search: '',
        area_id: null,
        lead_status_id: null,
        assigned_sales_id: null,
        source: null,
        next_action_status: null,
      }
    },
  },
})
