export interface Config {
  sharedSecret: string | undefined;
  openAiApiKey: string | undefined;
  model: string;
  baseUrl: string | undefined;
  llmTimeoutMs: number;
  port: number;
}

export function loadConfig(env: NodeJS.ProcessEnv = process.env): Config {
  return {
    sharedSecret: env.TRIAGE_SHARED_SECRET || undefined,
    openAiApiKey: env.OPENAI_API_KEY || undefined,
    model: env.OPENAI_MODEL || "gpt-4o-mini",
    baseUrl: env.OPENAI_BASE_URL || undefined,
    llmTimeoutMs: Number(env.LLM_TIMEOUT_MS ?? 15000),
    port: Number(env.PORT ?? 3001),
  };
}
