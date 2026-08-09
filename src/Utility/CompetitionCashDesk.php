<?php
declare(strict_types=1);

namespace App\Utility;

use App\Model\Entity\Competition;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Check-in cash desk rows: payments grouped by who collected (fee_paid_by).
 */
class CompetitionCashDesk
{
    use LocatorAwareTrait;

    /**
     * @return array{
     *     groups: list<array{
     *         collector_id: string,
     *         collector_name: string,
     *         rows: list<array{name: string, amount: float, paid_at: mixed}>,
     *         subtotal: float
     *     }>,
     *     total: float
     * }
     */
    public static function paidGroupsForCompetition(Competition $competition): array
    {
        $appsTable = (new static())->getTableLocator()->get('CompetitionsUsers');
        $paidApps = $appsTable->find()
            ->contain(['Users', 'FeeCollectors'])
            ->where([
                'CompetitionsUsers.competition_id' => $competition->id,
                'CompetitionsUsers.status IN' => CompetitionApplication::activeStatuses(),
                'CompetitionsUsers.fee_paid_at IS NOT' => null,
            ])
            ->orderBy([
                'CompetitionsUsers.fee_paid_at' => 'ASC',
                'Users.last_name' => 'ASC',
                'Users.first_name' => 'ASC',
            ])
            ->all();

        $groups = [];
        $total = 0.0;

        foreach ($paidApps as $paidApp) {
            $paidUser = $paidApp->user;
            $paidName = static::userDisplayName($paidUser, (string)$paidApp->user_id);
            $amount = round((float)($paidApp->get('fee_total') ?? 0), 2);
            if ($amount <= 0 && $paidUser !== null) {
                $lines = CompetitionFees::lineItems($competition, $paidApp, $paidUser);
                foreach ($lines as $line) {
                    if (($line['kind'] ?? '') === 'total') {
                        $amount = round((float)$line['amount'], 2);
                        break;
                    }
                }
            }

            $collectorId = trim((string)($paidApp->get('fee_paid_by') ?? ''));
            $collector = $paidApp->get('fee_collector');
            if ($collectorId === '') {
                $collectorId = '_unknown';
                $collectorName = __('Not recorded');
            } else {
                $collectorName = static::userDisplayName($collector, $collectorId);
            }

            if (!isset($groups[$collectorId])) {
                $groups[$collectorId] = [
                    'collector_id' => $collectorId,
                    'collector_name' => $collectorName,
                    'rows' => [],
                    'subtotal' => 0.0,
                ];
            }
            $groups[$collectorId]['rows'][] = [
                'name' => $paidName,
                'amount' => $amount,
                'paid_at' => $paidApp->fee_paid_at,
            ];
            $groups[$collectorId]['subtotal'] += $amount;
            $total += $amount;
        }

        foreach ($groups as &$group) {
            $group['subtotal'] = round((float)$group['subtotal'], 2);
        }
        unset($group);

        uasort($groups, static function (array $a, array $b): int {
            if ($a['collector_id'] === '_unknown') {
                return 1;
            }
            if ($b['collector_id'] === '_unknown') {
                return -1;
            }

            return strnatcasecmp($a['collector_name'], $b['collector_name']);
        });

        return [
            'groups' => array_values($groups),
            'total' => round($total, 2),
        ];
    }

    public static function userDisplayName(mixed $user, string $fallbackId): string
    {
        if ($user !== null) {
            $name = trim(
                ((string)($user->last_name ?? '')) . ' ' . ((string)($user->first_name ?? ''))
            );
            if ($name === '') {
                $name = (string)($user->email ?? '');
            }
            if ($name !== '') {
                return $name;
            }
        }

        return $fallbackId !== '' && $fallbackId !== '_unknown' ? $fallbackId : __('Not recorded');
    }

    /**
     * Label for competition select (name — datetime).
     */
    public static function competitionOptionLabel(Competition $competition): string
    {
        $label = (string)$competition->name;
        $when = $competition->competition_datetime
            ? LocaleDateParser::format($competition->competition_datetime, 'datetime_short')
            : '';
        if ($when !== '') {
            $label .= ' — ' . $when;
        }

        return $label;
    }
}
