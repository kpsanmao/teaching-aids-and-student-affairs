import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

export interface AuthUser {
  id: number
  name: string
  email: string
  role: 'teacher' | 'admin'
  avatar?: string | null
}

export const useAuthStore = defineStore(
  'auth',
  () => {
    const token = ref<string | null>(null)
    const user = ref<AuthUser | null>(null)

    const isLoggedIn = computed(() => !!token.value)
    const isAdmin = computed(() => user.value?.role === 'admin')

    function setAuth(payload: { token: string; user: AuthUser }) {
      token.value = payload.token
      user.value = payload.user
    }

    function clear() {
      token.value = null
      user.value = null
    }

    return { token, user, isLoggedIn, isAdmin, setAuth, clear }
  },
  {
    persist: {
      key: 'tp_auth',
      pick: ['token', 'user'],
    },
  },
)
