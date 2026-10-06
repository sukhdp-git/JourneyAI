<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Database;
use App\Trading\Payments;

/** Plan & Billing page inside the terminal (uses the terminal guard: signed-in, onboarded member). */
final class BillingPage extends TerminalController
{
    public function show(): never
    {
        $this->render('billing', [
            'plans' => Payments::plans(), 'gateway' => Payments::gateway(),
            'payments' => Database::all('SELECT p.*, pl.name AS plan_name FROM payments p LEFT JOIN plans pl ON pl.id = p.plan_id WHERE p.user_id = :u AND p.status <> \'created\' ORDER BY p.id DESC LIMIT 50', ['u' => $this->uid]),
        ], 'Plan & Billing', 'billing');
    }
}
