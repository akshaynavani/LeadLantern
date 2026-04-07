import { useState, type FormEvent } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import { api } from "../api/client";
import { useAsync } from "../api/useAsync";
import { humanize, PriorityBadge, StatusBadge } from "../components/Badges";
import { Can } from "../components/Guards";

export function ContactDetailPage() {
  const id = Number(useParams().id);
  const { data: contact, error, loading, reload } = useAsync(() => api.contact(id), [id]);

  if (loading && !contact) return <p className="muted">Loading…</p>;
  if (error) return <div className="error">{error.message}</div>;
  if (!contact) return null;

  return (
    <section>
      <header className="page-header">
        <div>
          <Link to="/contacts" className="muted small">← Contacts</Link>
          <h1>{contact.full_name}</h1>
          <div className="muted">
            {[contact.company, contact.email, contact.phone].filter(Boolean).join(" · ")} · {humanize(contact.lifecycle_stage)}
          </div>
        </div>
      </header>

      <div className="grid two">
        <div className="card">
          <h3>Interaction history</h3>
          <Can permission="contacts.manage">
            <LogInteractionForm contactId={contact.id} onLogged={reload} />
          </Can>
          <ol className="timeline">
            {(contact.interactions ?? []).map((i) => (
              <li key={i.id} className={`timeline-item ${i.direction}`}>
                <div className="timeline-meta">
                  <strong>{humanize(i.channel)}</strong> · {i.direction} · {new Date(i.occurred_at).toLocaleString()}
                  {i.user && <> · {i.user.name}</>}
                </div>
                {i.subject && <div className="timeline-subject">{i.subject}</div>}
                <p className="pre">{i.body}</p>
                {i.task_id && <Link to={`/tasks/${i.task_id}`} className="small">View routed task →</Link>}
              </li>
            ))}
            {contact.interactions?.length === 0 && <li className="muted">No interactions yet.</li>}
          </ol>
        </div>

        <div className="card">
          <h3>Tasks & routing results</h3>
          <ul className="plain">
            {(contact.tasks ?? []).map((t) => (
              <li key={t.id} className="task-row">
                <Link to={`/tasks/${t.id}`}>{t.title}</Link>
                <div className="row">
                  <StatusBadge status={t.status} />
                  <PriorityBadge priority={t.priority} />
                  <span className="muted small">{humanize(t.category)} → {t.team?.name ?? "unrouted"}</span>
                </div>
              </li>
            ))}
            {contact.tasks?.length === 0 && <li className="muted">No tasks.</li>}
          </ul>
        </div>
      </div>
    </section>
  );
}

function LogInteractionForm({ contactId, onLogged }: { contactId: number; onLogged: () => Promise<unknown> }) {
  const navigate = useNavigate();
  const [channel, setChannel] = useState("email");
  const [direction, setDirection] = useState("inbound");
  const [subject, setSubject] = useState("");
  const [body, setBody] = useState("");
  const [createTask, setCreateTask] = useState(true);
  const [busy, setBusy] = useState(false);

  async function submit(e: FormEvent) {
    e.preventDefault();
    setBusy(true);
    try {
      const res = await api.logInteraction(contactId, {
        channel,
        direction,
        subject: subject || undefined,
        body,
        create_task: direction === "inbound" && createTask,
      });
      setSubject("");
      setBody("");
      if (res.task_id) navigate(`/tasks/${res.task_id}`);
      else await onLogged();
    } finally {
      setBusy(false);
    }
  }

  return (
    <form className="form" onSubmit={submit}>
      <div className="row">
        <select value={channel} onChange={(e) => setChannel(e.target.value)} aria-label="Channel">
          {["email", "call", "chat", "form", "meeting", "note"].map((c) => <option key={c} value={c}>{humanize(c)}</option>)}
        </select>
        <select value={direction} onChange={(e) => setDirection(e.target.value)} aria-label="Direction">
          <option value="inbound">Inbound</option>
          <option value="outbound">Outbound</option>
        </select>
      </div>
      <input placeholder="Subject" value={subject} onChange={(e) => setSubject(e.target.value)} />
      <textarea rows={3} placeholder="What happened?" value={body} onChange={(e) => setBody(e.target.value)} required />
      {direction === "inbound" && (
        <label className="checkbox">
          <input type="checkbox" checked={createTask} onChange={(e) => setCreateTask(e.target.checked)} />
          Create a task and run LLM triage
        </label>
      )}
      <button type="submit" disabled={busy}>{busy ? "Saving…" : "Log interaction"}</button>
    </form>
  );
}
