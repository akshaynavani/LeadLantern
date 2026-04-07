import { NavLink, Outlet } from "react-router-dom";
import { useAuth } from "../auth/AuthContext";

export function Layout() {
  const { user, logout, can } = useAuth();

  return (
    <div className="shell">
      <aside className="sidebar">
        <div className="brand">LeadLantern</div>
        <nav>
          {can("reports.view") && <NavLink to="/">Dashboard</NavLink>}
          <NavLink to="/tasks">Tasks</NavLink>
          <NavLink to="/tasks?status=needs_review">Review queue</NavLink>
          <NavLink to="/contacts">Contacts</NavLink>
          {can("routing_rules.manage") && <NavLink to="/routing-rules">Routing rules</NavLink>}
        </nav>
        <div className="sidebar-footer">
          <div>{user?.name}</div>
          <div className="muted">
            {user?.role}
            {user?.team ? ` · ${user.team.name}` : ""}
          </div>
          <button className="link" onClick={() => void logout()}>
            Sign out
          </button>
        </div>
      </aside>
      <main className="content">
        <Outlet />
      </main>
    </div>
  );
}
