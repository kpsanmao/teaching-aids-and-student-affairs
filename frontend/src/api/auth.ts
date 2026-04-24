import request from '@/utils/request'
import type { AuthUser } from '@/stores/auth'

export interface LoginPayload {
  email: string
  password: string
  device_name?: string
}

export interface LoginResponse {
  success: boolean
  data: {
    token: string
    user: AuthUser
  }
  message: string
}

export interface MeResponse {
  success: boolean
  data: AuthUser
}

export function loginApi(payload: LoginPayload) {
  return request.post<unknown, LoginResponse>('/auth/login', payload)
}

export function logoutApi() {
  return request.post<unknown, { success: boolean; message: string }>('/auth/logout')
}

export function meApi() {
  return request.get<unknown, MeResponse>('/auth/me')
}
