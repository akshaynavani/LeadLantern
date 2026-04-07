import type {
  Contact,
  DashboardStats,
  Interaction,
  Paginated,
  RoutingDecision,
  RoutingRule,
  Task,
  Team,
  User,
} from "../types";

const BASE = (import.meta.env.VITE_API_URL ?? "").replace(/\/$/, "") + "/api";
const TOKEN_KEY = "leadlantern.token";

export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
    public errors?: Record<string, string[]>,
  ) {
    super(message);
  }
}

export const tokenStore = {
  get: () => localStorage.getItem(TOKEN_KEY),
  set: (t: string) => localStorage.setItem(TOKEN_KEY, t),
  clear: () => localStorage.removeItem(TOKEN_KEY),
};

let onUnauthorized: (() => void) | null = null;
export function setUnauthorizedHandler(fn: () => void) {
  onUnauthorized = fn;
}

async function request<T>(method: string, path: string, body?: unknown): Promise<T> {
  const token = tokenStore.get();
  const res = await fetch(BASE + path, {
    method,
    headers: {
      Accept: "application/json",
      ...(body !== undefined ? { "Content-Type": "application/json" } : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });

  if (res.status === 204) return undefined as T;

  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    if (res.status === 401) onUnauthorized?.();
    throw new ApiError(res.status, data.message ?? res.statusText, data.errors);
  }
  return data as T;
}

function qs(params: Record<string, string | number | undefined>): string {
  const entries = Object.entries(params).filter(([, v]) => v !== undefined && v !== "");
  return entries.length ? "?" + new URLSearchParams(entries.map(([k, v]) => [k, String(v)])).toString() : "";
}

export interface AuthPayload {
  user: User;
  permissions: string[];
}

export const api = {
  login: (email: string, password: string) =>
    request<AuthPayload & { token: string }>("POST", "/auth/login", { email, password }),
  me: () => request<AuthPayload>("GET", "/auth/me"),
  logout: () => request<void>("POST", "/auth/logout"),

  stats: (days = 30) => request<DashboardStats>("GET", `/dashboard/stats${qs({ days })}`),

  tasks: (filters: Record<string, string | number | undefined> = {}) =>
    request<Paginated<Task>>("GET", `/tasks${qs(filters)}`),
  task: (id: number) => request<Task>("GET", `/tasks/${id}`),
  createTask: (input: { title: string; description: string; channel?: string; contact_id?: number | null }) =>
    request<Task>("POST", "/tasks", input),
  updateTask: (id: number, input: Partial<Pick<Task, "status" | "title" | "description">> & { assignee_id?: number | null }) =>
    request<Task>("PATCH", `/tasks/${id}`, input),
  rerouteTask: (id: number) => request<RoutingDecision>("POST", `/tasks/${id}/route`),
  overrideTask: (id: number, input: { category: string; priority: string; team_id?: number | null; note?: string }) =>
    request<RoutingDecision>("POST", `/tasks/${id}/override`, input),

  contacts: (q?: string, page = 1) => request<Paginated<Contact>>("GET", `/contacts${qs({ q, page })}`),
  contact: (id: number) => request<Contact>("GET", `/contacts/${id}`),
  logInteraction: (
    contactId: number,
    input: { channel: string; direction?: string; subject?: string; body: string; create_task?: boolean },
  ) => request<{ interaction: Interaction; task_id: number | null }>("POST", `/contacts/${contactId}/interactions`, input),

  teams: () => request<Team[]>("GET", "/teams"),
  routingRules: () => request<RoutingRule[]>("GET", "/routing-rules"),
  updateRoutingRule: (id: number, input: Partial<Pick<RoutingRule, "team_id" | "default_priority" | "is_active">>) =>
    request<RoutingRule>("PATCH", `/routing-rules/${id}`, input),
};
