import type { GroupStat } from '@journzey/shared';
import { fmtNum, fmtPct, humanize } from '../lib/format';
import { Pnl } from './ui';

export function GroupTable({ groups, currency, caption, keyLabel, labelFor }: { groups: GroupStat[]; currency: string; caption: string; keyLabel: string; labelFor?: (k: string) => string }) {
  if (groups.length === 0) return <p className="py-4 text-center text-sm text-muted">No closed trades in this period.</p>;
  return (
    <div className="scrollbar-thin overflow-x-auto">
      <table className="w-full text-xs">
        <caption className="sr-only">{caption}</caption>
        <thead className="text-left text-muted">
          <tr className="border-b border-line">
            <th scope="col" className="py-1.5 pr-2 font-semibold">
              {keyLabel}
            </th>
            <th scope="col" className="px-2 py-1.5 text-right font-semibold">
              Trades
            </th>
            <th scope="col" className="px-2 py-1.5 text-right font-semibold">
              Win %
            </th>
            <th scope="col" className="px-2 py-1.5 text-right font-semibold">
              PF
            </th>
            <th scope="col" className="px-2 py-1.5 text-right font-semibold">
              Avg R
            </th>
            <th scope="col" className="px-2 py-1.5 text-right font-semibold">
              Expectancy
            </th>
            <th scope="col" className="py-1.5 pl-2 text-right font-semibold">
              Net P&L
            </th>
          </tr>
        </thead>
        <tbody className="divide-y divide-line">
          {groups.map((g) => (
            <tr key={g.key}>
              <th scope="row" className="py-1.5 pr-2 text-left font-medium">
                {labelFor ? labelFor(g.key) : g.label ?? humanize(g.key)}
              </th>
              <td className="num px-2 py-1.5 text-right">{g.summary.trades}</td>
              <td className="num px-2 py-1.5 text-right">{fmtPct(g.summary.winRate)}</td>
              <td className="num px-2 py-1.5 text-right">{g.summary.profitFactor === null ? '—' : fmtNum(g.summary.profitFactor)}</td>
              <td className="num px-2 py-1.5 text-right">{g.summary.avgR === null ? '—' : fmtNum(g.summary.avgR)}</td>
              <td className="px-2 py-1.5 text-right">
                <Pnl value={g.summary.expectancy} currency={currency} />
              </td>
              <td className="py-1.5 pl-2 text-right">
                <Pnl value={g.summary.netPnl} currency={currency} />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
