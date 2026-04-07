import { useState } from "react";
import { Link } from "react-router-dom";
import { api } from "../api/client";
import { useAsync } from "../api/useAsync";
import { humanize } from "../components/Badges";

export function ContactsPage() {
  const [q, setQ] = useState("");
  const [query, setQuery] = useState("");
  const { data, error, loading } = useAsync(() => api.contacts(query), [query]);

  return (
    <section>
      <header className="page-header">
        <h1>Contacts</h1>
      </header>
      <div className="filters">
        <input
          placeholder="Search name, email, company…"
          value={q}
          onChange={(e) => setQ(e.target.value)}
          onKeyDown={(e) => e.key === "Enter" && setQuery(q)}
        />
      </div>
      {error && <div className="error">{error.message}</div>}
      <table className="table">
        <thead>
          <tr><th>Name</th><th>Company</th><th>Email</th><th>Stage</th><th>Interactions</th></tr>
        </thead>
        <tbody>
          {loading && !data && <tr><td colSpan={5} className="muted">Loading…</td></tr>}
          {data?.data.map((c) => (
            <tr key={c.id}>
              <td><Link to={`/contacts/${c.id}`}>{c.full_name}</Link></td>
              <td>{c.company ?? "—"}</td>
              <td>{c.email ?? "—"}</td>
              <td>{humanize(c.lifecycle_stage)}</td>
              <td>{c.interactions_count ?? 0}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </section>
  );
}
