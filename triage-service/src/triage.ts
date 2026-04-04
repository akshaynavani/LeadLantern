import { classifyWithKeywords } from "./fallback.js";
import type { Classifier } from "./llm.js";
import type { TriageRequest, TriageResponse } from "./schema.js";

export interface TriageLogger {
  warn(msg: string, meta?: Record<string, unknown>): void;
}

/**
 * Try the LLM classifier; on absence or failure, degrade to keywords.
 */
export async function triage(
  req: TriageRequest,
  classifier: Classifier | null,
  logger: TriageLogger = console,
  now: () => number = Date.now,
): Promise<TriageResponse> {
  const started = now();

  if (classifier) {
    try {
      const result = await classifier.classify(req);
      return { ...result, source: "llm", model: classifier.model, latency_ms: now() - started };
    } catch (err) {
      logger.warn("LLM classification failed, using keyword fallback", {
        task_id: req.task_id,
        error: err instanceof Error ? err.message : String(err),
      });
    }
  }

  return { ...classifyWithKeywords(req), source: "fallback", model: null, latency_ms: now() - started };
}
