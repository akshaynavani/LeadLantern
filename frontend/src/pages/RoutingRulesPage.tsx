import { api } from "../api/client";
import { useAsync } from "../api/useAsync";
import { humanize } from "../components/Badges";
import { PRIORITIES, type Priority, type RoutingRule } from "../types";

export function RoutingRulesPage() {
  const rules = useAsync(() => api.routingRules(), []);
  const teams = useAsync(() => api.teams(), []);

  async function save(rule: RoutingRule, patch: Partial<RoutingRule>) {
    const updated = await api.updateRoutingRule(rule.id, patch);
    rules.setData((prev) => prev?.map((r) => (r.id === updated.id ? updated : r)) ?? null);
  }

  return (
    <section>
      <header className="page-header">
        <h1>Routing rules</h1>
      </header>
      <p className="muted">
        The LLM picks a category; these rules map it to a team. The default priority acts as a floor — the LLM can raise
        it but never lower it. Low-confidence results always go to the triage review queue.
      </p>
      {rules.error && <div className="error">{rules.error.message}</div>}
      <table className="table">
        <thead>
          <tr><th>Category</th><th>Team</th><th>Minimum priority</th><th>Active</th></tr>
        </thead>
        <tbody>
          {rules.data?.map((rule) => (
            <tr key={rule.id}>
              <td>{humanize(rule.category)}</td>
              <td>
                <select value={rule.team_id} onChange={(e) => save(rule, { team_id: Number(e.target.value) })} aria-label="Team">
                  {teams.data?.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
                </select>
              </td>
              <td>
                <select
                  value={rule.default_priority ?? ""}
                  onChange={(e) => save(rule, { default_priority: (e.target.value || null) as Priority | null })}
                  aria-label="Minimum priority"
                >
                  <option value="">None</option>
                  {PRIORITIES.map((p) => <option key={p} value={p}>{humanize(p)}</option>)}
                </select>
              </td>
              <td>
                <input type="checkbox" checked={rule.is_active} onChange={(e) => save(rule, { is_active: e.target.checked })} aria-label="Active" />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </section>
  );
}
