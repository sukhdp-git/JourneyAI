# QA fix report — browser QA round (October 2026)

The QA report, ZIP and SQL attachments did not reach the development session; the work below is based on the
bug descriptions in the request and on the repository (same code as the last delivered ZIP). All verification used
a local copy of the app with synthetic users and accounts. No real payments, no production data, no deployment.
**No database schema change** — no SQL migration is needed for this round.

## Bug-by-bug status

| # | Issue | Status | Root cause found | Fix |
|---|---|---|---|---|
| 2 | AI Coach 502 / provider billing text shown | **Fixed (failure paths); success not verified with a real key** | Provider error text (incl. *credit balance too low*) was passed to members; the app itself returned HTTP 502 for provider errors; `fetch` network errors left the chat on "Analysing…" forever; the question was cleared on failure; a slow AI call held the PHP session lock | Member-safe messages + reference ID; diagnostics only in logs and the Control Panel; 503 + `Retry-After`; no stuck state; question kept + **Try again**; 90 s timeout; session lock released; Test connection uses the production request shape |
| 3 | Trade-entry preview shows numbers for invalid input | **Fixed** | Preview computed from raw numbers without the save rules | Same rules as `Ledger::validate` before any derived value; field-level errors; optional fields unchanged |
| 4 | Calculator keeps stale explanation | **Fixed** | `reset()` skipped the explanation line and colours; emptying entry/stop returned early; no stale-response guard | Full reset on invalid/failed/empty; newest request wins; `aria-busy` loading state; formulas untouched |
| 5 | Forgot password accepts blank email | **Fixed** | Auth pages load `auth.js`, which had no validation; server always showed the success message | Client + server validation; nothing sent for invalid input; neutral message for every valid address |
| 6 | Empty login says "Incorrect email or password" | **Fixed** | Same as 5; server went straight to the credential check | Required-field errors, focus + ARIA; generic message kept for non-empty wrong credentials |
| 7 | Unfinished policy pages | **Partially fixed — launch blocker remains** | Starter CMS content with owner placeholders | Draft banner, *[to be completed]* markers, `noindex`, dashboard launch blockers. Final text needs owner inputs + legal review: `docs/LAUNCH-POLICY-INPUTS.md` |
| 8 | Demo testimonials on homepage | **Fixed** | Public query ignored the `is_demo` flag | Demo rows never shown publicly; still manageable; section disappears when empty |
| 9 | Strategy vs setup inconsistent | **Fixed** | Undocumented "strategy, else setup" fallback used in some views but not others | One rule: strategy = assigned strategy only; "No strategy assigned" group + filter; stored data unchanged |
| 10 | Home Hub "Today" lists historical trades | **Fixed** | Table is intentionally "latest 6, any date" but sat under "Today" unlabelled | Labelled **Recent trades · all dates**; Today metrics unchanged (timezone boundary verified) |
| 11 | "How it works" starts at 02 | **Fixed defensively; cause not reproduced** | Not reproducible with the shipped data (01–04 render). Likely production data (step 01 unpublished/deleted) | Numeric step numbers now follow published order, so a gap can't appear. Check *Content → Process steps* on production |
| 12 | Intermittent 502s | **Partially fixed; remainder needs the host** | Confirmed app defect: a long AI request blocked all of that member's other requests (dashboard waited 35 s). Other 502s not reproducible locally (pages render in 10–90 ms) | Session lock released before slow outbound calls; request IDs, fatal-error and slow-request logging. Google OAuth code is correct (PKCE/state/nonce) and was not changed |

## Owner actions

1. **AI credits / key**: add Anthropic credits (console.anthropic.com → Plans & Billing) or set a working key,
   then **Control Panel → Integrations → AI Coach → Test connection**. A real successful AI answer was **not**
   tested (no funded key in the test environment).
2. **Policy pages**: provide the inputs in `docs/LAUNCH-POLICY-INPUTS.md` and have the text legally reviewed.
3. **Process step 01**: confirm it is published in *Content → Process steps* on production.
4. **502s**: if a 502/504 has no matching `rid` in `storage/logs/app-YYYY-MM.log`, ask the host for its error log
   and gateway timeout at that time.
5. **Contact email**: the `contact_email` setting is still `owner@example.com`.
