import { useState } from "react";
import { api } from "../api/client";
import { useAsync } from "../api/useAsync";
import { BarList } from "../components/BarList";
import { StatCard } from "../components/StatCard";

export function DashboardPage() {
  const [days, setDays] = useState(30);
  const { data, error, loading } = useAsync(() => api.stats(days), [days]);

  return (
    <section>
      <header className="page-header">
        <h1>Dashboard</h1>
        <select value={days} onChange={(e) => setDays(Number(e.target.value))} aria-label="Time window">
          <option value={7}>Last 7 days</option>
          <option value={30}>Last 30 days</option>
          <option value={90}>Last 90 days</option>
        </select>
      </header>

      {error && <div className="error">{error.message}</div>}
      {loading && !data && <p className="muted">Loading…</p>}

      {data && (
        <>
          <div className="grid stats">
            <StatCard label="Tasks" value={data.totals.tasks} />
            <StatCard label="Open" value={data.totals.open} />
            <StatCard label="Needs review" value={data.totals.needs_review} hint="Low confidence or triage unavailable" />
            <StatCard label="Avg LLM confidence" value={`${Math.round(data.routing.avg_confidence * 100)}%`} />
            <StatCard label="Avg triage latency" value={`${data.routing.avg_latency_ms} ms`} />
            <StatCard
              label="Override rate"
              value={`${Math.round(data.routing.override_rate * 100)}%`}
              hint="LLM-routed tasks later corrected by a human"
            />
          </div>
          <div className="grid two">
            <BarList title="By category" data={data.by_category} />
            <BarList title="By team" data={data.by_team} />
            <BarList title="By priority" data={data.by_priority} />
            <BarList title="Routing source" data={data.routing.by_source} />
          </div>
        </>
      )}
    </section>
  );
}
