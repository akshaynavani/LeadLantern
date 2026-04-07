import { useState, type FormEvent } from "react";
import { Link, useSearchParams } from "react-router-dom";
import { api } from "../api/client";
import { useAsync } from "../api/useAsync";
import { ConfidenceMeter, humanize, PriorityBadge, StatusBadge } from "../components/Badges";
import { Can } from "../components/Guards";
import { CATEGORIES, PRIORITIES, STATUSES } from "../types";

export function TasksPage() {
  const [params, setParams] = useSearchParams();
  const filters = {
    status: params.get("status") ?? undefined,
    priority: params.get("priority") ?? undefined,
    category: params.get("category") ?? undefined,
    q: params.get("q") ?? undefined,
    page: params.get("page") ?? undefined,
  };
  const key = params.toString();
  const { data, error, loading, reload } = useAsync(() => api.tasks(filters), [key]);
  const [showNew, setShowNew] = useState(false);

  function setFilter(name: string, value: string) {
    const next = new URLSearchParams(params);
    if (value) next.set(name, value);
    else next.delete(name);
    next.delete("page");
    setParams(next);
  }

  return (
    <section>
      <header className="page-header">
        <h1>Tasks</h1>
        <Can permission="tasks.create">
          <button onClick={() => setShowNew((s) => !s)}>{showNew ? "Cancel" : "New task"}</button>
        </Can>
      </header>

      {showNew && (
        <NewTaskForm
          onCreated={() => {
            setShowNew(false);
            void reload();
          }}
        />
      )}

      <div className="filters">
        <input placeholder="Search…" defaultValue={filters.q} onKeyDown={(e) => e.key === "Enter" && setFilter("q", e.currentTarget.value)} />
        <select value={filters.status ?? ""} onChange={(e) => setFilter("status", e.target.value)} aria-label="Status">
          <option value="">All statuses</option>
          {STATUSES.map((s) => (
            <option key={s} value={s}>{humanize(s)}</option>
          ))}
        </select>
        <select value={filters.priority ?? ""} onChange={(e) => setFilter("priority", e.target.value)} aria-label="Priority">
          <option value="">All priorities</option>
          {PRIORITIES.map((p) => (
            <option key={p} value={p}>{humanize(p)}</option>
          ))}
        </select>
        <select value={filters.category ?? ""} onChange={(e) => setFilter("category", e.target.value)} aria-label="Category">
          <option value="">All categories</option>
          {CATEGORIES.map((c) => (
            <option key={c} value={c}>{humanize(c)}</option>
          ))}
        </select>
      </div>

      {error && <div className="error">{error.message}</div>}

      <table className="table">
        <thead>
          <tr>
            <th>Task</th>
            <th>Contact</th>
            <th>Category</th>
            <th>Priority</th>
            <th>Team</th>
            <th>Confidence</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          {loading && !data && (
            <tr><td colSpan={7} className="muted">Loading…</td></tr>
          )}
          {data?.data.map((t) => (
            <tr key={t.id}>
              <td>
                <Link to={`/tasks/${t.id}`}>{t.title}</Link>
                <div className="muted small">{t.channel}</div>
              </td>
              <td>{t.contact ? <Link to={`/contacts/${t.contact.id}`}>{t.contact.full_name}</Link> : "—"}</td>
              <td>{humanize(t.category)}</td>
              <td><PriorityBadge priority={t.priority} /></td>
              <td>{t.team?.name ?? "—"}</td>
              <td>{t.latest_decision ? <ConfidenceMeter value={t.latest_decision.confidence} /> : "—"}</td>
              <td><StatusBadge status={t.status} /></td>
            </tr>
          ))}
          {data && data.data.length === 0 && (
            <tr><td colSpan={7} className="muted">No tasks match these filters.</td></tr>
          )}
        </tbody>
      </table>

      {data && data.meta.last_page > 1 && (
        <div className="pager">
          <button disabled={data.meta.current_page <= 1} onClick={() => setFilter("page", String(data.meta.current_page - 1))}>Prev</button>
          <span>Page {data.meta.current_page} of {data.meta.last_page}</span>
          <button disabled={data.meta.current_page >= data.meta.last_page} onClick={() => setFilter("page", String(data.meta.current_page + 1))}>Next</button>
        </div>
      )}
    </section>
  );
}

function NewTaskForm({ onCreated }: { onCreated: () => void }) {
  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [channel, setChannel] = useState("email");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function submit(e: FormEvent) {
    e.preventDefault();
    setBusy(true);
    setError(null);
    try {
      await api.createTask({ title, description, channel });
      onCreated();
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setBusy(false);
    }
  }

  return (
    <form className="card form" onSubmit={submit}>
      <label>
        Title
        <input value={title} onChange={(e) => setTitle(e.target.value)} required />
      </label>
      <label>
        Channel
        <select value={channel} onChange={(e) => setChannel(e.target.value)}>
          {["email", "call", "chat", "form", "meeting", "note"].map((c) => (
            <option key={c} value={c}>{humanize(c)}</option>
          ))}
        </select>
      </label>
      <label>
        Message
        <textarea rows={4} value={description} onChange={(e) => setDescription(e.target.value)} required />
      </label>
      {error && <div className="error">{error}</div>}
      <button type="submit" disabled={busy}>{busy ? "Creating & triaging…" : "Create and triage"}</button>
    </form>
  );
}
