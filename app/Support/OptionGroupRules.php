<?php

namespace App\Support;

/** Aturan konsistensi option group (selaras dengan CHECK option_groups_selection_check). */
final class OptionGroupRules
{
    /**
     * @return array<string, list<string>> kosong bila valid; kunci = nama field untuk ValidationException
     */
    public static function violations(int $min, int $max, bool $required): array
    {
        $errors = [];

        if ($min < 0) {
            $errors['min_selection'][] = 'Minimal pilihan tidak boleh negatif.';
        }
        if ($max < 1) {
            $errors['max_selection'][] = 'Maksimal pilihan minimal 1.';
        }
        if ($min >= 0 && $max >= 1 && $min > $max) {
            $errors['min_selection'][] = 'Minimal pilihan tidak boleh lebih besar dari maksimal.';
        }
        if ($required && $min < 1) {
            $errors['min_selection'][] = 'Group wajib dipilih membutuhkan minimal pilihan 1.';
        }

        return $errors;
    }
}
