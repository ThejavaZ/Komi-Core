<?php

namespace App\Jobs;

use App\Models\AdminLog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UnbanExpiredUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $users = User::where('status', 'banned')
            ->whereNotNull('banned_until')
            ->where('banned_until', '<=', now())
            ->get();

        foreach ($users as $user) {
            $old = ['status' => $user->status, 'banned_until' => $user->banned_until];
            $user->update([
                'status' => 'active',
                'banned_until' => null,
            ]);
            AdminLog::log('user.unban.expired', $user, $old, [
                'status' => 'active',
                'banned_until' => null,
            ]);
        }
    }
}
