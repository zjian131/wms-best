import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token'))

  const isAuthenticated = computed(() => !!token.value)

  const setUser = (userData) => {
    user.value = userData
  }

  const setToken = (newToken) => {
    token.value = newToken
    if (newToken) {
      localStorage.setItem('token', newToken)
      axios.defaults.headers.common['Authorization'] = `Bearer ${newToken}`
    } else {
      localStorage.removeItem('token')
      delete axios.defaults.headers.common['Authorization']
    }
  }

  const login = async (credentials) => {
    const response = await axios.post('/api/auth/login', credentials)
    setToken(response.data.token)
    setUser(response.data.user)
    return response.data
  }

  const logout = async () => {
    try {
      await axios.post('/api/auth/logout')
    } catch (error) {
      console.error('Logout error:', error)
    }
    setToken(null)
    setUser(null)
  }

  const fetchUser = async () => {
    if (!token.value) return null
    const response = await axios.get('/api/auth/me')
    setUser(response.data)
    return response.data
  }

  return {
    user,
    token,
    isAuthenticated,
    login,
    logout,
    fetchUser,
    setUser,
    setToken
  }
})
