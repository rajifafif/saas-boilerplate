<?php

namespace App\Helpers;

class Helper {
    public static function paginatedResource($paginated, $resourceCollection)
    {
        $response = [
            'current_page' => $paginated->currentPage(),
            'data' => $resourceCollection,
            'first_page_url' => $paginated->url(1),
            'from' => $paginated->firstItem(),
            'last_page' => $paginated->lastPage(),
            'last_page_url' => $paginated->url($paginated->lastPage()),
            'links' => [
                [
                    'url' => $paginated->previousPageUrl(),
                    'label' => '&laquo; Previous',
                    'active' => false,
                ],
                [
                    'url' => $paginated->url(1),
                    'label' => '1',
                    'active' => $paginated->currentPage() === 1,
                ],
                [
                    'url' => $paginated->nextPageUrl(),
                    'label' => 'Next &raquo;',
                    'active' => false,
                ],
            ],
            'next_page_url' => $paginated->nextPageUrl(),
            'path' => $paginated->path(),
            'per_page' => $paginated->perPage(),
            'prev_page_url' => $paginated->previousPageUrl(),
            'to' => $paginated->lastItem(),
            'total' => $paginated->total(),
        ];


        return $response;
    }

    public static function safeTrim($value, $isTime = false)
    {
        if (is_string($value)) {
            return trim($value);
        }

        if ($value instanceof \DateTimeInterface) {
            return $isTime ? $value->format('H:i') : $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        return '';
    }

    public static function getDaysOfWeekText($daysOfWeek)
    {
        $dayNames = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu'
        ];

        $daysOfWeekText = collect($daysOfWeek)->map(function($item) use ($dayNames) {
            return $dayNames[$item];
        });

        return $daysOfWeekText;
    }

}
