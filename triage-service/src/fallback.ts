import type { Classification, TriageRequest } from "./schema.js";

type Category = Classification["category"];

/**
 * Deterministic keyword classifier used when no LLM is configured or the
 * LLM call fails. Deliberately conservative: confidence stays low so the
 * backend sends most of these through human review.
 */
const KEYWORDS: Record<Exclude<Category, "other">, string[]> = {
  sales_lead: ["pricing", "quote", "demo", "trial", "seats", "purchase", "buy", "evaluate", "proposal"],
  billing: ["invoice", "charged", "refund", "payment", "billing", "receipt", "credit card", "overcharge"],
  technical_support: ["error", "bug", "500", "crash", "not working", "broken", "api", "login issue", "outage", "down"],
  account_management: ["upgrade", "add users", "renewal", "contract", "account owner", "onboarding"],
  churn_risk: ["cancel", "cancellation", "competitor", "frustrated", "switching", "not renewing", "disappointed"],
  partnership: ["partner", "reseller", "integration partner", "affiliate", "alliance", "co-marketing"],
  spam: ["unsubscribe", "seo services", "crypto", "winner", "lottery", "backlinks"],
};

const URGENT = ["urgent", "asap", "immediately", "outage", "down", "cancel", "legal", "lawyer"];
const HIGH = ["today", "this week", "blocked", "frustrated", "charged twice", "enterprise"];
const NEGATIVE = ["frustrated", "angry", "disappointed", "terrible", "unacceptable", "cancel", "broken", "charged twice"];
const POSITIVE = ["love", "great", "thanks", "excited", "happy", "impressed"];

function countHits(text: string, words: string[]): number {
  return words.reduce((n, w) => (text.includes(w) ? n + 1 : n), 0);
}

export function classifyWithKeywords(req: TriageRequest): Classification {
  const text = `${req.title}\n${req.description}`.toLowerCase();

  const scores = (Object.keys(KEYWORDS) as Array<keyof typeof KEYWORDS>)
    .map((category) => ({ category, hits: countHits(text, KEYWORDS[category]) }))
    .sort((a, b) => b.hits - a.hits);

  const best = scores[0];
  const runnerUp = scores[1];
  const category: Category = best && best.hits > 0 ? best.category : "other";

  // Margin over the runner-up drives confidence, capped well below LLM levels.
  const margin = best && best.hits > 0 ? best.hits - (runnerUp?.hits ?? 0) : 0;
  const confidence = category === "other" ? 0.2 : Math.min(0.75, 0.4 + 0.12 * best!.hits + 0.08 * margin);

  let priority: Classification["priority"] = "medium";
  if (countHits(text, URGENT) > 0 || category === "churn_risk") priority = "urgent";
  else if (countHits(text, HIGH) > 0) priority = "high";
  else if (category === "spam" || category === "partnership") priority = "low";

  const neg = countHits(text, NEGATIVE);
  const pos = countHits(text, POSITIVE);
  const sentiment: Classification["sentiment"] = neg > pos ? "negative" : pos > neg ? "positive" : "neutral";

  const matched = best && best.hits > 0 ? KEYWORDS[best.category].filter((w) => text.includes(w)) : [];

  return {
    category,
    priority,
    sentiment,
    confidence: Number(confidence.toFixed(3)),
    rationale:
      matched.length > 0
        ? `Keyword match (${matched.slice(0, 4).join(", ")}) suggests ${category.replace("_", " ")}.`
        : "No strong keyword signal; needs human review.",
    suggested_actions: category === "other" ? ["Review manually and assign a category"] : [],
  };
}
