import { redirect } from 'next/navigation';

/**
 * Atasan Index Page
 * Redirect /atasan ke /atasan/dashboard
 * Server-side redirect (zero flash)
 */
export default function AtasanIndexPage() {
  redirect('/atasan/dashboard');
}
