import { redirect } from 'next/navigation';

/**
 * ASN Index Page
 * Redirect /asn ke /asn/dashboard
 * Server-side redirect (zero flash)
 */
export default function AsnIndexPage() {
  redirect('/asn/dashboard');
}
