<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class AnnouncementRecipientController extends Controller
{
    public function read(Announcement $announcement): RedirectResponse
    {
        $recipient = $announcement->recipients()->where('user_id', Auth::id())->firstOrFail();
        $recipient->update(['read_at' => $recipient->read_at ?? now()]);

        return back()->with('status', 'Comunicado marcado como leído.');
    }
}
