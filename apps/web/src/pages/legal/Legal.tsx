import { Link } from 'react-router';
import { Logo } from '../../components/Logo';

type Page = 'terms' | 'privacy' | 'security' | 'disclaimer';

const CONTENT: Record<Page, { title: string; sections: Array<{ h: string; p: string[] }> }> = {
  terms: {
    title: 'Terms of Service',
    sections: [
      { h: 'The service', p: ['journzey.ai provides trading-journal, analytics and coaching software. It does not execute trades, hold funds or provide brokerage, investment-advisory or portfolio-management services.'] },
      { h: 'Your account', p: ['You sign in with Google. You are responsible for activity under your account and for keeping your Google account secure.', 'You must provide accurate information and use the service lawfully.'] },
      { h: 'Your data', p: ['You own the trading data and journal content you submit. You grant us a limited licence to store and process it solely to operate the service for you. You can export or delete it at any time from Account Settings.'] },
      { h: 'Integrations', p: ['Broker, market-data and AI integrations depend on third parties and may be unavailable. Imported data is only as accurate as its source.'] },
      { h: 'No warranty', p: ['The service is provided “as is”. Analytics, simulations and AI outputs may contain errors and are not guarantees of any outcome. To the maximum extent permitted by law, we are not liable for trading losses or decisions made using the service.'] },
      { h: 'Changes', p: ['We may update these terms; material changes will be communicated in the application.'] },
    ],
  },
  privacy: {
    title: 'Privacy Policy',
    sections: [
      { h: 'What we collect', p: ['From Google sign-in: your name, email, profile image and a stable Google account identifier. We never receive your Google password.', 'Data you enter: trades, journal entries, strategies, settings, screenshots and broker connection metadata.', 'Security logs: sign-in/out and security-sensitive actions with IP address and user agent.'] },
      { h: 'How we use it', p: ['Only to provide journzey.ai to you: storing your journal, computing analytics, and — if you use the AI Coach — sending aggregated statistics (not raw records, not your email) to the configured AI provider.'] },
      { h: 'Sharing', p: ['We do not sell personal data. Processors (hosting, database, object storage, AI and market-data providers) act on our instructions. Share cards you export never include account identifiers.'] },
      { h: 'Retention and deletion', p: ['Data is kept while your account exists. “Delete Account” permanently removes your profile, trades, journals, strategies, settings, broker connections (including encrypted credentials), screenshots and AI history. Security audit entries are retained without a link to your identity.'] },
      { h: 'Your rights', p: ['Export your data as JSON or CSV at any time, correct your profile, or delete your account from Account Settings.'] },
      { h: 'Cookies', p: ['We use one strictly-necessary, HttpOnly session cookie. Your theme preference may be stored in your browser for faster loading.'] },
    ],
  },
  security: {
    title: 'Security',
    sections: [
      { h: 'Authentication', p: ['Google OAuth 2.0 / OpenID Connect with PKCE, state and nonce validation and server-side ID-token signature verification.', 'Server-managed sessions in PostgreSQL with HttpOnly, Secure (production) and SameSite=Lax cookies; the session id is rotated on login.'] },
      { h: 'Data isolation', p: ['Every request resolves the user from the server-side session. Every query on user-owned data is filtered by that user; client-supplied user ids are never trusted. Automated tests verify one user cannot read or modify another user’s data.'] },
      { h: 'Secrets', p: ['Broker credentials and webhook signing secrets are encrypted at rest with AES-256-GCM and never returned to the browser after creation. API keys for AI and market data stay on the server.'] },
      { h: 'Application security', p: ['CSRF tokens and origin checks, strict CORS, Helmet security headers and Content-Security-Policy, input validation on every endpoint, parameterised queries, rate limiting, file-type verification by content, CSV formula-injection protection and audit logging.'] },
      { h: 'Reporting', p: ['If you believe you have found a vulnerability, contact the operator of this deployment. Please do not access data that is not yours.'] },
    ],
  },
  disclaimer: {
    title: 'Risk Disclaimer',
    sections: [
      { h: 'Software, not advice', p: ['journzey.ai is trading-journal and analytics software. It does not provide financial, investment, tax or legal advice and is not a substitute for advice from a qualified professional.'] },
      { h: 'No guarantees', p: ['journzey.ai does not guarantee profits or any investment outcome. Trading leveraged products such as CFDs, forex, futures and crypto carries a high risk of losing money rapidly.'] },
      { h: 'Historical data', p: ['All analytics are computed from historical data you provide or import. Past performance is not indicative of future results.'] },
      { h: 'Hypothetical and statistical results', p: ['The Discipline Leak Mirror, Post-Trade Runner Auditor and Monte Carlo risk-of-ruin features produce hypothetical or statistical estimates based on stated assumptions. They are not predictions and hypothetical results have inherent limitations.'] },
      { h: 'Terminal lock', p: ['The Tilt Circuit Breaker locks the journzey.ai terminal only. journzey.ai cannot block orders at your broker.'] },
    ],
  },
};

export function LegalFooter() {
  return (
    <footer className="border-t border-line">
      <nav aria-label="Legal" className="mx-auto flex max-w-6xl flex-wrap items-center gap-4 px-4 py-6 text-2xs text-muted">
        <span>© {new Date().getFullYear()} journzey.ai</span>
        <Link to="/terms">Terms</Link>
        <Link to="/privacy">Privacy</Link>
        <Link to="/security">Security</Link>
        <Link to="/disclaimer">Disclaimer</Link>
      </nav>
    </footer>
  );
}

export default function Legal({ page }: { page: Page }) {
  const c = CONTENT[page];
  return (
    <div className="min-h-full">
      <header className="mx-auto max-w-3xl px-4 py-4">
        <Link to="/" aria-label="journzey.ai home">
          <Logo />
        </Link>
      </header>
      <main id="main" className="mx-auto max-w-3xl px-4 pb-16">
        <h1 className="font-display text-3xl font-bold">{c.title}</h1>
        <p className="mt-2 text-2xs text-muted">Template policy for this deployment — operators should review it with counsel before public launch.</p>
        {c.sections.map((s) => (
          <section key={s.h} className="mt-8">
            <h2 className="text-base font-semibold">{s.h}</h2>
            {s.p.map((p) => (
              <p key={p} className="mt-2 text-sm leading-relaxed text-muted">
                {p}
              </p>
            ))}
          </section>
        ))}
      </main>
      <LegalFooter />
    </div>
  );
}
