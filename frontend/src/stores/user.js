import { defineStore } from 'pinia'
import api from '@/api/axios'

export const useUserStore = defineStore('user', {
  state: () => ({
    users: [],
    salesUsers: [],
    loading: false,
  }),

  actions: {
    async fetchUsers() {
      this.loading = true
      try {
        const response = await api.get('/api/users')
        this.users = response.data
        return response.data
      } catch (error) {
        console.error('Failed to fetch users:', error)
        return []
      } finally {
        this.loading = false
      }
    },

    async fetchSalesUsers() {
      this.loading = true
      try {
        const response = await api.get('/api/users?role=sales')
        this.salesUsers = response.data
        return response.data
      } catch (error) {
        console.error('Failed to fetch sales users:', error)
        // Return empty array if endpoint doesn't exist
        return []
      } finally {
        this.loading = false
      }
    },

    async fetchUser(id) {
      this.loading = true
      try {
        const response = await api.get(`/api/users/${id}`)
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async createUser(data) {
      this.loading = true
      try {
        const response = await api.post('/api/users', data)
        if (data.role === 'sales') {
          this.salesUsers.push(response.data)
        } else {
          this.users.push(response.data)
        }
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateUser(id, data) {
      this.loading = true
      try {
        const response = await api.put(`/api/users/${id}`, data)
        
        // Update in salesUsers array
        const salesIndex = this.salesUsers.findIndex(u => u.id === parseInt(id))
        if (salesIndex !== -1) {
          this.salesUsers[salesIndex] = response.data
        }
        
        // Update in users array
        const userIndex = this.users.findIndex(u => u.id === parseInt(id))
        if (userIndex !== -1) {
          this.users[userIndex] = response.data
        }
        
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteUser(id) {
      this.loading = true
      try {
        await api.delete(`/api/users/${id}`)
        this.salesUsers = this.salesUsers.filter(u => u.id !== parseInt(id))
        this.users = this.users.filter(u => u.id !== parseInt(id))
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})
