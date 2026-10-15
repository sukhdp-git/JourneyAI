# Launch blocker: public policy pages

The Privacy Policy, Terms & Conditions, Security and Risk Disclaimer pages are CMS pages
(**Control Panel → Pages**). They still contain the starter template's placeholders. Until every
placeholder is replaced, the site shows each page with a **"Draft — not yet final"** banner, marks
the gaps as *[to be completed]*, keeps the page out of search engines (`noindex`) and lists it as a
**LAUNCH BLOCKER** on the Control Panel dashboard. The page content in the database has not been
changed — only you (and a qualified legal reviewer) should finalise it.

Nothing below has been invented. Items marked **Owner input** must come from you; items marked
**Verified from the code** describe what the software actually does today, so the policy can say so.

## 1. Owner inputs required (exact gaps)

| Page | Placeholder in the current text | What is needed |
|---|---|---|
| Privacy Policy | `[Replace with your registered company name and address.]` | Legal entity name and registered address of the operator. |
| Privacy Policy | `[Describe the tools you enable.]` | Which analytics/marketing tools you will enable (Google Analytics, Tag Manager, Meta Pixel are all **off** in the current settings). |
| Privacy Policy | `[State your retention period.]` | How long account data, trade journals, AI conversations, logs and enquiries are kept, and what happens after account deletion. |
| Privacy Policy | `[Add your privacy contact address.]` | Email/postal address for privacy requests. (The site's `contact_email` setting is currently a placeholder value `owner@example.com`; `support_email` is empty.) |
| Privacy Policy | Template notice | Legal review for your jurisdiction (and GDPR/DPDP Act or other regimes that apply to your users). |
| Terms & Conditions | `[Have this clause reviewed for your jurisdiction.]` | Reviewed limitation-of-liability clause. |
| Terms & Conditions | `[Replace with your governing law and jurisdiction.]` | Governing law and courts. |
| Terms & Conditions | Template notice | Legal review; subscription, renewal, cancellation and refund terms that match your plans and payment settings. |
| Security | `[Replace] Add the email address for security reports.` | A monitored security-reporting address (e.g. security@your-domain). |
| Security | Template notice | Professional review of the security statements. |
| Risk Disclaimer | Template notice | Professional review (no placeholders otherwise). |

## 2. Verified from the code — facts the Privacy Policy currently omits

The current Privacy Policy only describes website enquiries. The product also processes the
following (verified in this codebase), which a reviewer should cover:

- **Account data**: name, email, timezone, language, password stored as a one-way hash, or Google
  sign-in (OpenID Connect) identifiers.
- **Trading journal data** entered by the member: trades, prices, lot sizes, notes, emotions,
  strategies, journal entries, chart screenshots (uploaded files or links), capital transactions.
- **AI Coach** (only when the operator configures a provider): aggregated statistics for the
  signed-in member plus their own short journal notes are sent to the configured AI provider
  (Anthropic or Google Gemini). "Read chart" sends the selected screenshot image to the provider.
- **Payments**: Razorpay and/or Stripe hosted checkout; card/UPI details are entered on the
  provider's page and do not reach this server. Payment records (amount, status, provider IDs) are stored.
- **Emails** via the SMTP provider you configure (welcome, password reset, receipts).
- **Security logs**: sign-in attempts with IP address and browser user agent; admin activity logs;
  rate-limit records.
- **Cookies**: a strictly necessary session cookie (HttpOnly; Secure on HTTPS); an affiliate referral
  cookie (60 days) when a visitor arrives through a referral link.
- **Browser storage** (on the member's device only): unsent drafts, course progress, the course
  trading-plan builder and UI preferences.
- **Third-party widgets in the terminal**: TradingView widgets load scripts from TradingView.
- **Public sharing the member chooses**: flex cards / verify pages and public P&L links show the
  figures the member selected.
- **Member controls**: data export (Settings → Export) and account deletion (Settings → Delete account).

## 3. Technical status

- Done in code: draft banner, placeholder markers, `noindex` for unfinished pages, dashboard launch-blocker list.
- Not done (requires you / legal counsel): the final legal text. Keep these pages marked as launch blockers until reviewed.
