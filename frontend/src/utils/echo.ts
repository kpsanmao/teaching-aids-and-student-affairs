import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

let echoInstance: Echo<'reverb'> | null = null

export function createEcho(token?: string | null): Echo<'reverb'> {
  if (echoInstance) return echoInstance

  ;(window as unknown as { Pusher: typeof Pusher }).Pusher = Pusher

  echoInstance = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
    forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
    auth: token
      ? { headers: { Authorization: `Bearer ${token}` } }
      : undefined,
  })

  return echoInstance
}

export function destroyEcho(): void {
  echoInstance?.disconnect()
  echoInstance = null
}
