<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PushSubscriptionController extends Controller
{
    /**
     * Update user's push subscription.
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'endpoint'    => 'required',
            'keys.auth'   => 'required',
            'keys.p256dh' => 'required'
        ]);

        $endpoint = $request->endpoint;
        $token = $request->keys['auth'];
        $key = $request->keys['p256dh'];

        $user = auth()->user();

        $user->updatePushSubscription($endpoint, $key, $token);

        return response()->json(['success' => true], 200);
    }
}
