import { defineStore } from 'pinia'
import api from '@/api/axios'

export const useContactStore = defineStore('contact', {
  state: () => ({
    contacts: [],
    loading: false,
  }),

  actions: {
    async fetchContacts(customerId) {
      this.loading = true
      try {
        const response = await api.get('/contacts', {
          params: { customer_id: customerId },
        })
        this.contacts = response.data
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchContact(id) {
      this.loading = true
      try {
        const response = await api.get(`/contacts/${id}`)
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async createContact(data) {
      this.loading = true
      try {
        const response = await api.post('/contacts', data)
        this.contacts.push(response.data)
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateContact(id, data) {
      this.loading = true
      try {
        const response = await api.put(`/contacts/${id}`, data)
        const index = this.contacts.findIndex(c => c.id === parseInt(id))
        if (index !== -1) {
          this.contacts[index] = response.data
        }
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteContact(id) {
      this.loading = true
      try {
        await api.delete(`/contacts/${id}`)
        this.contacts = this.contacts.filter(c => c.id !== parseInt(id))
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})
