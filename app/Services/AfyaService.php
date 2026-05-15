<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AfyaService
{
    protected $baseUrl;
    protected $username;
    protected $password;

    public function __construct()
    {
        $this->baseUrl  = env('AFYA_API_URL', 'http://152.118.52.27:8081');
        $this->username = env('AFYA_API_USER', 'apm01');
        $this->password = env('AFYA_API_PASS', '123456789');
    }

    /**
     * Ambil Token (cached 30 menit)
     */
    public function getToken(): ?string
    {
        return Cache::remember('afya_token', 1800, function () {
            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent'   => 'insomnia/12.4.0',
                ])
                ->withoutVerifying()
                ->timeout(10)
                ->post($this->baseUrl . '/api/v2/auth/login', [
                    'username' => $this->username,
                    'password' => $this->password,
                ]);

                $code = $response->json('metadata.code');
                if ($code == 200 || $code == 202) {
                    return $response->json('results.0.tokenKey');
                }

                Log::warning('Afya login gagal', ['response' => $response->json()]);
            } catch (\Exception $e) {
                Log::error('Afya getToken error: ' . $e->getMessage());
            }

            return null;
        });
    }

    /**
     * Cari data pasien berdasarkan No RM
     * Menggunakan endpoint management-show (ranap/pulang)
     */
    public function getPatientByNoRM(string $noRm): ?array
    {
        $token = $this->getToken();
        if (!$token) return null;

        try {
            $response = Http::withHeaders([
                'token'        => $token,
                'Content-Type' => 'application/json',
                'User-Agent'   => 'insomnia/12.4.0',
            ])
            ->withoutVerifying()
            ->timeout(15)
            ->post($this->baseUrl . '/api/v8/references/bed/management-show', [
                'pageNumber'       => 1,
                'pageSize'         => 5,
                'name'             => null,
                'mrn'              => $noRm,
                'billingNo'        => null,
                'treatmentTypeKey' => null,
            ]);

            if ($response->json('metadata.code') != 200) {
                Log::warning('Afya management-show gagal', ['mrn' => $noRm, 'resp' => $response->json()]);
                return null;
            }

            $results = $response->json('results');
            if (!empty($results)) {
                return $results[0]; // Ambil data pertama yang ditemukan
            }
        } catch (\Exception $e) {
            Log::error('Afya getPatientByNoRM error: ' . $e->getMessage());
        }

        return null;
    }
}
