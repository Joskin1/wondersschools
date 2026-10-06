<?php

use App\Mail\TenantAdminCreated;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

describe('TenantAdminCreated Mailable', function () {

    it('implements ShouldQueue interface for asynchronous background sending', function () {
        $user = new User([
            'name' => 'School Admin',
            'email' => 'admin@school.com',
            'role' => 'admin',
        ]);

        $mailable = new TenantAdminCreated($user, 'secret123', 'https://school.wonders.app/admin/login');

        expect($mailable)->toBeInstanceOf(ShouldQueue::class);
    });

    it('can be queued via Mail facade', function () {
        Mail::fake();

        $user = new User([
            'name' => 'School Admin',
            'email' => 'admin@school.com',
            'role' => 'admin',
        ]);

        Mail::to($user->email)->queue(
            new TenantAdminCreated($user, 'temp-pass', 'https://school.wonders.app/admin/login')
        );

        Mail::assertQueued(TenantAdminCreated::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email)
                && $mail->loginUrl === 'https://school.wonders.app/admin/login'
                && $mail->password === 'temp-pass';
        });
    });
});
