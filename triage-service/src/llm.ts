import { ChatOpenAI } from "@langchain/openai";
import type { Runnable } from "@langchain/core/runnables";
import type { Config } from "./config.js";
import { formatPromptInput, triagePrompt } from "./prompt.js";
import { ClassificationSchema, type Classification, type TriageRequest } from "./schema.js";

export interface Classifier {
  model: string;
  classify(req: TriageRequest): Promise<Classification>;
}

/**
 * LangChain chain: prompt -> chat model with Zod-enforced structured output.
 */
export function createLlmClassifier(config: Config): Classifier | null {
  if (!config.openAiApiKey) return null;

  const llm = new ChatOpenAI({
    apiKey: config.openAiApiKey,
    model: config.model,
    temperature: 0,
    timeout: config.llmTimeoutMs,
    maxRetries: 1,
    configuration: config.baseUrl ? { baseURL: config.baseUrl } : undefined,
  });

  const chain: Runnable<Record<string, string>, Classification> = triagePrompt.pipe(
    llm.withStructuredOutput(ClassificationSchema, { name: "classify_crm_task" }),
  );

  return {
    model: config.model,
    async classify(req) {
      const raw = await chain.invoke(formatPromptInput(req));
      // Re-validate: providers occasionally drift from the schema.
      return ClassificationSchema.parse(raw);
    },
  };
}
