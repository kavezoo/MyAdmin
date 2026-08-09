<?php
declare(strict_types=1);

namespace App\Utility;

use App\Model\Entity\Competition;
use Cake\Datasource\EntityInterface;

/**
 * Competition entry fee + racing-pipe unit prices.
 *
 * “Member” = user paid national pipe association fee for the current year
 * (`users.national_membership_fee_date`). Otherwise non-member (higher) rates apply.
 */
class CompetitionFees
{
    /**
     * @return list<string>
     */
    public static function moneyFields(): array
    {
        return [
            'entry_fee_member',
            'entry_fee_non_member',
            'lunch_price',
            'racing_pipe_1_price_member',
            'racing_pipe_1_price_non_member',
            'racing_pipe_2_price_member',
            'racing_pipe_2_price_non_member',
            'racing_pipe_3_price_member',
            'racing_pipe_3_price_non_member',
        ];
    }

    public static function currency(Competition|EntityInterface $competition): string
    {
        return CountryCurrency::normalize(
            $competition->get('currency') ?? '',
            (int)($competition->get('country_id') ?? 0),
        );
    }

    /**
     * True when the applicant paid national association fee for the target year.
     */
    public static function isNationalMember(mixed $user, ?int $year = null): bool
    {
        $year ??= MembershipFee::currentYear();
        $feeDate = null;
        if (is_object($user) && method_exists($user, 'get')) {
            $feeDate = $user->get(MembershipFee::FIELD_NATIONAL);
        } elseif (is_array($user)) {
            $feeDate = $user[MembershipFee::FIELD_NATIONAL] ?? null;
        }

        return MembershipFee::isPaidForYear($feeDate, $year);
    }

    public static function entryFee(Competition|EntityInterface $competition, mixed $user, ?int $year = null): float
    {
        $member = static::isNationalMember($user, $year);
        $field = $member ? 'entry_fee_member' : 'entry_fee_non_member';

        return static::moneyAmount($competition->get($field));
    }

    /**
     * Unit price for racing pipe slot 1–3 (0 if invalid index).
     * Title is not required for pricing (Translate may be empty in some locales).
     */
    public static function pipeUnitPrice(
        Competition|EntityInterface $competition,
        int $index,
        mixed $user,
        ?int $year = null,
    ): float {
        if ($index < 1 || $index > 3) {
            return 0.0;
        }
        $member = static::isNationalMember($user, $year);
        $field = 'racing_pipe_' . $index . '_price_' . ($member ? 'member' : 'non_member');

        return static::moneyAmount($competition->get($field));
    }

    /**
     * Pipe label for UI (Translate title, or fallback).
     */
    public static function pipeTitle(Competition|EntityInterface $competition, int $index): string
    {
        if ($index < 1 || $index > 3) {
            return '';
        }
        $title = trim((string)($competition->get('racing_pipe_' . $index . '_title') ?? ''));
        if ($title !== '') {
            return $title;
        }

        return __('Racing pipe {0}', (string)$index);
    }

    /**
     * Fee snapshot fields for competitions_users.
     *
     * @return list<string>
     */
    public static function dueAmountFields(): array
    {
        return [
            'entry_fee_amount',
            'racing_pipe_1_fee',
            'racing_pipe_2_fee',
            'racing_pipe_3_fee',
            'lunch_fee',
            'fee_total',
        ];
    }

    /**
     * Lunch unit price from competition.
     */
    public static function lunchUnitPrice(Competition|EntityInterface $competition): float
    {
        return static::moneyAmount($competition->get('lunch_price'));
    }

    /**
     * Extra lunches requested on the application (companions / attendants).
     */
    public static function lunchQty(array|EntityInterface|null $source): int
    {
        if ($source === null) {
            return 0;
        }
        if ($source instanceof EntityInterface) {
            return max(0, (int)($source->get('lunch_for_the_attendant') ?? 0));
        }

        return max(0, (int)($source['lunch_for_the_attendant'] ?? 0));
    }

    /**
     * Calculate amounts from competition rates + applicant pipe quantities + lunches.
     *
     * @param array<string, mixed>|EntityInterface|null $qtysSource Application entity/array
     * @return array{
     *     entry_fee_amount: float,
     *     racing_pipe_1_fee: float,
     *     racing_pipe_2_fee: float,
     *     racing_pipe_3_fee: float,
     *     lunch_fee: float,
     *     fee_total: float
     * }
     */
    public static function calculateDue(
        Competition|EntityInterface $competition,
        mixed $user,
        array|EntityInterface|null $qtysSource = null,
        ?int $year = null,
    ): array {
        $entry = static::entryFee($competition, $user, $year);
        $pipeFees = [];
        $total = $entry;
        for ($i = 1; $i <= 3; $i++) {
            $qty = static::qtyFrom($qtysSource, $i);
            $unit = static::pipeUnitPrice($competition, $i, $user, $year);
            $line = $qty > 0 ? round($qty * $unit, 2) : 0.0;
            $pipeFees[$i] = $line;
            $total += $line;
        }
        $lunchQty = static::lunchQty($qtysSource);
        $lunchUnit = static::lunchUnitPrice($competition);
        $lunchFee = $lunchQty > 0 ? round($lunchQty * $lunchUnit, 2) : 0.0;
        $total += $lunchFee;

        return [
            'entry_fee_amount' => $entry,
            'racing_pipe_1_fee' => $pipeFees[1],
            'racing_pipe_2_fee' => $pipeFees[2],
            'racing_pipe_3_fee' => $pipeFees[3],
            'lunch_fee' => $lunchFee,
            'fee_total' => round($total, 2),
        ];
    }

    /**
     * Write due amounts onto the entity (does not save).
     * Skips overwrite when already paid unless $force.
     */
    public static function applyDueToEntity(
        EntityInterface $application,
        Competition|EntityInterface $competition,
        mixed $user,
        bool $force = false,
        ?int $year = null,
    ): void {
        if (!$force && $application->get('fee_paid_at')) {
            return;
        }
        foreach (static::calculateDue($competition, $user, $application, $year) as $field => $value) {
            $application->set($field, $value);
        }
    }

    /**
     * Persist due amounts (unpaid applications only, unless $force).
     */
    public static function syncDueAmounts(
        \Cake\ORM\Table $competitionsUsers,
        Competition|EntityInterface $competition,
        EntityInterface $application,
        mixed $user,
        bool $force = false,
    ): bool {
        if (!$force && $application->get('fee_paid_at')) {
            return static::healPaidMissingLunchFee($competitionsUsers, $competition, $application, $user);
        }
        static::applyDueToEntity($application, $competition, $user, $force);
        $fields = static::dueAmountFields();

        return (bool)$competitionsUsers->save($application, [
            'fields' => $fields,
            'accessibleFields' => array_fill_keys($fields, true),
            'checkRules' => false,
        ]);
    }

    /**
     * Paid snapshot missing lunch while qty + competition lunch_price exist → patch lunch_fee + fee_total.
     */
    public static function healPaidMissingLunchFee(
        \Cake\ORM\Table $competitionsUsers,
        Competition|EntityInterface $competition,
        EntityInterface $application,
        mixed $user = null,
    ): bool {
        if (!$application->get('fee_paid_at')) {
            return true;
        }
        $qty = static::lunchQty($application);
        $unit = static::lunchUnitPrice($competition);
        $storedLunch = static::moneyAmount($application->get('lunch_fee'));
        if ($qty < 1 || $unit <= 0 || $storedLunch > 0) {
            return true;
        }

        $lunchFee = round($qty * $unit, 2);
        $oldTotal = static::moneyAmount($application->get('fee_total'));
        if ($oldTotal <= 0 && $user !== null) {
            $due = static::calculateDue($competition, $user, $application);
            $application->set('lunch_fee', $due['lunch_fee']);
            $application->set('fee_total', $due['fee_total']);
            foreach (['entry_fee_amount', 'racing_pipe_1_fee', 'racing_pipe_2_fee', 'racing_pipe_3_fee'] as $field) {
                if (static::moneyAmount($application->get($field)) <= 0 && isset($due[$field])) {
                    $application->set($field, $due[$field]);
                }
            }
        } else {
            $application->set('lunch_fee', $lunchFee);
            $application->set('fee_total', round($oldTotal + $lunchFee, 2));
        }

        $fields = ['lunch_fee', 'fee_total'];

        return (bool)$competitionsUsers->save($application, [
            'fields' => $fields,
            'accessibleFields' => array_fill_keys($fields, true),
            'checkRules' => false,
        ]);
    }

    /**
     * Display lines: entry fee + each requested pipe (unit × qty = line) + total.
     *
     * Unpaid: always live calculation from competition rates × quantities.
     * Paid: use stored snapshot (unit shown as amount/qty when possible).
     *
     * @return list<array{label: string, amount: float, unit: float, kind: string, qty: int}>
     */
    public static function lineItems(
        Competition|EntityInterface $competition,
        EntityInterface $application,
        mixed $user,
        ?int $year = null,
    ): array {
        $paid = $application->get('fee_paid_at') !== null && $application->get('fee_paid_at') !== '';

        $lines = [];
        if ($paid) {
            $entry = static::moneyAmount($application->get('entry_fee_amount'));
            $lines[] = [
                'label' => __('Entry fee'),
                'amount' => $entry,
                'unit' => $entry,
                'kind' => 'entry',
                'qty' => 1,
            ];
            $pipeSum = 0.0;
            for ($i = 1; $i <= 3; $i++) {
                $qty = static::qtyFrom($application, $i);
                if ($qty < 1) {
                    continue;
                }
                $line = static::moneyAmount($application->get('racing_pipe_' . $i . '_fee'));
                $pipeSum += $line;
                $unit = $qty > 0 ? round($line / $qty, 2) : 0.0;
                $lines[] = [
                    'label' => static::pipeTitle($competition, $i),
                    'amount' => $line,
                    'unit' => $unit,
                    'kind' => 'pipe',
                    'qty' => $qty,
                ];
            }
            $lunchQty = static::lunchQty($application);
            $lunchFee = static::moneyAmount($application->get('lunch_fee'));
            $liveLunchUnit = static::lunchUnitPrice($competition);
            // Paid before lunch pricing existed / lunch added later → heal display from live rate.
            if ($lunchQty > 0 && $lunchFee <= 0 && $liveLunchUnit > 0) {
                $lunchFee = round($lunchQty * $liveLunchUnit, 2);
            }
            if ($lunchQty > 0 || $lunchFee > 0) {
                $lunchUnit = $lunchQty > 0
                    ? round($lunchFee / max(1, $lunchQty), 2)
                    : $liveLunchUnit;
                $lunchLabel = trim((string)($competition->get('lunch_description') ?? ''));
                if ($lunchLabel === '') {
                    $lunchLabel = __('Lunch');
                }
                $lines[] = [
                    'label' => $lunchLabel,
                    'amount' => $lunchFee,
                    'unit' => $lunchUnit,
                    'kind' => 'lunch',
                    'qty' => max(1, $lunchQty),
                ];
                $pipeSum += $lunchFee;
            }
            $total = static::moneyAmount($application->get('fee_total'));
            $expected = round($entry + $pipeSum, 2);
            // Prefer reconstructed total when snapshot omitted lunch (or other lines).
            if ($total <= 0 || ($lunchFee > 0 && $total + 0.001 < $expected)) {
                $total = $expected;
            }
            $lines[] = [
                'label' => __('Total'),
                'amount' => $total,
                'unit' => 0.0,
                'kind' => 'total',
                'qty' => 0,
            ];

            return $lines;
        }

        // Live: entry + qty × unit for each requested pipe.
        $entry = static::entryFee($competition, $user, $year);
        $lines[] = [
            'label' => __('Entry fee'),
            'amount' => $entry,
            'unit' => $entry,
            'kind' => 'entry',
            'qty' => 1,
        ];
        $total = $entry;
        for ($i = 1; $i <= 3; $i++) {
            $qty = static::qtyFrom($application, $i);
            if ($qty < 1) {
                continue;
            }
            $unit = static::pipeUnitPrice($competition, $i, $user, $year);
            $line = round($qty * $unit, 2);
            $total += $line;
            $lines[] = [
                'label' => static::pipeTitle($competition, $i),
                'amount' => $line,
                'unit' => $unit,
                'kind' => 'pipe',
                'qty' => $qty,
            ];
        }
        $lunchQty = static::lunchQty($application);
        $lunchUnit = static::lunchUnitPrice($competition);
        if ($lunchQty > 0) {
            $lunchLine = round($lunchQty * $lunchUnit, 2);
            $total += $lunchLine;
            $lunchLabel = trim((string)($competition->get('lunch_description') ?? ''));
            if ($lunchLabel === '') {
                $lunchLabel = __('Lunch');
            }
            $lines[] = [
                'label' => $lunchLabel,
                'amount' => $lunchLine,
                'unit' => $lunchUnit,
                'kind' => 'lunch',
                'qty' => $lunchQty,
            ];
        }
        $lines[] = [
            'label' => __('Total'),
            'amount' => round($total, 2),
            'unit' => 0.0,
            'kind' => 'total',
            'qty' => 0,
        ];

        return $lines;
    }

    protected static function qtyFrom(array|EntityInterface|null $source, int $index): int
    {
        if ($source === null) {
            return 0;
        }
        $key = 'racing_pipe_' . $index . '_qty';
        if ($source instanceof EntityInterface) {
            return max(0, (int)($source->get($key) ?? 0));
        }

        return max(0, (int)($source[$key] ?? 0));
    }

    public static function format(
        mixed $amount,
        Competition|EntityInterface|string $competitionOrCurrency,
        ?string $locale = null,
    ): string {
        $currency = is_string($competitionOrCurrency)
            ? CountryCurrency::normalize($competitionOrCurrency)
            : static::currency($competitionOrCurrency);

        return LocaleNumberParser::formatCurrency($amount, $locale, $currency, 0);
    }

    /**
     * Placeholder / view strings for both rate tiers.
     *
     * @return array<string, string>
     */
    public static function displayVars(Competition|EntityInterface $competition, ?string $locale = null): array
    {
        $currency = static::currency($competition);
        $fmt = static function (mixed $v) use ($competition, $locale): string {
            $n = static::moneyAmount($v);
            if ($n <= 0) {
                return '';
            }

            return static::format($n, $competition, $locale);
        };

        $vars = [
            'currency' => $currency,
            'entry_fee_member' => $fmt($competition->get('entry_fee_member')),
            'entry_fee_non_member' => $fmt($competition->get('entry_fee_non_member')),
            'lunch_price' => $fmt($competition->get('lunch_price')),
            'lunch_description' => (string)($competition->get('lunch_description') ?? ''),
        ];
        for ($i = 1; $i <= 3; $i++) {
            $vars['racing_pipe_' . $i . '_price_member'] = $fmt($competition->get('racing_pipe_' . $i . '_price_member'));
            $vars['racing_pipe_' . $i . '_price_non_member'] = $fmt($competition->get('racing_pipe_' . $i . '_price_non_member'));
            $title = trim((string)($competition->get('racing_pipe_' . $i . '_title') ?? ''));
            $stored = (string)($competition->get('racing_pipe_' . $i . '_image') ?? '');
            $vars['racing_pipe_' . $i . '_image_url'] = CompetitionPipeImage::publicUrl($stored);
            $vars['racing_pipe_' . $i . '_image'] = CompetitionPipeImage::imgHtml(
                $stored,
                $title !== '' ? $title : __('Racing pipe {0}', $i)
            );
        }

        return $vars;
    }

    protected static function moneyAmount(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        if (!is_numeric($value)) {
            return 0.0;
        }

        return round((float)$value, 2);
    }
}
