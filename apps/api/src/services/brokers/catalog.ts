import type { BrokerProviderInfo } from '@journzey/shared';

/**
 * Honest connector catalogue. "connectable" connectors map onto a working adapter:
 *  csv_import (statement import), webhook (signed push), binance (REST API, beta).
 * Everything else is labelled with its real status — nothing pretends to sync.
 */
export const BROKER_CATALOG: BrokerProviderInfo[] = [
  { id: 'csv_import', name: 'Statement Import (CSV)', status: 'AVAILABLE', method: 'MT4/MT5, cTrader, NinjaTrader history export', description: 'Upload a trade-history CSV. Columns are auto-detected; duplicates are skipped by ticket number.', connectable: true },
  { id: 'webhook', name: 'Signed Webhook', status: 'AVAILABLE', method: 'HMAC-SHA256 signed HTTPS push', description: 'Push closed trades from an MT5 EA, TradingView alert relay or your own script. Replay-protected and idempotent.', connectable: true },
  { id: 'binance', name: 'Binance Spot', status: 'BETA', method: 'Read-only API key (HMAC-signed REST)', description: 'Imports spot fills for selected symbols and pairs buys/sells FIFO into round-trip trades.', connectable: true },
  { id: 'vantage', name: 'Vantage', status: 'AVAILABLE', method: 'Via MT4/MT5 CSV import or signed webhook', description: 'Vantage does not offer a public trade-history API; use the MT4/MT5 history export.', connectable: false },
  { id: 'exness', name: 'Exness', status: 'AVAILABLE', method: 'Via MT4/MT5 CSV import or signed webhook', description: 'Use the MT4/MT5 account history export (Report → Save as CSV/Excel).', connectable: false },
  { id: 'ftmo', name: 'FTMO', status: 'AVAILABLE', method: 'Via MT4/MT5 or cTrader CSV import', description: 'Export history from the FTMO platform you trade on and import it here.', connectable: false },
  { id: 'fundednext', name: 'FundedNext', status: 'AVAILABLE', method: 'Via MT4/MT5 or cTrader CSV import', description: 'Export history from your FundedNext trading platform and import it here.', connectable: false },
  { id: 'ninjatrader', name: 'NinjaTrader', status: 'AVAILABLE', method: 'Via Trade Performance CSV export', description: 'Export trades from Trade Performance → Trades grid and import the CSV.', connectable: false },
  { id: 'ctrader', name: 'cTrader Open API', status: 'COMING_SOON', method: 'OAuth (requires registered cTrader application)', description: 'Direct sync is not implemented yet. cTrader CSV history import works today.', connectable: false },
  { id: 'tradovate', name: 'Tradovate', status: 'COMING_SOON', method: 'REST API (requires vendor approval)', description: 'Direct sync is not implemented yet.', connectable: false },
  { id: 'topstep', name: 'Topstep', status: 'COMING_SOON', method: 'Depends on Tradovate/Rithmic access', description: 'No public journal API is available; direct sync is not implemented.', connectable: false },
  { id: 'apex', name: 'Apex Trader Funding', status: 'COMING_SOON', method: 'Depends on Rithmic/Tradovate access', description: 'No public journal API is available; direct sync is not implemented.', connectable: false },
  { id: 'execution_lock', name: 'Broker Execution Lock', status: 'UNSUPPORTED', method: 'n/a', description: 'journzey.ai cannot block orders at any broker. The Tilt Circuit Breaker locks the journzey terminal only.', connectable: false },
];
