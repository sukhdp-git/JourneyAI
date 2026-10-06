import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { App } from './App';
import './index.css';

const qc = new QueryClient({ defaultOptions: { queries: { refetchOnWindowFocus: false, retry: 1, staleTime: 10_000 } } });

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <QueryClientProvider client={qc}>
      {/* Clean URLs: /control-panel/, /control-panel/login, /control-panel/users … */}
      <BrowserRouter basename="/control-panel">
        <App />
      </BrowserRouter>
    </QueryClientProvider>
  </StrictMode>,
);
