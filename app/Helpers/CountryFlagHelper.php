<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class CountryFlagHelper
{
    /**
     * Bản đồ ánh xạ tên đội (English/Vietnamese) sang mã ISO-2 (dùng cho blade-country-flags)
     */
    private static array $map = [
        'argentina' => 'ar',
        'australia' => 'au',
        'belgium' => 'be',
        'brazil' => 'br',
        'canada' => 'ca',
        'cameroon' => 'cm',
        'colombia' => 'co',
        'costa rica' => 'cr',
        'croatia' => 'hr',
        'denmark' => 'dk',
        'ecuador' => 'ec',
        'england' => 'gb-eng',
        'france' => 'fr',
        'germany' => 'de',
        'ghana' => 'gh',
        'iran' => 'ir',
        'italy' => 'it',
        'japan' => 'jp',
        'mexico' => 'mx',
        'morocco' => 'ma',
        'netherlands' => 'nl',
        'poland' => 'pl',
        'portugal' => 'pt',
        'qatar' => 'qa',
        'saudi arabia' => 'sa',
        'senegal' => 'sn',
        'serbia' => 'rs',
        'south korea' => 'kr',
        'korea republic' => 'kr',
        'spain' => 'es',
        'switzerland' => 'ch',
        'tunisia' => 'tn',
        'united states' => 'us',
        'usa' => 'us',
        'uruguay' => 'uy',
        'wales' => 'gb-wls',
        'vietnam' => 'vn',
        // TBD WC2026
        'south africa' => 'za',
        'czech republic' => 'cz',
        'bosnia & herzegovina' => 'ba',
        'paraguay' => 'py',
        'haiti' => 'ht',
        'ivory coast' => 'ci',
        'côte d\'ivoire' => 'ci',
        'scotland' => 'gb-sct',
        'turkey' => 'tr',
        'new zealand' => 'nz',
        'norway' => 'no',
        'jordan' => 'jo',
        'panama' => 'pa',
        'curacao' => 'cw',
        'cape verde' => 'cv',
        'egypt' => 'eg',
        'dr congo' => 'cd',
        'algeria' => 'dz',
        'uzbekistan' => 'uz',
        'austria' => 'at',
        'iraq' => 'iq',
        'sweden' => 'se',
    ];

    public static function getIsoCode(?string $teamName): ?string
    {
        if (empty($teamName)) {
            return null;
        }

        $normalized = Str::lower(trim($teamName));

        return self::$map[$normalized] ?? null;
    }

    /**
     * Render cờ + tên đội thành chuỗi HTML an toàn
     */
    public static function renderHtml(?string $teamName): string
    {
        if (empty($teamName)) {
            return '';
        }

        $iso = self::getIsoCode($teamName);

        $fallbackSvg = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-4 inline-block align-middle me-2 rounded-sm shadow-sm object-cover text-gray-400"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>';

        if (! $iso) {
            return $fallbackSvg.'<span class="align-middle">'.e($teamName).'</span>';
        }

        try {
            // Sử dụng helper svg() từ blade-icons (được blade-country-flags dùng dưới nền)
            $svg = svg('flag-4x3-'.$iso, 'w-6 h-4 inline-block align-middle me-2 rounded-sm shadow-sm object-cover')->toHtml();

            return $svg.'<span class="align-middle">'.e($teamName).'</span>';
        } catch (\Exception $e) {
            // Fallback nếu không tìm thấy icon
            return $fallbackSvg.'<span class="align-middle">'.e($teamName).'</span>';
        }
    }
}
