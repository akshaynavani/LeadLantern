import { render, screen } from "@testing-library/react";
import { ConfidenceMeter, humanize, PriorityBadge, SourceBadge } from "../components/Badges";

describe("badges", () => {
  it("humanizes snake_case values", () => {
    expect(humanize("technical_support")).toBe("Technical Support");
    expect(humanize(null)).toBe("—");
  });

  it("renders priority and source labels", () => {
    render(
      <>
        <PriorityBadge priority="urgent" />
        <SourceBadge source="llm" />
      </>,
    );
    expect(screen.getByText("Urgent")).toHaveClass("priority-urgent");
    expect(screen.getByText("LLM")).toBeInTheDocument();
  });

  it("shows confidence as a percentage with a tone", () => {
    const { container } = render(<ConfidenceMeter value={0.42} />);
    expect(screen.getByText("42%")).toBeInTheDocument();
    expect(container.querySelector(".confidence-fill")).toHaveClass("low");
  });
});
