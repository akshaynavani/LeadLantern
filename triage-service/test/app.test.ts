import { describe, expect, it, vi } from "vitest";
import { createApp } from "../src/app.js";
import { loadConfig } from "../src/config.js";
import type { Classifier } from "../src/llm.js";

const config = loadConfig({ TRIAGE_SHARED_SECRET: "s3cret" });
const body = JSON.stringify({ title: "API down", description: "Every request returns 500 since 9am" });

function stubClassifier(impl: Classifier["classify"]): Classifier {
  return { model: "stub-model", classify: impl };
}

describe("triage app", () => {
  it("rejects requests without the shared secret", async () => {
    const app = createApp(config, null);
    const res = await app({ method: "POST", path: "/triage", headers: {}, body });
    expect(res.statusCode).toBe(401);
  });

  it("validates the payload", async () => {
    const app = createApp(config, null);
    const res = await app({ method: "POST", path: "/triage", headers: { "x-triage-secret": "s3cret" }, body: "{}" });
    expect(res.statusCode).toBe(422);
  });

  it("uses the LLM classifier when available", async () => {
    const classify = vi.fn().mockResolvedValue({
      category: "technical_support",
      priority: "urgent",
      sentiment: "negative",
      confidence: 0.94,
      rationale: "Production API outage.",
      suggested_actions: ["Page on-call"],
    });
    const app = createApp(config, stubClassifier(classify));
    const res = await app({ method: "POST", path: "/triage", headers: { "x-triage-secret": "s3cret" }, body });

    expect(res.statusCode).toBe(200);
    expect(res.body).toMatchObject({ category: "technical_support", source: "llm", model: "stub-model" });
    expect(classify).toHaveBeenCalledOnce();
  });

  it("falls back to keywords when the LLM throws", async () => {
    const app = createApp(config, stubClassifier(() => Promise.reject(new Error("rate limited"))));
    const res = await app({ method: "POST", path: "/triage", headers: { "x-triage-secret": "s3cret" }, body });

    expect(res.statusCode).toBe(200);
    expect(res.body).toMatchObject({ source: "fallback", model: null, category: "technical_support" });
  });

  it("serves a health check", async () => {
    const app = createApp(config, null);
    const res = await app({ method: "GET", path: "/health", headers: {}, body: undefined });
    expect(res.body).toEqual({ status: "ok", llm: "disabled" });
  });
});
