import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from "react";
import { api, setUnauthorizedHandler, tokenStore } from "../api/client";
import type { User } from "../types";

interface AuthState {
  user: User | null;
  permissions: string[];
  loading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  can: (permission: string) => boolean;
}

const AuthContext = createContext<AuthState | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [permissions, setPermissions] = useState<string[]>([]);
  const [loading, setLoading] = useState(true);

  const reset = useCallback(() => {
    tokenStore.clear();
    setUser(null);
    setPermissions([]);
  }, []);

  useEffect(() => {
    setUnauthorizedHandler(reset);
    if (!tokenStore.get()) {
      setLoading(false);
      return;
    }
    api
      .me()
      .then((res) => {
        setUser(res.user);
        setPermissions(res.permissions);
      })
      .catch(reset)
      .finally(() => setLoading(false));
  }, [reset]);

  const value = useMemo<AuthState>(
    () => ({
      user,
      permissions,
      loading,
      async login(email, password) {
        const res = await api.login(email, password);
        tokenStore.set(res.token);
        setUser(res.user);
        setPermissions(res.permissions);
      },
      async logout() {
        await api.logout().catch(() => undefined);
        reset();
      },
      can: (p) => permissions.includes("*") || permissions.includes(p),
    }),
    [user, permissions, loading, reset],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthState {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used inside <AuthProvider>");
  return ctx;
}
