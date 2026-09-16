<?php

namespace App\Services;

use Illuminate\Http\Request;

class LegacyListNavigation
{
    public function returnUrl(Request $request, string $listRoute): string
    {
        $fallback = route($listRoute);
        $candidate = $request->input('return_url');
        if (! is_string($candidate) || strlen($candidate) > 4096 || preg_match('/[\x00-\x20\\\\]/', $candidate) || str_starts_with($candidate, '//')) {
            return $fallback;
        }

        $target = parse_url($candidate);
        $expected = parse_url($fallback);
        if ($target === false || isset($target['user']) || isset($target['pass']) || isset($target['fragment']) || ($target['path'] ?? '') !== $expected['path']) {
            return $fallback;
        }
        if (isset($target['scheme']) || isset($target['host'])) {
            foreach (['scheme', 'host', 'port'] as $key) {
                if (($target[$key] ?? null) !== ($expected[$key] ?? null)) {
                    return $fallback;
                }
            }
        }

        parse_str($target['query'] ?? '', $query);
        $allowed = ['search', 'q', 'mahasiswa_id', 'angkatan', 'semester', 'tahun_akademik', 'semester_akademik', 'fakultas_id', 'prodi_id', 'kelas_id', 'mata_kuliah_id', 'dosen_id', 'ruangan_id', 'hari', 'status', 'status_evaluasi', 'jenis', 'tahun', 'tanggal_mulai', 'tanggal_selesai', 'page'];
        $query = array_filter(array_intersect_key($query, array_flip($allowed)), fn ($value) => is_string($value));
        if (isset($query['page']) && (! ctype_digit($query['page']) || (int) $query['page'] < 1 || (int) $query['page'] > 100000)) {
            unset($query['page']);
        }

        // Always rebuild from the trusted route origin, never redirect to user-supplied hosts.
        return $fallback.($query ? '?'.http_build_query($query) : '');
    }
}
