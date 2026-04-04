import { isAuthorized } from "./auth.js";
import type { Config } from "./config.js";
import type { Classifier } from "./llm.js";
import { TriageRequestSchema } from "./schema.js";
import { triage } from "./triage.js";

export interface AppRequest {
  method: string;
  path: string;
  headers: Record<string, string | undefined>;
  body: string | undefined;
}

export interface AppResponse {
  statusCode: number;
  body: unknown;
}

/**
 * Transport-agnostic request handler shared by the Lambda entrypoint and
 * the local Node HTTP server.
 */
export function createApp(config: Config, classifier: Classifier | null) {
  return async function handle(req: AppRequest): Promise<AppResponse> {
    const path = req.path.replace(/\/+$/, "") || "/";

    if (req.method === "GET" && path === "/health") {
      return { statusCode: 200, body: { status: "ok", llm: classifier ? classifier.model : "disabled" } };
    }

    if (req.method !== "POST" || path !== "/triage") {
      return { statusCode: 404, body: { error: "Not found" } };
    }

    const secret = req.headers["x-triage-secret"];
    if (!isAuthorized(config.sharedSecret, secret)) {
      return { statusCode: 401, body: { error: "Unauthorized" } };
    }

    let json: unknown;
    try {
      json = JSON.parse(req.body ?? "");
    } catch {
      return { statusCode: 400, body: { error: "Body must be valid JSON" } };
    }

    const parsed = TriageRequestSchema.safeParse(json);
    if (!parsed.success) {
      return { statusCode: 422, body: { error: "Invalid triage request", issues: parsed.error.issues } };
    }

    const result = await triage(parsed.data, classifier);
    console.log(
      JSON.stringify({
        event: "triage",
        task_id: parsed.data.task_id,
        category: result.category,
        priority: result.priority,
        confidence: result.confidence,
        source: result.source,
        latency_ms: result.latency_ms,
      }),
    );
    return { statusCode: 200, body: result };
  };
}

export function lowerCaseHeaders(headers: Record<string, string | string[] | undefined>): Record<string, string | undefined> {
  const out: Record<string, string | undefined> = {};
  for (const [k, v] of Object.entries(headers)) out[k.toLowerCase()] = Array.isArray(v) ? v[0] : v;
  return out;
}
