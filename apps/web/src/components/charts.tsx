import { Area, AreaChart, Bar, BarChart, CartesianGrid, Cell, Line, LineChart, ReferenceLine, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import type { GroupStat } from '@journzey/shared';
import { fmtMoney, fmtPct, humanize } from '../lib/format';
import { useThemeColors } from '../lib/useThemeColors';

const tooltipStyle = (c: ReturnType<typeof useThemeColors>) => ({
  contentStyle: { background: c.panel, border: `1px solid ${c.line}`, borderRadius: 6, fontSize: 12, color: c.fg },
  labelStyle: { color: c.muted },
  itemStyle: { color: c.fg },
});

export function EquityChart({ data, currency, dataKey, label }: { data: Array<Record<string, string | number>>; currency: string; dataKey: string; label: string }) {
  const c = useThemeColors();
  return (
    <div className="h-64" role="img" aria-label={`${label} chart`}>
      <ResponsiveContainer>
        <AreaChart data={data} margin={{ top: 8, right: 8, bottom: 0, left: 0 }}>
          <defs>
            <linearGradient id={`g-${dataKey}`} x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stopColor={c.accent} stopOpacity={0.35} />
              <stop offset="100%" stopColor={c.accent} stopOpacity={0} />
            </linearGradient>
          </defs>
          <CartesianGrid stroke={c.line} strokeDasharray="3 3" vertical={false} />
          <XAxis dataKey="label" tick={{ fill: c.muted, fontSize: 10 }} tickLine={false} axisLine={false} minTickGap={24} />
          <YAxis tick={{ fill: c.muted, fontSize: 10 }} tickLine={false} axisLine={false} width={64} tickFormatter={(v: number) => fmtMoney(v, currency, { compact: true })} domain={['auto', 'auto']} />
          <Tooltip {...tooltipStyle(c)} formatter={(v: number) => [fmtMoney(v, currency), label]} />
          <Area type="monotone" dataKey={dataKey} stroke={c.accent} strokeWidth={2} fill={`url(#g-${dataKey})`} isAnimationActive={false} />
        </AreaChart>
      </ResponsiveContainer>
    </div>
  );
}

export function DrawdownChart({ data }: { data: Array<{ label: string; drawdownPct: number }> }) {
  const c = useThemeColors();
  return (
    <div className="h-48" role="img" aria-label="Drawdown chart">
      <ResponsiveContainer>
        <AreaChart data={data.map((d) => ({ ...d, dd: -d.drawdownPct * 100 }))} margin={{ top: 8, right: 8, bottom: 0, left: 0 }}>
          <CartesianGrid stroke={c.line} strokeDasharray="3 3" vertical={false} />
          <XAxis dataKey="label" tick={{ fill: c.muted, fontSize: 10 }} tickLine={false} axisLine={false} minTickGap={24} />
          <YAxis tick={{ fill: c.muted, fontSize: 10 }} tickLine={false} axisLine={false} width={48} tickFormatter={(v: number) => `${v.toFixed(1)}%`} />
          <Tooltip {...tooltipStyle(c)} formatter={(v: number) => [`${v.toFixed(2)}%`, 'Drawdown']} />
          <Area type="stepAfter" dataKey="dd" stroke={c.loss} fill={c.loss} fillOpacity={0.2} isAnimationActive={false} />
        </AreaChart>
      </ResponsiveContainer>
    </div>
  );
}

export function GroupBarChart({ groups, currency, labelFor }: { groups: GroupStat[]; currency: string; labelFor?: (k: string) => string }) {
  const c = useThemeColors();
  const data = groups.map((g) => ({
    key: labelFor ? labelFor(g.key) : g.label ?? humanize(g.key),
    pnl: Number(g.summary.netPnl),
    trades: g.summary.trades,
    winRate: g.summary.winRate,
  }));
  if (data.length === 0) return <p className="py-6 text-center text-sm text-muted">No closed trades in this range.</p>;
  return (
    <div style={{ height: Math.max(140, data.length * 30 + 30) }} role="img" aria-label={`P&L by group: ${data.map((d) => `${d.key} ${fmtMoney(d.pnl, currency)}`).join(', ')}`}>
      <ResponsiveContainer>
        <BarChart data={data} layout="vertical" margin={{ top: 4, right: 12, bottom: 4, left: 4 }}>
          <CartesianGrid stroke={c.line} strokeDasharray="3 3" horizontal={false} />
          <XAxis
            type="number"
            domain={[(min: number) => Math.min(0, min), (max: number) => Math.max(0, max)]}
            tick={{ fill: c.muted, fontSize: 10 }}
            tickLine={false}
            axisLine={false}
            tickFormatter={(v: number) => fmtMoney(v, currency, { compact: true })}
          />
          <YAxis type="category" dataKey="key" width={110} tick={{ fill: c.fg, fontSize: 11 }} tickLine={false} axisLine={false} />
          <ReferenceLine x={0} stroke={c.muted} />
          <Tooltip
            {...tooltipStyle(c)}
            cursor={{ fill: c.line, opacity: 0.3 }}
            formatter={(v: number, _n, p) => [`${fmtMoney(v, currency, { sign: true })} · ${p.payload.trades} trades · ${fmtPct(p.payload.winRate)} win`, 'P&L']}
          />
          <Bar dataKey="pnl" isAnimationActive={false} radius={[0, 3, 3, 0]}>
            {data.map((d) => (
              <Cell key={d.key} fill={d.pnl >= 0 ? c.profit : c.loss} />
            ))}
          </Bar>
        </BarChart>
      </ResponsiveContainer>
    </div>
  );
}

export function BandsChart({ bands }: { bands: Array<{ step: number; p5: number; p50: number; p95: number }> }) {
  const c = useThemeColors();
  const data = bands.map((b) => ({ step: b.step, p5: (b.p5 - 1) * 100, p50: (b.p50 - 1) * 100, p95: (b.p95 - 1) * 100 }));
  return (
    <div className="h-56" role="img" aria-label="Simulated equity percentile bands">
      <ResponsiveContainer>
        <LineChart data={data} margin={{ top: 8, right: 8, bottom: 0, left: 0 }}>
          <CartesianGrid stroke={c.line} strokeDasharray="3 3" vertical={false} />
          <XAxis dataKey="step" minTickGap={28} tick={{ fill: c.muted, fontSize: 10 }} tickLine={false} axisLine={false} label={{ value: 'trades', fill: c.muted, fontSize: 10, position: 'insideBottomRight', offset: -2 }} />
          <YAxis tick={{ fill: c.muted, fontSize: 10 }} tickLine={false} axisLine={false} width={48} tickFormatter={(v: number) => `${v.toFixed(0)}%`} />
          <Tooltip {...tooltipStyle(c)} formatter={(v: number, n) => [`${v.toFixed(1)}%`, n === 'p50' ? 'Median' : n === 'p5' ? '5th pct' : '95th pct']} />
          <ReferenceLine y={0} stroke={c.muted} />
          <Line dataKey="p95" stroke={c.profit} dot={false} strokeDasharray="4 3" isAnimationActive={false} />
          <Line dataKey="p50" stroke={c.accent} dot={false} strokeWidth={2} isAnimationActive={false} />
          <Line dataKey="p5" stroke={c.loss} dot={false} strokeDasharray="4 3" isAnimationActive={false} />
        </LineChart>
      </ResponsiveContainer>
    </div>
  );
}
