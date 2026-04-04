import { z } from "zod";

// Must stay in sync with backend/app/Enums/TaskCategory.php
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

export const PRIORITIES = ["low", "medium", "high", "urgent"] as const;
export const SENTIMENTS = ["positive", "neutral", "negative"] as const;

export const InteractionSchema = z.object({
  channel: z.string(),
  direction: z.string().optional(),
  subject: z.string().nullish(),
  body: z.string(),
  occurred_at: z.string().nullish(),
});

export const TriageRequestSchema = z.object({
  task_id: z.number().int().optional(),
  title: z.string().min(1).max(500),
  description: z.string().min(1).max(20000),
  channel: z.string().default("email"),
  contact: z
    .object({
      name: z.string().nullish(),
      company: z.string().nullish(),
      lifecycle_stage: z.string().nullish(),
    })
    .nullish(),
  recent_interactions: z.array(InteractionSchema).max(20).default([]),
});

export type TriageRequest = z.infer<typeof TriageRequestSchema>;

/** Shape the LLM is forced to produce via structured output. */
export const ClassificationSchema = z.object({
  category: z.enum(CATEGORIES).describe("Which CRM queue this task belongs to"),
  priority: z.enum(PRIORITIES).describe("Urgency: urgent = revenue/outage/churn risk needing same-day action"),
  sentiment: z.enum(SENTIMENTS).describe("Customer sentiment expressed in the message"),
  confidence: z.number().min(0).max(1).describe("Your confidence in the category, 0-1"),
  rationale: z.string().max(400).describe("One or two sentences explaining the decision"),
  suggested_actions: z.array(z.string().max(160)).max(4).describe("Concrete next steps for the assignee"),
});

export type Classification = z.infer<typeof ClassificationSchema>;

export interface TriageResponse extends Classification {
  source: "llm" | "fallback";
  model: string | null;
  latency_ms: number;
}
