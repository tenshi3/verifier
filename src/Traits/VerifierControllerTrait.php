<?php namespace Coderjp\Verifier\Traits;

/**
 * This file is part of Verifier,
 *
 * Adds routes to the controller for verifying email addresses
 *
 * @license MIT
 * @package Coderjp\Verifier
 */

use Auth;
use Coderjp\Notify\Facades\Notify;
use Config;
use Illuminate\Http\Request;

trait VerifierControllerTrait
{
    /**
     * @param Request $request
     * @param string $code
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function verifyAttempt(Request $request, $code = null)
    {
        // If a code is specified, attempt to verify it
        if(!empty($code)) {
            $model = static::getModel();
            $user = $model::verify($code);

            // If verified, login the user TODO: Optional?
            if ($user) {
                Auth::login($user);
                Notify::success('<strong>Welcome!</strong> Email verification successful.');
                return redirect()->intended(property_exists($this, 'redirectAfterVerify') ? $this->redirectAfterVerify : '/');
            }
        }

        return view(Config::get('verifier.verify_template'))->with('attempt', $code ? true : false);
    }

    /**
     * Posted email address, attempts to resend verification message
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resendVerification(Request $request)
    {
        // Validate email address
        $this->validate($request, [
            'email' => 'email|required',
        ]);

        // Retrieve user with that email address
        $model = static::getModel();
        $user = $model::where('email', $request->get('email'))->first();

        // If the user is valid and not verified, send verification email
        if($user && !$user->verified) {

            $user->sendVerification();

            Notify::success('Email verification code has been sent.');
            return redirect()->back();
        } else {
            return redirect()->back()
                ->withErrors([
                    'email' => 'Invalid email address.',
                ]);
        }
    }

    /**
     * Model used for login, typically \App\User
     * @return string
     */
    public static function getModel()
    {
        return "\\App\\User";
    }

}
