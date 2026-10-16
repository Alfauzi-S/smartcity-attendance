<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Event;
use App\Models\StatusHistory;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $user = Auth::require(Auth::SUPER);
        $event = $this->currentEvent($user);
        $this->render('super/dashboard', [
            'title' => t('Dashboard'),
            'active' => 'dashboard',
            'event' => $event,
            'events' => Event::all(),
            'stats' => $event ? Event::stats((int) $event['id']) : null,
            'slots' => $event ? Event::checkinSlots($event) : [],
            'history' => $event ? StatusHistory::list((int) $event['id'], 'all', 4) : [],
        ]);
    }
}
