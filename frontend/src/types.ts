export type Priority = "low" | "medium" | "high" | "urgent";
export type TaskStatus = "new" | "routed" | "needs_review" | "in_progress" | "resolved";
export type DecisionSource = "llm" | "fallback" | "manual";

export const CATEGORIES = [
  "sales_lead",
  "billing",
  "technical_support",
  "account_management",
  "churn_risk",
  "partnership",
  "spam",
  "other",
] as const;
export type Category = (typeof CATEGORIES)[number];
export const PRIORITIES: Priority[] = ["low", "medium", "high", "urgent"];
export const STATUSES: TaskStatus[] = ["new", "routed", "needs_review", "in_progress", "resolved"];

export interface TeamRef {
  id: number;
  name: string;
  slug: string;
}

export interface Team extends TeamRef {
  description: string | null;
  open_tasks_count?: number;
}

export interface User {
  id: number;
  name: string;
  email: string;
  role: "admin" | "manager" | "agent";
  team_id: number | null;
  team?: Team | null;
  is_active: boolean;
}

export interface RoutingDecision {
  id: number;
  task_id: number;
  source: DecisionSource;
  category: Category;
  priority: Priority;
  sentiment: string | null;
  confidence: number;
  rationale: string | null;
  suggested_actions: string[];
  team?: TeamRef | null;
  model: string | null;
  latency_ms: number | null;
  decided_by?: { id: number; name: string } | null;
  created_at: string;
}

export interface Task {
  id: number;
  title: string;
  description: string;
  channel: string;
  status: TaskStatus;
  category: Category | null;
  priority: Priority | null;
  team?: TeamRef | null;
  assignee?: { id: number; name: string } | null;
  contact?: { id: number; full_name: string; company: string | null } | null;
  interaction_id: number | null;
  latest_decision?: RoutingDecision | null;
  routing_decisions?: RoutingDecision[];
  due_at: string | null;
  routed_at: string | null;
  created_at: string;
}

export interface Interaction {
  id: number;
  contact_id: number;
  channel: string;
  direction: "inbound" | "outbound";
  subject: string | null;
  body: string;
  occurred_at: string;
  user?: { id: number; name: string } | null;
  task_id?: number | null;
}

export interface Contact {
  id: number;
  first_name: string;
  last_name: string | null;
  full_name: string;
  email: string | null;
  phone: string | null;
  company: string | null;
  lifecycle_stage: string;
  interactions_count?: number;
  interactions?: Interaction[];
  tasks?: Task[];
}

export interface RoutingRule {
  id: number;
  category: Category;
  team_id: number;
  team?: TeamRef;
  default_priority: Priority | null;
  is_active: boolean;
}

export interface Paginated<T> {
  data: T[];
  meta: { current_page: number; last_page: number; total: number; per_page: number };
}

export interface DashboardStats {
  window_days: number;
  totals: { tasks: number; open: number; needs_review: number };
  by_status: Record<string, number>;
  by_category: Record<string, number>;
  by_priority: Record<string, number>;
  by_team: Record<string, number>;
  routing: {
    by_source: Record<string, number>;
    avg_confidence: number;
    avg_latency_ms: number;
    override_rate: number;
  };
}
