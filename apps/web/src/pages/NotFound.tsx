import { Link } from 'react-router';

export default function NotFound({ inApp }: { inApp?: boolean }) {
  return (
    <main id="main" className="flex min-h-[60vh] flex-col items-center justify-center gap-3 p-6 text-center">
      <p className="num text-4xl font-bold text-accent">404</p>
      <p className="text-sm text-muted">This page does not exist.</p>
      <Link to={inApp ? '/app' : '/'} className="text-sm text-accent underline">
        {inApp ? 'Back to Home Hub' : 'Back to journzey.ai'}
      </Link>
    </main>
  );
}
