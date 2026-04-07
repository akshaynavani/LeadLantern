import { useState, type FormEvent } from "react";
import { Link, useParams } from "react-router-dom";
import { api } from "../api/client";
import { useAsync } from "../api/useAsync";
import { ConfidenceMeter, humanize, PriorityBadge, SourceBadge, StatusBadge } from "../components/Badges";
import { Can } from "../components/Guards";
import { CATEGORIES, PRIORITIES, STATUSES, type Task, type TaskStatus } from "../types";

export function TaskDetailPage() {
  const id = Number(useParams().id);
  const { data: task, error, loading, reload } = useAsync(() => api.task(id), [id]);
  const [busy, setBusy] = useState(false);

  if (loading && !task) return <p className="muted">Loading…</p>;
  if (error) return <div className="error">{error.message}</div>;
  if (!task) return null;

  async function act(fn: () => Promise<unknown>) {
    setBusy(true);
    try {
      await fn();
      await reload();
    } finally {
      setBusy(false);
    }
  }

  const latest = task.latest_decision;

  return (
    <section>
      <header className="page-header">
        <div>
          <Link to="/tasks" className="muted small">← Tasks</Link>
          <h1>{task.title}</h1>
          <div className="row">
            <StatusBadge status={task.status} />
            <PriorityBadge priority={task.priority} />
            <span className="muted">{humanize(task.category)} · {task.team?.name ?? "Unrouted"}</span>
          </div>
        </div>
        <div className="row">
          <Can permission="tasks.update">
            <select
              aria-label="Status"
              value={task.status}
              disabled={busy}
              onChange={(e) => act(() => api.updateTask(task.id, { status: e.target.value as TaskStatus }))}
            >
              {STATUSES.map((s) => (
                <option key={s} value={s}>{humanize(s)}</option>
              ))}
            </select>
          </Can>
          <Can permission="tasks.route">
            <button disabled={busy} onClick={() => act(() => api.rerouteTask(task.id))}>
              {busy ? "Working…" : "Re-run LLM triage"}
            </button>
          </Can>
        </div>
      </header>

      <div className="grid two">
        <div className="card">
          <h3>Message</h3>
          {task.contact && (
            <p className="muted">
              From <Link to={`/contacts/${task.contact.id}`}>{task.contact.full_name}</Link>
              {task.contact.company ? ` (${task.contact.company})` : ""} via {task.channel}
            </p>
          )}
          <p className="pre">{task.description}</p>
        </div>

        <div className="card">
          <h3>Current routing</h3>
          {latest ? (
            <>
              <dl className="kv">
                <dt>Source</dt><dd><SourceBadge source={latest.source} /></dd>
                <dt>Confidence</dt><dd><ConfidenceMeter value={latest.confidence} /></dd>
                <dt>Sentiment</dt><dd>{humanize(latest.sentiment)}</dd>
                <dt>Model</dt><dd>{latest.model ?? "—"}{latest.latency_ms != null ? ` · ${latest.latency_ms} ms` : ""}</dd>
              </dl>
              {latest.rationale && <p>{latest.rationale}</p>}
              {latest.suggested_actions.length > 0 && (
                <>
                  <h4>Suggested next steps</h4>
                  <ul>{latest.suggested_actions.map((a) => <li key={a}>{a}</li>)}</ul>
                </>
              )}
            </>
          ) : (
            <p className="muted">Not routed yet.</p>
          )}
          <Can permission="tasks.route">
            <OverrideForm task={task} onDone={reload} />
          </Can>
        </div>
      </div>

      <div className="card">
        <h3>Routing history</h3>
        <table className="table">
          <thead>
            <tr><th>When</th><th>Source</th><th>Category</th><th>Priority</th><th>Team</th><th>Confidence</th><th>By</th></tr>
          </thead>
          <tbody>
            {(task.routing_decisions ?? []).map((d) => (
              <tr key={d.id}>
                <td>{new Date(d.created_at).toLocaleString()}</td>
                <td><SourceBadge source={d.source} /></td>
                <td>{humanize(d.category)}</td>
                <td><PriorityBadge priority={d.priority} /></td>
                <td>{d.team?.name ?? "—"}</td>
                <td><ConfidenceMeter value={d.confidence} /></td>
                <td>{d.decided_by?.name ?? d.model ?? "—"}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  );
}

function OverrideForm({ task, onDone }: { task: Task; onDone: () => Promise<unknown> }) {
  const { data: teams } = useAsync(() => api.teams(), []);
  const [open, setOpen] = useState(false);
  const [category, setCategory] = useState<string>(task.category ?? "other");
  const [priority, setPriority] = useState<string>(task.priority ?? "medium");
  const [teamId, setTeamId] = useState<string>("");
  const [note, setNote] = useState("");
  const [busy, setBusy] = useState(false);

  if (!open) {
    return <button className="secondary" onClick={() => setOpen(true)}>Override routing</button>;
  }

  async function submit(e: FormEvent) {
    e.preventDefault();
    setBusy(true);
    try {
      await api.overrideTask(task.id, { category, priority, team_id: teamId ? Number(teamId) : null, note: note || undefined });
      setOpen(false);
      await onDone();
    } finally {
      setBusy(false);
    }
  }

  return (
    <form className="form inline-form" onSubmit={submit}>
      <select value={category} onChange={(e) => setCategory(e.target.value)} aria-label="Category">
        {CATEGORIES.map((c) => <option key={c} value={c}>{humanize(c)}</option>)}
      </select>
      <select value={priority} onChange={(e) => setPriority(e.target.value)} aria-label="Priority">
        {PRIORITIES.map((p) => <option key={p} value={p}>{humanize(p)}</option>)}
      </select>
      <select value={teamId} onChange={(e) => setTeamId(e.target.value)} aria-label="Team">
        <option value="">Team from routing rule</option>
        {teams?.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
      </select>
      <input placeholder="Reason (optional)" value={note} onChange={(e) => setNote(e.target.value)} />
      <div className="row">
        <button type="submit" disabled={busy}>Apply</button>
        <button type="button" className="secondary" onClick={() => setOpen(false)}>Cancel</button>
      </div>
    </form>
  );
}
