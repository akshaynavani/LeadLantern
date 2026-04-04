import { ChatPromptTemplate } from "@langchain/core/prompts";
import type { TriageRequest } from "./schema.js";

export const SYSTEM_PROMPT = `You are the triage engine for a B2B CRM. Classify each inbound task so it can be routed to the right team.

Categories:
- sales_lead: prospects asking about pricing, demos, trials, buying, or new seats
- billing: invoices, charges, refunds, payment methods, tax/receipts
- technical_support: bugs, errors, outages, integrations failing, how-to questions about the product
- account_management: existing customers changing plans, adding users, renewals, contract admin (not at risk)
- churn_risk: customer signals intent to cancel, downgrade, switch to a competitor, or is very dissatisfied
- partnership: resellers, agencies, technology partners, co-marketing
- spam: unsolicited marketing, scams, irrelevant automated mail
- other: anything that fits none of the above

Priority guide:
- urgent: production outage, legal threats, explicit cancellation intent, large deal with a hard deadline
- high: blocked customer, payment errors, hot enterprise lead
- medium: normal requests
- low: informational, partnership feelers, spam

Use the recent interaction history for context (e.g. repeated complaints raise churn risk).
Be calibrated: lower your confidence when the message is ambiguous or very short.`;

export const triagePrompt = ChatPromptTemplate.fromMessages([
  ["system", SYSTEM_PROMPT],
  [
    "human",
    `Channel: {channel}
Contact: {contact}

Recent interactions (most recent first):
{history}

--- TASK ---
Title: {title}
Body:
{description}`,
  ],
]);

export function formatPromptInput(req: TriageRequest): Record<string, string> {
  const contact = req.contact
    ? [req.contact.name, req.contact.company && `@ ${req.contact.company}`, req.contact.lifecycle_stage && `(${req.contact.lifecycle_stage})`]
        .filter(Boolean)
        .join(" ")
    : "unknown";

  const history =
    req.recent_interactions.length === 0
      ? "(none)"
      : req.recent_interactions
          .map((i) => `- [${i.occurred_at ?? "?"}] ${i.direction ?? "inbound"} ${i.channel}: ${i.subject ? i.subject + " — " : ""}${i.body.slice(0, 300)}`)
          .join("\n");

  return {
    channel: req.channel,
    contact,
    history,
    title: req.title,
    description: req.description.slice(0, 6000),
  };
}
