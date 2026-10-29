<?php

namespace App\Services\Personnel;

class GradeHierarchyService
{
    /**
     * Hierarchy Weight Constants
     */
    public const SCORE_PREMIER = 1000;        // JUSA / TURUS / Pengurus Besar

    public const SCORE_GRED_54 = 900;         // Gred 54 / N14 (Timbalan Pengurus Besar)

    public const SCORE_GRED_52 = 850;         // Gred 52 / N13 (Pengurus Bahagian / Pengurus Kanan)

    public const SCORE_GRED_48 = 800;         // Gred 48 / N12 / W12 / WA12 / J12 / F12 / G12 / L12

    public const SCORE_GRED_44 = 700;         // Gred 44 / N10 / W10 / WA10 / J10 / Ketua Unit

    public const SCORE_GRED_41 = 600;         // Gred 41/42 / N9 / G9 / J9 / FA9 / Pegawai

    public const SCORE_GRED_36_38 = 500;      // Gred 36-38 / N7 / G7 / JA7 / W7 / J7 / E7

    public const SCORE_GRED_32 = 400;         // Gred 32 / N6 / JA6 / FA6 / L6 / S6 / W6 / NT6

    public const SCORE_GRED_29 = 300;         // Gred 29-30 / N5 / JA5 / FA5 / G5 / W5 / NT5 / E5 / N29 / W29

    public const SCORE_GRED_22_26 = 250;      // Gred 22-26 / N3 / JA3 / N22

    public const SCORE_GRED_19 = 200;         // Gred 19 / N2 / JA2 / G2 / S2 / W2 / N19

    public const SCORE_GRED_11_14 = 150;      // Gred 11-14 / H11 / Penghantar Notis

    public const SCORE_GRED_1_10 = 120;       // Gred 1-10 / H1 / N1 / W1 / FT1 / KP1 / J1 / Pemandu / Pembantu Awam

    public const SCORE_MYSTEP = 50;           // MySTEP / Pelatih / Praktikal

    public const SCORE_DEFAULT = 100;         // Default active staff fallback

    public const SCORE_UNKNOWN = 0;

    /**
     * Calculate hierarchy score from grade code and job title.
     */
    public function calculateScore(?string $gred, ?string $jawatan): int
    {
        $g = strtoupper(trim((string) $gred));
        $j = strtoupper(trim((string) $jawatan));

        // 1. MySTEP check (even if gred has placeholder like 00, 1, none, or blank)
        if (str_contains($j, 'MYSTEP') || $g === '00' || $g === 'NONE') {
            return self::SCORE_MYSTEP;
        }

        // 2. Premier / JUSA Grade
        if (str_contains($g, 'JUSA') || str_contains($g, 'TURUS') || ($this->containsWord($j, 'PENGURUS BESAR') && ! str_contains($j, 'TIMBALAN'))) {
            return self::SCORE_PREMIER;
        }

        // 3. Timbalan Pengurus Besar / Gred 54 / N14
        if ($g === 'N14' || $this->matchesGradeNumber($g, 54) || str_contains($j, 'TIMBALAN PENGURUS BESAR')) {
            return self::SCORE_GRED_54;
        }

        // 4. Pengurus Bahagian / Gred 52 / N13 / N52
        if ($g === 'N13' || $g === 'N52' || $this->matchesGradeNumber($g, 52) || str_contains($j, 'PENGURUS BAHAGIAN') || str_contains($j, 'PENGURUS KANAN')) {
            return self::SCORE_GRED_52;
        }

        // 5. Pengurus / Gred 48 / N12, W12, WA12, J12, F12, G12, L12
        if (preg_match('/^(N|W|WA|J|F|G|L)12$/i', $g) || $this->matchesGradeNumber($g, 48) || (str_starts_with($j, 'PENGURUS') && ! str_contains($j, 'PENOLONG'))) {
            return self::SCORE_GRED_48;
        }

        // 6. Ketua Unit / Gred 44 / N10, W10, WA10, J10, N44
        if (preg_match('/^(N|W|WA|J)10$/i', $g) || $this->matchesGradeNumber($g, 44) || str_contains($j, 'KETUA UNIT') || str_contains($j, 'PENOLONG PENGURUS KANAN')) {
            return self::SCORE_GRED_44;
        }

        // 7. Pegawai / Penolong Pengurus / Gred 41/42 / N9, G9, J9, FA9, N41, G41
        if (
            preg_match('/^(N|G|J|FA)9$/i', $g)
            || $this->matchesGradeNumber($g, 41)
            || $this->matchesGradeNumber($g, 42)
            || str_contains($j, 'PENOLONG PENGURUS')
            || ($this->containsWord($j, 'PEGAWAI') && ! str_contains($j, 'PENOLONG') && ! str_contains($j, 'PEMBANTU'))
            || ($this->containsWord($j, 'JURUTERA') && ! str_contains($j, 'PENOLONG') && ! str_contains($j, 'PEN.'))
            || ($this->containsWord($j, 'JURUUKUR') && ! str_contains($j, 'PENOLONG') && ! str_contains($j, 'PEN.'))
            || ($this->containsWord($j, 'ARKITEK') && ! str_contains($j, 'PENOLONG') && ! str_contains($j, 'PEN.'))
            || ($this->containsWord($j, 'AKAUNTAN') && ! str_contains($j, 'PENOLONG') && ! str_contains($j, 'PEMBANTU'))
        ) {
            return self::SCORE_GRED_41;
        }

        // 8. Pelaksana Diploma Kanan (Gred 36-38) / N7, G7, JA7, W7, J7, E7
        if (preg_match('/^(N|G|JA|W|J|E)7$/i', $g) || $this->matchesGradeNumber($g, 36) || $this->matchesGradeNumber($g, 38) || str_contains($j, 'PENOLONG PEGAWAI KANAN')) {
            return self::SCORE_GRED_36_38;
        }

        // 9. Pelaksana Diploma (Gred 32) / N6, JA6, FA6, L6, S6, W6, NT6
        if (preg_match('/^(N|JA|FA|L|S|W|NT)6$/i', $g) || $this->matchesGradeNumber($g, 32)) {
            return self::SCORE_GRED_32;
        }

        // 10. Pelaksana Diploma (Gred 29-30) / N5, JA5, FA5, G5, W5, NT5, E5, N29, W29, N30
        if (
            preg_match('/^(N|JA|FA|G|W|NT|E)5$/i', $g)
            || $this->matchesGradeNumber($g, 29)
            || $this->matchesGradeNumber($g, 30)
            || str_contains($j, 'PENOLONG PEGAWAI')
            || str_contains($j, 'PEN. PEG')
            || str_contains($j, 'PEN. JURUTERA')
            || str_contains($j, 'PEN. JURUKUR')
        ) {
            return self::SCORE_GRED_29;
        }

        // 11. Pelaksana SPM Kanan (Gred 22-26) / N3, JA3, N22
        if (preg_match('/^(N|JA)3$/i', $g) || $this->matchesGradeNumber($g, 22) || $this->matchesGradeNumber($g, 26)) {
            return self::SCORE_GRED_22_26;
        }

        // 12. Pelaksana SPM (Gred 19) / N2, JA2, G2, S2, W2, N19, GRED 19
        if (
            preg_match('/^(N|JA|G|S|W)2$/i', $g)
            || $this->matchesGradeNumber($g, 19)
            || str_contains($g, '19')
            || str_contains($j, 'PEMBANTU TADBIR')
            || str_contains($j, 'PELUKIS PELAN')
            || str_contains($j, 'SETIAUSAHA PEJABAT')
            || str_contains($j, 'PEGAWAI KHIDMAT PELANGGAN')
        ) {
            return self::SCORE_GRED_19;
        }

        // 13. Pelaksana PMR/SRP (Gred 11-14) / H11
        if (preg_match('/^H11$/i', $g) || $this->matchesGradeNumber($g, 11) || $this->matchesGradeNumber($g, 14) || str_contains($j, 'PENGHANTAR NOTIS')) {
            return self::SCORE_GRED_11_14;
        }

        // 14. Pelaksana PMR/Bakat (Gred 1-10) / H1, N1, W1, FT1, KP1, J1
        if (
            preg_match('/^(H|N|W|FT|KP|J)1$/i', $g)
            || $g === '1'
            || str_contains($j, 'PEMANDU')
            || str_contains($j, 'PEMBANTU AWAM')
            || str_contains($j, 'PENYELAMAT')
            || str_contains($j, 'PEMBANTU OPERASI')
            || str_contains($j, 'PEKERJA AWAM')
            || str_contains($j, 'PEMBANTU KEMAHIRAN')
            || str_contains($j, 'PEMBANTU KHIDMAT AM')
            || str_contains($j, 'PENGAWAL KESELAMATAN')
            || str_contains($j, 'JURUTEKNIK KOMPUTER')
        ) {
            return self::SCORE_GRED_1_10;
        }

        // 15. Fallbacks
        if (! empty($j) || ! empty($g)) {
            return self::SCORE_DEFAULT;
        }

        return self::SCORE_UNKNOWN;
    }

    /**
     * Get user-friendly category title from hierarchy score.
     */
    public function getCategoryLabel(int $score): string
    {
        return match (true) {
            $score >= self::SCORE_PREMIER => 'Pengurusan Tertinggi',
            $score >= self::SCORE_GRED_41 => 'Pengurusan & Profesional',
            $score >= self::SCORE_GRED_29 => 'Pelaksana (Diploma)',
            $score >= self::SCORE_GRED_19 => 'Pelaksana (SPM)',
            $score >= self::SCORE_GRED_1_10 => 'Pelaksana (Operasi)',
            $score >= self::SCORE_MYSTEP => 'Personel MySTEP',
            default => 'Lain-lain',
        };
    }

    /**
     * Helper to match 2-digit grade numbers inside string (e.g. "N52" -> 52, "G41" -> 41).
     */
    protected function matchesGradeNumber(string $gred, int $number): bool
    {
        return preg_match('/(?:^|[^\d])'.$number.'(?:$|[^\d])/i', $gred) === 1;
    }

    /**
     * Helper to match complete word boundaries in title.
     */
    protected function containsWord(string $haystack, string $needle): bool
    {
        return preg_match('/(?:^|\b)'.preg_quote($needle, '/').'(?:\b|$)/i', $haystack) === 1;
    }
}
