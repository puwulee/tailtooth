<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Contracts\LineProfileFetcher;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/** LINE Login：報名身分綁定（唯一線上渠道）。 */
class LineLoginController extends Controller
{
    public function __construct(private SettingsService $settings) {}

    /** 導向 LINE 授權頁。 */
    public function redirect(Request $request)
    {
        $channelId = $this->settings->get('line.login_channel_id');
        abort_unless($channelId, 503, 'LINE Login 尚未設定');

        $state = Str::random(32);
        $request->session()->put('line_oauth_state', $state);

        $url = 'https://access.line.me/oauth2/v2.1/authorize?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $channelId,
            'redirect_uri' => $this->callbackUrl(),
            'state' => $state,
            'scope' => 'profile openid email',
        ]);

        return redirect($url);
    }

    /** 授權回呼：建立/綁定帳號並登入。 */
    public function callback(Request $request, LineProfileFetcher $fetcher)
    {
        abort_unless($request->query('state') === $request->session()->pull('line_oauth_state'), 419, 'state 驗證失敗');
        abort_unless($request->filled('code'), 400, '缺少授權碼');

        $profile = $fetcher->fetch($request->query('code'), $this->callbackUrl());
        abort_unless($profile['userId'] ?? false, 422, '無法取得 LINE 使用者');

        $user = User::firstOrNew(['line_user_id' => $profile['userId']]);
        $isNew = ! $user->exists;
        $user->fill([
            'name' => $profile['displayName'] ?? 'LINE 使用者',
            'avatar_url' => $profile['pictureUrl'] ?? null,
            'email' => $profile['email'] ?? ($profile['userId'] . '@line.local'),
        ]);
        if ($isNew) {
            $user->password = \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(40));
        }
        $user->save();

        if ($isNew || $user->roles()->count() === 0) {
            $user->assignRole('participant');
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended('/me');
    }

    private function callbackUrl(): string
    {
        return $this->settings->get('line.login_callback', url('/auth/line/callback'));
    }
}
