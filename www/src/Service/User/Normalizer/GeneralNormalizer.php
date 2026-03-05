<?php

namespace App\Service\User\Normalizer;

class GeneralNormalizer
{
    /**
     * @param array $data
     * @return array
     */
    public function clean(array $data): array
    {
        $cleanedData = array_map(function ($value) {
            return trim($value);
        }, $data);

        $cleanedData['email'] = mb_strtolower($cleanedData['email']);
        return $cleanedData;
    }
}