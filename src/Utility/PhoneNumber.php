<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * International user phone: + prefix, digits only, optional empty when only country prefix.
 */
class PhoneNumber
{
    use LocatorAwareTrait;

    public static function normalizePrefix(?string $prefix): string
    {
        $digits = preg_replace('/\D/', '', (string)$prefix);

        return $digits !== '' ? '+' . $digits : '';
    }

    public static function prefixForCountryId(int $countryId): string
    {
        if ($countryId < 1) {
            return '';
        }

        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = (new self())->fetchTable('Countries');
        $row = $countries->find()
            ->select(['Countries.phone_prefix', 'Countries.iso2'])
            ->where(['Countries.id' => $countryId])
            ->first();
        if ($row === null) {
            return '';
        }

        $stored = trim((string)$row->get('phone_prefix'));
        if ($stored !== '') {
            return static::normalizePrefix($stored);
        }

        return PhonePrefixMap::prefixForIso2((string)$row->get('iso2'));
    }

    /**
     * @param list<int> $countryIds
     * @return array<int, string>
     */
    public static function prefixMapForCountryIds(array $countryIds): array
    {
        $countryIds = array_values(array_unique(array_filter(
            array_map('intval', $countryIds),
            static fn(int $id): bool => $id > 0,
        )));
        if ($countryIds === []) {
            return [];
        }

        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = (new self())->fetchTable('Countries');
        $rows = $countries->find()
            ->select(['Countries.id', 'Countries.phone_prefix', 'Countries.iso2'])
            ->where(['Countries.id IN' => $countryIds])
            ->all();

        $out = [];
        foreach ($rows as $row) {
            $id = (int)$row->get('id');
            $stored = trim((string)$row->get('phone_prefix'));
            $out[$id] = $stored !== ''
                ? static::normalizePrefix($stored)
                : PhonePrefixMap::prefixForIso2((string)$row->get('iso2'));
        }

        return $out;
    }

    /**
     * DB value: null when empty or only the country calling prefix was entered.
     */
    public static function normalizeForStorage(?string $phone, ?string $countryPrefix): ?string
    {
        $phone = trim((string)$phone);
        if ($phone === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);
        if ($digits === '') {
            return null;
        }

        $normalized = '+' . $digits;
        $prefix = static::normalizePrefix($countryPrefix);
        if ($normalized === '+' || ($prefix !== '' && $normalized === $prefix)) {
            return null;
        }

        if ($prefix !== '' && str_starts_with($normalized, $prefix)) {
            $subscriber = substr($normalized, strlen($prefix));
            if ($subscriber === '') {
                return null;
            }
        }

        return $normalized;
    }

    /**
     * Form input: stored full number, or empty when unset (prefix is placeholder only).
     */
    public static function formatForInput(?string $phone, ?string $defaultPrefix = null): string
    {
        $phone = trim((string)$phone);
        if ($phone === '') {
            return '';
        }

        $digits = preg_replace('/\D/', '', $phone);
        if ($digits === '') {
            return '';
        }

        $normalized = '+' . $digits;
        $prefix = static::normalizePrefix($defaultPrefix);
        if ($prefix !== '' && $normalized === $prefix) {
            return '';
        }

        return $normalized;
    }

    public static function isValidStored(?string $phone): bool
    {
        if ($phone === null || $phone === '') {
            return true;
        }

        return (bool)preg_match('/^\+\d{2,}$/', (string)$phone);
    }
}
