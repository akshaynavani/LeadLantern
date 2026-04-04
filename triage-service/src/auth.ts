import { timingSafeEqual } from "node:crypto";

/** Constant-time comparison of the X-Triage-Secret header. */
export function isAuthorized(expected: string | undefined, provided: string | undefined): boolean {
  if (!expected) return true; // auth disabled (local dev only)
  if (!provided) return false;
  const a = Buffer.from(expected);
  const b = Buffer.from(provided);
  return a.length === b.length && timingSafeEqual(a, b);
}
