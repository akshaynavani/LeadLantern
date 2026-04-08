import { render, screen } from "@testing-library/react";
import { BarList } from "../components/BarList";

describe("BarList", () => {
  it("sorts rows by value descending", () => {
    render(<BarList title="By category" data={{ billing: 2, sales_lead: 5 }} />);
    const labels = screen.getAllByText(/Billing|Sales Lead/).map((el) => el.textContent);
    expect(labels).toEqual(["Sales Lead", "Billing"]);
  });

  it("shows an empty state", () => {
    render(<BarList title="Empty" data={{}} />);
    expect(screen.getByText("No data yet.")).toBeInTheDocument();
  });
});
