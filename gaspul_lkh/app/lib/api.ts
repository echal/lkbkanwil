const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

// Global logout callback (akan diset oleh AuthProvider)
let globalLogoutCallback: (() => void) | null = null;

export function setGlobalLogoutCallback(callback: () => void) {
  globalLogoutCallback = callback;
}

/**
 * API Fetch Wrapper dengan auto 401 handling
 */
export async function apiFetch<T = any>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  // Get token dari localStorage
  const token = typeof window !== 'undefined'
    ? localStorage.getItem('access_token')
    : null;

  // Setup headers
  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    ...options.headers,
  };

  // Attach token jika ada
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  // Fetch request
  const response = await fetch(`${API_URL}${endpoint}`, {
    ...options,
    headers,
  });

  // Auto logout pada 401 Unauthorized
  if (response.status === 401) {
    // Clear token
    if (typeof window !== 'undefined') {
      localStorage.removeItem('access_token');
      localStorage.removeItem('user');
    }

    // Trigger global logout callback
    if (globalLogoutCallback) {
      globalLogoutCallback();
    }

    throw new Error('Unauthorized - Session expired');
  }

  // Handle non-OK responses
  if (!response.ok) {
    const errorData = await response.json().catch(() => ({}));
    throw new Error(errorData.message || `HTTP Error: ${response.status}`);
  }

  // Return parsed JSON
  return response.json();
}
