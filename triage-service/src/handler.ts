import type { APIGatewayProxyEventV2, APIGatewayProxyResultV2 } from "aws-lambda";
import { createApp, lowerCaseHeaders } from "./app.js";
import { loadConfig } from "./config.js";
import { createLlmClassifier } from "./llm.js";

// Initialised once per Lambda container and reused across invocations.
const config = loadConfig();
const app = createApp(config, createLlmClassifier(config));

/** Lambda Function URL / HTTP API (payload v2) entrypoint. */
export async function handler(event: APIGatewayProxyEventV2): Promise<APIGatewayProxyResultV2> {
  const body = event.body && event.isBase64Encoded ? Buffer.from(event.body, "base64").toString("utf8") : event.body;

  const res = await app({
    method: event.requestContext.http.method,
    path: event.rawPath,
    headers: lowerCaseHeaders(event.headers ?? {}),
    body,
  });

  return {
    statusCode: res.statusCode,
    headers: { "content-type": "application/json" },
    body: JSON.stringify(res.body),
  };
}
