<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use LdapRecord\Models\ActiveDirectory\User as LdapUser;

class AuthController extends Controller
{
    public function showLogin()
    {
        $captcha = $this->generateCaptchaData();
        return view('auth.login', [
            'captcha_image' => $captcha['image']
        ]);
    }

    public function refreshCaptcha()
    {
        $captcha = $this->generateCaptchaData();
        return response()->json([
            'captcha_image' => $captcha['image']
        ]);
    }

    private function generateCaptchaData()
    {
        $num1 = rand(10, 50);
        $num2 = rand(1, 10);
        $operators = ['+', '-'];
        $operator = $operators[array_rand($operators)];

        if ($operator === '-') {
            if ($num1 < $num2) {
                $temp = $num1;
                $num1 = $num2;
                $num2 = $temp;
            }
            $answer = $num1 - $num2;
        } else {
            $answer = $num1 + $num2;
        }

        session(['captcha_answer' => $answer]);
        $captcha_question = "$num1 $operator $num2 =";

        // Create Image
        $width = 160;
        $height = 50;
        $image = imagecreatetruecolor($width, $height);

        // Colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $bg_color = imagecolorallocate($image, 248, 249, 250); // Light gray/blue
        $text_color = imagecolorallocate($image, 31, 59, 179); // Primary blue
        $noise_color = imagecolorallocate($image, 180, 180, 180);
        $line_color = imagecolorallocate($image, 219, 58, 232); // Purple line

        imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

        // Add Noise
        for ($i = 0; $i < 50; $i++) {
            imagesetpixel($image, rand(0, $width), rand(0, $height), $noise_color);
        }

        // Add Line (like in example)
        imageline($image, 10, rand(30, 45), $width - 10, rand(5, 20), $line_color);
        imageline($image, 0, rand(0, $height), $width, rand(0, $height), $noise_color);

        // Add Text
        // Using built-in font (1-5)
        $font_size = 5;
        $x = 20;
        $y = 15;
        imagestring($image, $font_size, $x, $y, $captcha_question, $text_color);

        // Capture Image
        ob_start();
        imagepng($image);
        $image_data = ob_get_clean();
        imagedestroy($image);

        return [
            'image' => 'data:image/png;base64,' . base64_encode($image_data)
        ];
    }

    public function login(Request $request)
    {
        $rules = [
            'username' => ['required', 'string'],
            'password' => ['required'],
        ];

        if (config('app.env') !== 'local') {
            $rules['captcha'] = ['required', 'numeric'];
        }

        $request->validate($rules);

        // Validasi Captcha
        if (config('app.env') !== 'local' && $request->captcha != session('captcha_answer')) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['captcha' => 'Jawaban captcha salah. Silakan coba lagi.']);
        }

        $username = $request->username;
        $password = $request->password;
        $allowedGroup = config('ldap.allowed_group', 'Monitoring Medrec');

        // ─── Langkah 1: Bypass untuk admin lokal ─────────────────────────
        if ($username === 'adminarya') {
            $localUser = \App\Models\User::where('username', 'adminarya')->first();
            if ($localUser && \Illuminate\Support\Facades\Hash::check($password, $localUser->password)) {
                Auth::login($localUser, $request->boolean('remember'));
                $request->session()->regenerate();
                return redirect()->intended('/');
            }
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Password salah. Silakan coba lagi.']);
        }

        // ─── Langkah 2: Cari user di Active Directory ─────────────────────────
        // Cek apakah input adalah email atau username biasa
        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $ldapUser = LdapUser::where('mail', $username)->first();
        } else {
            $ldapUser = LdapUser::where('samaccountname', $username)->first();
        }

        if (!$ldapUser) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Username atau email tidak ditemukan di Active Directory.']);
        }

        // Ambil samaccountname asli dari LDAP (untuk keperluan Auth::attempt)
        $samaccountname = $ldapUser->getFirstAttribute('samaccountname');

        // ─── Langkah 2: Cek keanggotaan grup via atribut memberOf ─────────────
        // Ambil semua DN grup yang dimiliki user (termasuk nested group)
        $memberOf = $ldapUser->getAttribute('memberof') ?? [];
        $groupSearch = 'cn=' . strtolower($allowedGroup);
        $isMember = false;

        foreach ($memberOf as $groupDn) {
            if (str_contains(strtolower($groupDn), $groupSearch)) {
                $isMember = true;
                break;
            }
        }

        // Jika tidak ditemukan di memberOf langsung, coba metode rekursif
        if (!$isMember) {
            try {
                $groups = $ldapUser->groups()->recursive()->get();
                $isMember = $groups->contains(function ($group) use ($allowedGroup) {
                    $cn = $group->getFirstAttribute('cn');
                    return $cn && strtolower($cn) === strtolower($allowedGroup);
                });
            } catch (\Exception $e) {
                $isMember = false;
            }
        }

        if (!$isMember) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Akun Anda tidak memiliki akses.']);
        }

        // ─── Langkah 3: Autentikasi password ke AD ────────────────────────────
        // Auth::attempt() juga akan menjalankan OnlySarprasGroup Rule sebagai
        // lapisan perlindungan kedua.
        $credentials = [
            'samaccountname' => $samaccountname,
            'password' => $password,
        ];

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Password salah atau Anda tidak memiliki akses LDAP.']);
        }

        // ─── Langkah 4: Assign role default untuk user baru ───────────────────
        $localUser = Auth::user();
        if (empty($localUser->role)) {
            $localUser->role = 'viewer';
            $localUser->save();
        }

        $request->session()->regenerate();
        return redirect()->intended('/');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}