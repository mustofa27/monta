<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SsoController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $state = Str::random(64);
        $request->session()->put('sso.oauth_state', $state);

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->ssoConfig('client_id'),
            'redirect_uri' => $this->ssoRedirectUri(),
            'scope' => $this->ssoConfig('scope', 'openid profile email'),
            'state' => $state,
        ]);

        return redirect()->away($this->ssoBaseUrl().'/oauth/authorize?'.$query);
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->query('error')) {
            abort(403, (string) $request->query('error_description', 'OAuth authorization failed.'));
        }

        $expectedState = (string) $request->session()->get('sso.oauth_state', '');
        $actualState = (string) $request->query('state', '');

        if ($expectedState === '' || $actualState === '' || ! hash_equals($expectedState, $actualState)) {
            abort(403, 'Invalid OAuth state.');
        }

        $request->session()->forget('sso.oauth_state');

        $authorizationCode = (string) $request->query('code', '');
        if ($authorizationCode === '') {
            abort(400, 'Missing authorization code.');
        }

        $tokenPayload = [
            'grant_type' => 'authorization_code',
            'code' => $authorizationCode,
            'client_id' => $this->ssoConfig('client_id'),
            'client_secret' => $this->ssoConfig('client_secret'),
            'redirect_uri' => $this->ssoRedirectUri(),
        ];

        $tokenResponse = Http::asForm()
            ->acceptJson()
            ->post($this->ssoBaseUrl().'/oauth/token', $tokenPayload);

        if (! $tokenResponse->successful()) {
            Log::warning('SSO token exchange failed.', [
                'status' => $tokenResponse->status(),
                'response' => $tokenResponse->json(),
            ]);

            abort(403, 'Token exchange failed.');
        }

        $tokenData = $tokenResponse->json();
        $accessToken = (string) data_get($tokenData, 'access_token', '');

        if ($accessToken === '') {
            Log::warning('SSO token response did not include access_token.', ['response' => $tokenData]);
            abort(403, 'Invalid token response.');
        }

        $userInfoResponse = Http::acceptJson()
            ->withToken($accessToken)
            ->get($this->ssoBaseUrl().'/oauth/userinfo');

        if (! $userInfoResponse->successful()) {
            Log::warning('SSO userinfo request failed.', [
                'status' => $userInfoResponse->status(),
                'response' => $userInfoResponse->json(),
            ]);

            abort(403, 'Failed to read SSO profile.');
        }

        $profile = $userInfoResponse->json();
        $sub = (string) data_get($profile, 'sub', '');

        if ($sub === '') {
            abort(403, 'Missing subject from SSO profile.');
        }

        $email = (string) data_get($profile, 'email', '');
        $name = (string) data_get($profile, 'name', 'SSO User');

        $normalizedEmail = Str::lower(trim($email));

        $user = null;

        if ($normalizedEmail !== '') {
            $user = User::query()->whereRaw('LOWER(email) = ?', [$normalizedEmail])->first();
        }

        if (! $user) {
            $user = User::query()->where('sso_sub', $sub)->first();
        }

        if (! $user) {
            $user = new User();
            $user->password = Str::password(32);
        }

        if ($normalizedEmail !== '') {
            $user->email = $normalizedEmail;
            $user->email_verified_at = now();
        }

        $user->name = $name;
        $user->sso_sub = $sub;
        $user->sso_user_type = data_get($profile, 'user_type');
        $user->sso_employee_type = data_get($profile, 'employee_type');
        $user->sso_profile = $profile;
        $user->save();

        $user->syncRolesBySlug($this->resolveRolesFromSsoProfile($profile));

        Auth::login($user, true);

        $request->session()->put('sso.tokens', [
            'access_token' => data_get($tokenData, 'access_token'),
            'refresh_token' => data_get($tokenData, 'refresh_token'),
            'token_type' => data_get($tokenData, 'token_type', 'Bearer'),
            'expires_in' => (int) data_get($tokenData, 'expires_in', 0),
            'scope' => data_get($tokenData, 'scope', ''),
            'issued_at' => now()->timestamp,
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function refreshToken(Request $request): JsonResponse
    {
        $refreshToken = (string) data_get($request->session()->get('sso.tokens'), 'refresh_token', '');

        if ($refreshToken === '') {
            return response()->json(['message' => 'No refresh token available.'], 400);
        }

        $tokenResponse = Http::asForm()
            ->acceptJson()
            ->post($this->ssoBaseUrl().'/oauth/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $this->ssoConfig('client_id'),
                'client_secret' => $this->ssoConfig('client_secret'),
            ]);

        if (! $tokenResponse->successful()) {
            Log::warning('SSO refresh token exchange failed.', [
                'status' => $tokenResponse->status(),
                'response' => $tokenResponse->json(),
            ]);

            return response()->json([
                'message' => 'Token refresh failed.',
                'error' => $tokenResponse->json('error'),
            ], $tokenResponse->status());
        }

        $tokenData = $tokenResponse->json();

        $request->session()->put('sso.tokens', [
            'access_token' => data_get($tokenData, 'access_token'),
            'refresh_token' => data_get($tokenData, 'refresh_token'),
            'token_type' => data_get($tokenData, 'token_type', 'Bearer'),
            'expires_in' => (int) data_get($tokenData, 'expires_in', 0),
            'scope' => data_get($tokenData, 'scope', ''),
            'issued_at' => now()->timestamp,
        ]);

        return response()->json([
            'message' => 'Token refreshed.',
            'data' => [
                'expires_in' => (int) data_get($tokenData, 'expires_in', 0),
                'scope' => (string) data_get($tokenData, 'scope', ''),
            ],
        ]);
    }

    public function triggerBackchannelLogout(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['nullable', 'integer'],
            'user_email' => ['nullable', 'email'],
        ]);

        if (! $request->filled('user_id') && ! $request->filled('user_email')) {
            return response()->json(['message' => 'Either user_id or user_email is required.'], 422);
        }

        $payload = [
            'client_id' => $this->ssoConfig('client_id'),
            'timestamp' => now()->timestamp,
        ];

        if ($request->filled('user_id')) {
            $payload['user_id'] = (int) $request->integer('user_id');
        }

        if ($request->filled('user_email')) {
            $payload['user_email'] = (string) $request->string('user_email');
        }

        $rawBody = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $rawBody, (string) $this->ssoConfig('client_secret'));

        $response = Http::acceptJson()
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-SSO-Signature' => $signature,
            ])
            ->withBody($rawBody, 'application/json')
            ->post($this->ssoBaseUrl().'/sso/backchannel/logout');

        return response()->json($response->json(), $response->status());
    }

    public function receiveBackchannelLogout(Request $request): JsonResponse
    {
        $signature = (string) $request->header('X-SSO-Signature', '');
        $rawBody = (string) $request->getContent();
        $expectedSignature = hash_hmac('sha256', $rawBody, (string) $this->ssoConfig('client_secret'));

        if ($signature === '' || ! hash_equals($expectedSignature, $signature)) {
            Log::warning('Rejected SSO backchannel logout notification due to invalid signature.');

            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $payload = $request->json()->all();
        $sub = (string) data_get($payload, 'user_id', '');
        $email = (string) data_get($payload, 'user_email', '');

        $user = User::query()
            ->where(function ($query) use ($sub, $email) {
                if ($sub !== '') {
                    $query->where('sso_sub', $sub);
                }

                if ($email !== '') {
                    $method = $sub !== '' ? 'orWhere' : 'where';
                    $query->{$method}('email', $email);
                }
            })
            ->first();

        if ($user) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        return response()->json(['message' => 'Logout notification received.']);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function dashboard(Request $request): View
    {
        return view('dashboard', [
            'user' => $request->user(),
            'ssoTokens' => $request->session()->get('sso.tokens', []),
        ]);
    }

    private function ssoBaseUrl(): string
    {
        return rtrim((string) $this->ssoConfig('base_url', 'https://sso.poltera.ac.id'), '/');
    }

    private function ssoRedirectUri(): string
    {
        $configured = (string) $this->ssoConfig('redirect_uri', '');

        return $configured !== '' ? $configured : URL::route('sso.callback');
    }

    private function ssoConfig(string $key, mixed $default = null): mixed
    {
        return config('services.sso.'.$key, $default);
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<int, string>
     */
    private function resolveRolesFromSsoProfile(array $profile): array
    {
        $rolesFromSso = $this->extractSsoRoleNames($profile);
        $ssoRoleMapping = config('sso.sso_role_mapping', []);

        foreach ($rolesFromSso as $ssoRole) {
            if (isset($ssoRoleMapping[$ssoRole]) && is_array($ssoRoleMapping[$ssoRole]) && $ssoRoleMapping[$ssoRole] !== []) {
                return array_values(array_unique($ssoRoleMapping[$ssoRole]));
            }
        }

        $userType = Str::lower((string) data_get($profile, 'user_type', ''));
        $employeeType = Str::lower((string) data_get($profile, 'employee_type', ''));

        $mapping = config('sso.role_mapping', []);
        $keys = [
            $userType.':'.$employeeType,
            $userType.':*',
        ];

        foreach ($keys as $key) {
            if (isset($mapping[$key]) && is_array($mapping[$key]) && $mapping[$key] !== []) {
                return array_values(array_unique($mapping[$key]));
            }
        }

        $fallback = config('sso.fallback_roles', ['mahasiswa']);

        return is_array($fallback) ? array_values(array_unique($fallback)) : ['mahasiswa'];
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<int, string>
     */
    private function extractSsoRoleNames(array $profile): array
    {
        $rawRoles = [];

        foreach (['role', 'roles', 'user_role', 'user_roles'] as $key) {
            $value = data_get($profile, $key);

            if (is_string($value) && trim($value) !== '') {
                $rawRoles[] = $value;
            }

            if (is_array($value)) {
                foreach ($value as $nestedRole) {
                    if (is_string($nestedRole) && trim($nestedRole) !== '') {
                        $rawRoles[] = $nestedRole;
                    }
                }
            }
        }

        $normalized = array_map(
            fn (string $role): string => Str::of($role)->lower()->squish()->toString(),
            $rawRoles
        );

        return array_values(array_unique(array_filter($normalized)));
    }
}
