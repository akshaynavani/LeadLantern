import { describe, expect, it } from "vitest";
import { formatPromptInput } from "../src/prompt.js";
import { TriageRequestSchema } from "../src/schema.js";

describe("formatPromptInput", () => {
  it("renders contact and history context", () => {
    const input = formatPromptInput(
      TriageRequestSchema.parse({
        title: "Follow-up",
        description: "Still waiting on a fix",
        channel: "chat",
        contact: { name: "Lena Ortiz", company: "Globex", lifecycle_stage: "customer" },
        recent_interactions: [{ channel: "email", direction: "inbound", subject: "Bug", body: "Sync fails" }],
      }),
    );

    expect(input.contact).toBe("Lena Ortiz @ Globex (customer)");
    expect(input.history).toContain("inbound email: Bug — Sync fails");
    expect(input.channel).toBe("chat");
  });

  it("handles missing context", () => {
    const input = formatPromptInput(TriageRequestSchema.parse({ title: "t", description: "d" }));
    expect(input.contact).toBe("unknown");
    expect(input.history).toBe("(none)");
  });
});
