import { useMemo, useState } from 'react';
import { INSTRUMENTS, lotSizeForRisk } from '@journzey/shared';
import { Field, Input, Modal, Select } from '../ui';
import { fmtMoney } from '../../lib/format';
import { useAccounts, useSettings } from '../../lib/queries';

export function LotSizeCalculator({ open, onClose }: { open: boolean; onClose: () => void }) {
  const settings = useSettings();
  const accounts = useAccounts();
  const primary = accounts.data?.find((a) => !a.sampleData && !a.archived) ?? accounts.data?.[0];
  const [symbol, setSymbol] = useState('XAUUSD');
  const [equity, setEquity] = useState('');
  const [risk, setRisk] = useState(settings.defaultRiskPercentage);
  const [entry, setEntry] = useState('');
  const [stop, setStop] = useState('');
  const currency = primary?.currency ?? settings.baseCurrency;
  const eq = equity || primary?.currentCapital || '';
  const result = useMemo(() => {
    if (!(Number(eq) > 0 && Number(risk) > 0 && Number(entry) > 0 && Number(stop) > 0) || entry === stop) return null;
    try {
      return lotSizeForRisk({ symbol, equity: eq, riskPercent: risk, entry, stopLoss: stop, accountCurrency: currency });
    } catch {
      return null;
    }
  }, [symbol, eq, risk, entry, stop, currency]);

  return (
    <Modal open={open} onClose={onClose} title="Lot Size Calculator">
      <div className="grid grid-cols-2 gap-3">
        <Field label="Instrument" htmlFor="ls-sym" className="col-span-2">
          <Select id="ls-sym" value={symbol} onChange={(e) => setSymbol(e.target.value)}>
            {INSTRUMENTS.map((i) => (
              <option key={i.symbol} value={i.symbol}>
                {i.symbol} — {i.displayName}
              </option>
            ))}
          </Select>
        </Field>
        <Field label={`Equity (${currency})`} htmlFor="ls-eq">
          <Input id="ls-eq" className="num" inputMode="decimal" placeholder={primary?.currentCapital} value={equity} onChange={(e) => setEquity(e.target.value)} />
        </Field>
        <Field label="Risk %" htmlFor="ls-risk">
          <Input id="ls-risk" className="num" inputMode="decimal" value={risk} onChange={(e) => setRisk(e.target.value)} />
        </Field>
        <Field label="Entry" htmlFor="ls-entry">
          <Input id="ls-entry" className="num" inputMode="decimal" value={entry} onChange={(e) => setEntry(e.target.value)} />
        </Field>
        <Field label="Stop loss" htmlFor="ls-stop">
          <Input id="ls-stop" className="num" inputMode="decimal" value={stop} onChange={(e) => setStop(e.target.value)} />
        </Field>
      </div>
      <div className="mt-4 rounded-md border border-line bg-panel2 p-3" aria-live="polite">
        {result ? (
          <dl className="grid grid-cols-3 gap-2 text-center">
            <div>
              <dt className="label">Lots</dt>
              <dd className="num text-2xl font-bold text-accent">{result.lots}</dd>
            </div>
            <div>
              <dt className="label">Risk amount</dt>
              <dd className="num text-sm">{fmtMoney(result.riskAmount, currency)}</dd>
            </div>
            <div>
              <dt className="label">Risk / 1.00 lot</dt>
              <dd className="num text-sm">{fmtMoney(result.perLotRisk, currency)}</dd>
            </div>
          </dl>
        ) : (
          <p className="text-sm text-muted">Enter equity, risk %, entry and stop. Instruments quoted in a currency other than your account currency (and not its base) need a broker-side calculation.</p>
        )}
      </div>
      <p className="mt-2 text-2xs text-muted">Uses standard contract sizes; confirm your broker’s contract specification before trading.</p>
    </Modal>
  );
}
