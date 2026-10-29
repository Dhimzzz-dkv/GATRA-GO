<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PasswordHistoryService
{
    /**
     * Number of previous password hashes retained in addition to the current password.
     */
    private const PREVIOUS_PASSWORD_LIMIT = 4;

    /**
     * Determine whether a candidate matches the current or one of the four previous passwords.
     */
    public function hasBeenUsed(User $user, string $plainTextPassword): bool
    {
        $currentPasswordHash = $user->getRawOriginal('password');

        if (is_string($currentPasswordHash) && Hash::check($plainTextPassword, $currentPasswordHash)) {
            return true;
        }

        $previousPasswordHashes = DB::table('user_password_histories')
            ->where('user_id', $user->getKey())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::PREVIOUS_PASSWORD_LIMIT)
            ->pluck('password_hash');

        foreach ($previousPasswordHashes as $passwordHash) {
            if (Hash::check($plainTextPassword, $passwordHash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Store the current hash before a password is replaced and keep only four previous hashes.
     */
    public function recordCurrentPassword(User $user): void
    {
        $currentPasswordHash = $user->getRawOriginal('password');

        if (! is_string($currentPasswordHash) || $currentPasswordHash === '') {
            return;
        }

        DB::table('user_password_histories')->insert([
            'user_id' => $user->getKey(),
            'password_hash' => $currentPasswordHash,
            'created_at' => now(),
        ]);

        $idsToKeep = DB::table('user_password_histories')
            ->where('user_id', $user->getKey())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::PREVIOUS_PASSWORD_LIMIT)
            ->pluck('id');

        if ($idsToKeep->isNotEmpty()) {
            DB::table('user_password_histories')
                ->where('user_id', $user->getKey())
                ->whereNotIn('id', $idsToKeep)
                ->delete();
        }
    }
}
