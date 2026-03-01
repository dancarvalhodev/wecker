<?php

namespace App\Service\User\Normalizer;

class FormNormalizer
{
    public function clean(array $data): array
    {
        $cleanedData = [];

        foreach ($data as $key => $value) {
            $cleanedData[] = trim(strip_tags($value));
        }

        return $cleanedData;
    }
}