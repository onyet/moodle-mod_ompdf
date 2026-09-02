<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Enterprise security helper for mod_ompdf.
 *
 * @package    mod_ompdf
 * @copyright  2026 Dian Mukti Wibowo <onyetcorp@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ompdf;

/**
 * Provides authenticated encryption helpers for PDF URLs.
 */
class security {
    /**
     * Get secret key derived from Moodle site identifier.
     *
     * @return string 32-byte binary key
     */
    private static function get_secret_key(): string {
        $siteid = get_site_identifier();
        return hash('sha256', $siteid . 'mod_ompdf_enterprise_secret_2026', true);
    }

    /**
     * Encrypt a target file URL or string using AES-256-CBC with HMAC-SHA256 signature.
     *
     * @param string $data Plaintext file URL
     * @param int $ttl Lifetime in seconds (default 1800s / 30 mins)
     * @return string Base64 url-safe token
     */
    public static function encrypt_url(string $data, $cmid = 0, int $ttl = 1800): string {
        $key = self::get_secret_key();
        $iv = openssl_random_pseudo_bytes(16);
        $payload = json_encode([
            'url'  => $data,
            'cmid' => (int)($cmid ?? 0),
            'exp'  => time() + $ttl,
            'salt' => bin2hex(openssl_random_pseudo_bytes(8)),
        ]);

        $ciphertext = openssl_encrypt($payload, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $iv . $ciphertext, $key, true);

        $b64 = base64_encode($iv . $hmac . $ciphertext);
        return str_replace(['+', '/', '='], ['-', '_', ''], $b64);
    }

    /**
     * Decrypt an AES-256 encrypted payload token and return data array.
     *
     * @param string $token Encrypted token
     * @return array|null Array with 'url' and 'cmid' or null if invalid/expired
     */
    public static function decrypt_payload(string $token): ?array {
        $token = str_replace(['-', '_', ' '], ['+', '/', '+'], rawurldecode($token));
        $mod4 = strlen($token) % 4;
        if ($mod4) {
            $token .= substr('====', $mod4);
        }
        $raw = base64_decode($token);
        if (!$raw || strlen($raw) < 49) {
            return null;
        }

        $key = self::get_secret_key();
        $iv = substr($raw, 0, 16);
        $hmac = substr($raw, 16, 32);
        $ciphertext = substr($raw, 48);

        $calculatedhmac = hash_hmac('sha256', $iv . $ciphertext, $key, true);
        if (!hash_equals($hmac, $calculatedhmac)) {
            return null;
        }

        $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if (!$decrypted) {
            return null;
        }

        $data = json_decode($decrypted, true);
        if (!$data || empty($data['url']) || empty($data['exp'])) {
            return null;
        }

        if (time() > (int)$data['exp']) {
            return null; // Token expired.
        }

        return $data;
    }

    /**
     * Decrypt an AES-256 encrypted payload token and return URL string.
     *
     * @param string $token Encrypted token
     * @return string|null Plaintext URL or null if invalid/expired
     */
    public static function decrypt_url(string $token): ?string {
        $payload = self::decrypt_payload($token);
        return $payload ? $payload['url'] : null;
    }
}
