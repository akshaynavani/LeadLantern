import type { DecisionSource, Priority, TaskStatus } from "../types";

export function humanize(value: string | null | undefined): string {
  if (!value) return "—";
  return value.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
}

export function PriorityBadge({ priority }: { priority: Priority | null }) {
  return <span className={`badge priority-${priority ?? "none"}`}>{humanize(priority) || "—"}</span>;
}

export function StatusBadge({ status }: { status: TaskStatus }) {
  return <span className={`badge status-${status}`}>{humanize(status)}</span>;
}

export function SourceBadge({ source }: { source: DecisionSource }) {
  const label = source === "llm" ? "LLM" : humanize(source);
  return <span className={`badge source-${source}`}>{label}</span>;
}

export function ConfidenceMeter({ value }: { value: number }) {
  const pct = Math.round(value * 100);
  const tone = value >= 0.8 ? "good" : value >= 0.6 ? "ok" : "low";
  return (
    <span className="confidence" title={`${pct}% confidence`}>
      <span className="confidence-track">
        <span className={`confidence-fill ${tone}`} style={{ width: `${pct}%` }} />
      </span>
      <span className="confidence-label">{pct}%</span>
    </span>
  );
}
