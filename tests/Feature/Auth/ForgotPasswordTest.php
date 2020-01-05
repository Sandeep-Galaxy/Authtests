<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_receives_an_email_with_a_password_reset_link()
    {
        Notification::fake();
      
        $user = factory(\App\User::class)->create();
      
        $response = $this->post('/password/email', [
            'email' => $user->email,
        ]);
      
        // assertions go here
        $this->assertNotNull($token = \DB::table('password_resets')->first());
        Notification::assertSentTo($user, ResetPassword::class, function ($notification, $channels) use ($token) {
            return Hash::check($notification->token, $token->token) === true;
        });

    }

    public function test_user_does_not_receive_email_when_not_registered()
    {
        Notification::fake();
        $response = $this->from('password/reset')->post('password/email', [
            'email' => 'nobody@example.com',
        ]);
        $response->assertRedirect('password/reset');
        $response->assertSessionHasErrors('email');
        Notification::assertNotSentTo(factory(\App\User::class)->make(['email' => 'nobody@example.com']), ResetPassword::class);
    }

    public function test_email_is_required()
    {
        $response = $this->from('password/reset')->post('password/email', []);
        $response->assertRedirect('password/reset');
        $response->assertSessionHasErrors('email');
    }

    public function test_email_is_a_valid_email()
    {
        $response = $this->from('password/reset')->post('password/email', [
            'email' => 'invalid-email',
        ]);
        $response->assertRedirect('password/reset');
        $response->assertSessionHasErrors('email');
    }
}
