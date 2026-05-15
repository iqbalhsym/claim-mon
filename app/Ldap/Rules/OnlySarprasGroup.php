<?php

namespace App\Ldap\Rules;

use LdapRecord\Laravel\Auth\Rule;
use LdapRecord\Models\Model as LdapModel;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class OnlySarprasGroup implements Rule
{
    /**
     * Cek apakah user AD adalah anggota grup yang diizinkan.
     * Dipanggil otomatis di dalam Auth::attempt().
     * Return false = login ditolak meski password benar.
     */
    public function passes(LdapModel $user, ?EloquentModel $model = null): bool
    {
        $allowedGroup = config('ldap.allowed_group', 'Monitoring Medrec');

        // Ambil semua grup termasuk nested/recursive
        $groups = $user->groups()->recursive()->get();

        foreach ($groups as $group) {
            $cn = $group->getFirstAttribute('cn');
            if (!is_null($cn) && strtolower($cn) === strtolower($allowedGroup)) {
                return true;
            }
        }

        return false;
    }
}
