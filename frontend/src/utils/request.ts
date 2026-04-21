import axios, { type AxiosInstance, type InternalAxiosRequestConfig } from 'axios'
import { ElMessage } from 'element-plus'
import { useAuthStore } from '@/stores/auth'

const request: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE || '/api',
  timeout: 30_000,
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

request.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const auth = useAuthStore()
  if (auth.token) {
    config.headers.set('Authorization', `Bearer ${auth.token}`)
  }
  return config
})

request.interceptors.response.use(
  (response) => response.data,
  (error) => {
    const status = error?.response?.status
    const message =
      error?.response?.data?.message || error?.message || '请求失败，请稍后重试'

    if (status === 401) {
      const auth = useAuthStore()
      auth.clear()
      if (location.pathname !== '/login') {
        location.replace('/login')
      }
    } else if (status === 403) {
      ElMessage.error('没有权限执行该操作')
    } else if (status === 422) {
      // 交由调用方处理表单错误
    } else if (status && status >= 500) {
      ElMessage.error(message || '服务器异常')
    } else {
      ElMessage.error(message)
    }

    return Promise.reject(error)
  },
)

export default request
