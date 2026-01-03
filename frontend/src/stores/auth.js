import { defineStore } from 'pinia'
import api from '@/api/axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user')) || null,
    isAuthenticated: !!localStorage.getItem('user'),
    loading: false,
  }),

  actions: {
    async getCsrfToken() {
      await api.get('/csrf-cookie')
    },

    async login(credentials) {
      this.loading = true
      try {
        await this.getCsrfToken()
        const response = await api.post('/login', credentials)
        this.user = response.data.user
        this.isAuthenticated = true
        localStorage.setItem('user', JSON.stringify(response.data.user))
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async logout() {
      this.loading = true
      try {
        await api.post('/logout')
      } catch (error) {
        // Ignore logout errors, just clear local state
        console.log('Logout request failed, clearing local state')
      } finally {
        // Always clear auth state regardless of API response
        this.user = null
        this.isAuthenticated = false
        localStorage.removeItem('user')
        this.loading = false
      }
    },

    async fetchUser() {
      this.loading = true
      try {
        const response = await api.get('/user')
        this.user = response.data.user
        this.isAuthenticated = true
        localStorage.setItem('user', JSON.stringify(response.data.user))
        return response.data.user
      } catch (error) {
        this.user = null
        this.isAuthenticated = false
        localStorage.removeItem('user')
        throw error
      } finally {
        this.loading = false
      }
    },

    async register(userData) {
      this.loading = true
      try {
        await this.getCsrfToken()
        const response = await api.post('/register', userData)
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})
