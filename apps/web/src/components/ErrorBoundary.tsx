import { Component, type ErrorInfo, type ReactNode } from 'react';
import { AlertTriangle } from 'lucide-react';

/** Contains render failures to the affected page so the rest of the terminal keeps working. */
export class ErrorBoundary extends Component<{ children: ReactNode; resetKey?: string }, { error: Error | null }> {
  override state = { error: null as Error | null };
  static getDerivedStateFromError(error: Error) {
    return { error };
  }
  override componentDidCatch(error: Error, info: ErrorInfo) {
    console.error('UI error', error, info.componentStack);
  }
  override componentDidUpdate(prev: { resetKey?: string }) {
    if (prev.resetKey !== this.props.resetKey && this.state.error) this.setState({ error: null });
  }
  override render() {
    if (!this.state.error) return this.props.children;
    return (
      <div role="alert" className="panel mx-auto mt-10 max-w-lg p-6 text-center">
        <AlertTriangle className="mx-auto h-6 w-6 text-warn" aria-hidden />
        <h2 className="mt-2 font-semibold">This view failed to load</h2>
        <p className="mt-1 text-sm text-muted">Your data is safe. Try again, or navigate to another section.</p>
        <button className="mt-4 rounded-md border border-line px-3 py-2 text-sm" onClick={() => this.setState({ error: null })}>
          Try again
        </button>
      </div>
    );
  }
}
