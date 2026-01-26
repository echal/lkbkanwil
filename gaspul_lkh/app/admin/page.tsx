import { redirect } from 'next/navigation';

/**
 * Admin Index Page
 * Redirect /admin ke /admin/dashboard
 * Server-side redirect (zero flash)
 */
export default function AdminIndexPage() {
  redirect('/admin/dashboard');
}
