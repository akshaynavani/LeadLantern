import { humanize } from "./Badges";

/** Horizontal bar list for small categorical breakdowns. */
export function BarList({ title, data }: { title: string; data: Record<string, number> }) {
  const rows = Object.entries(data).sort((a, b) => b[1] - a[1]);
  const max = Math.max(1, ...rows.map(([, v]) => v));

  return (
    <div className="card">
      <h3>{title}</h3>
      {rows.length === 0 && <p className="muted">No data yet.</p>}
      <ul className="barlist">
        {rows.map(([key, value]) => (
          <li key={key}>
            <span className="barlist-label">{humanize(key)}</span>
            <span className="barlist-track">
              <span className="barlist-fill" style={{ width: `${(value / max) * 100}%` }} />
            </span>
            <span className="barlist-value">{value}</span>
          </li>
        ))}
      </ul>
    </div>
  );
}
