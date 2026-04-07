import type { ReactNode } from "react";
import { Navigate, useLocation } from "react-router-dom";
import { useAuth } from "../auth/AuthContext";

export function RequireAuth({ children }: { children: ReactNode }) {
  const { user, loading } = useAuth();
  const location = useLocation();

  if (loading) return <div className="center muted">Loading…</div>;
  if (!user) return <Navigate to="/login" replace state={{ from: location }} />;
  return <>{children}</>;
}

export function RequirePermission({ permission, children, fallback = "/tasks" }: { permission: string; children: ReactNode; fallback?: string }) {
  const { can } = useAuth();
  return can(permission) ? <>{children}</> : <Navigate to={fallback} replace />;
}

/** Hide UI that the current role cannot use (the API enforces it regardless). */
export function Can({ permission, children }: { permission: string; children: ReactNode }) {
  const { can } = useAuth();
  return can(permission) ? <>{children}</> : null;
}
