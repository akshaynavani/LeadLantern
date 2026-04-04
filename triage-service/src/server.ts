import { createServer } from "node:http";
import { createApp, lowerCaseHeaders } from "./app.js";
import { loadConfig } from "./config.js";
import { createLlmClassifier } from "./llm.js";

// Local development server mirroring the Lambda handler.
const config = loadConfig();
const classifier = createLlmClassifier(config);
const app = createApp(config, classifier);

const server = createServer(async (req, res) => {
  const chunks: Buffer[] = [];
  for await (const chunk of req) chunks.push(chunk as Buffer);

  try {
    const result = await app({
      method: req.method ?? "GET",
      path: new URL(req.url ?? "/", "http://localhost").pathname,
      headers: lowerCaseHeaders(req.headers),
      body: Buffer.concat(chunks).toString("utf8"),
    });
    res.writeHead(result.statusCode, { "content-type": "application/json" });
    res.end(JSON.stringify(result.body));
  } catch (err) {
    console.error(err);
    res.writeHead(500, { "content-type": "application/json" });
    res.end(JSON.stringify({ error: "Internal error" }));
  }
});

server.listen(config.port, () => {
  console.log(`triage-service listening on :${config.port} (llm: ${classifier ? config.model : "disabled, keyword fallback"})`);
});
