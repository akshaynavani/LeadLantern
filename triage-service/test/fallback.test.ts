import { describe, expect, it } from "vitest";
import { classifyWithKeywords } from "../src/fallback.js";
import { TriageRequestSchema } from "../src/schema.js";

const req = (title: string, description: string) => TriageRequestSchema.parse({ title, description });

describe("classifyWithKeywords", () => {
  it("detects billing issues", () => {
    const r = classifyWithKeywords(req("Charged twice", "My card was charged twice, please refund the invoice."));
    expect(r.category).toBe("billing");
    expect(r.priority).toBe("high");
    expect(r.sentiment).toBe("negative");
  });

  it("flags churn risk as urgent", () => {
    const r = classifyWithKeywords(req("Renewal", "We are frustrated and moving to a competitor, please cancel."));
    expect(r.category).toBe("churn_risk");
    expect(r.priority).toBe("urgent");
  });

  it("recognises sales leads", () => {
    const r = classifyWithKeywords(req("Demo request", "Can we get pricing and a demo for 50 seats?"));
    expect(r.category).toBe("sales_lead");
  });

  it("returns low-confidence other when nothing matches", () => {
    const r = classifyWithKeywords(req("hello", "just saying hi"));
    expect(r.category).toBe("other");
    expect(r.confidence).toBeLessThan(0.5);
  });

  it("never exceeds the fallback confidence cap", () => {
    const r = classifyWithKeywords(req("pricing quote demo trial", "pricing quote demo trial seats purchase buy evaluate"));
    expect(r.confidence).toBeLessThanOrEqual(0.75);
  });
});
