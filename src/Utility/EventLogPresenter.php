<?php
declare(strict_types=1);

namespace App\Utility;

use App\Model\Entity\EventLog;

/**
 * User-facing activity log labels (less technical than raw module/action/field names).
 */
class EventLogPresenter
{
    public static function moduleLabel(string $module): string
    {
        $module = trim($module);
        if ($module === '') {
            return __('Record');
        }

        return static::moduleLabels()[$module] ?? $module;
    }

    public static function actionLabel(string $action): string
    {
        $action = strtolower(trim($action));
        $labels = static::actionLabels();

        return $labels[$action] ?? ucfirst($action);
    }

    public static function fieldLabel(string $module, string $field): string
    {
        $field = trim($field);
        if ($field === '') {
            return '';
        }

        if (str_contains($field, ':')) {
            [$base, $locale] = explode(':', $field, 2);
            $baseLabel = static::fieldLabels()[$base] ?? str_replace('_', ' ', $base);

            return $baseLabel . ' (' . $locale . ')';
        }

        return static::fieldLabels()[$field] ?? str_replace('_', ' ', $field);
    }

    /**
     * One field value for activity log display (FK labels, booleans, …).
     */
    public static function formatFieldValue(string $module, string $field, mixed $value): string
    {
        return EventLogValueResolver::display($module, $field, $value);
    }

    /**
     * @return array<string, string>
     */
    protected static function moduleLabels(): array
    {
        return [
            'Auth' => __('Sign-in'),
            'Users' => __('User account'),
            'Countries' => __('Country'),
            'Clubs' => __('Club'),
            'Setups' => __('Application settings'),
            'Samples' => __('Sample record'),
            'Parents' => __('Parent record'),
            'Cities' => __('City'),
            'Continents' => __('Continent'),
            'EventLogs' => __('Activity log'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function fieldLabels(): array
    {
        return [
            'first_name' => __('Name'),
            'last_name' => __('Last name'),
            'email' => __('Email'),
            'username' => __('Username'),
            'phone' => __('Phone'),
            'country_id' => __('Country'),
            'club_id' => __('Club'),
            'role' => __('Role'),
            'enabled' => __('Account enabled'),
            'active' => __('Account active'),
            'visible' => __('Visible'),
            'pos' => __('Position'),
            'name' => __('Name'),
            'endonim_name' => __('Endonym'),
            'locale' => __('Language code'),
            'timezone' => __('Timezone'),
            'iso2' => __('ISO code'),
            'membership_status' => __('Membership status'),
            'membership_joined_date' => __('Member since'),
            'club_membership_fee_date' => __('Club membership fee'),
            'national_membership_fee_date' => __('National association membership fee'),
            'avatar' => __('Profile picture'),
            'password' => __('Password'),
            'value' => __('Value'),
            'slug' => __('Identifier'),
            'type' => __('Type'),
            'edit_by' => __('Who can edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function actionLabels(): array
    {
        return [
            'login' => __('Signed in'),
            'logout' => __('Signed out'),
            'add' => __('Created'),
            'edit' => __('Updated'),
            'delete' => __('Deleted'),
        ];
    }

    /**
     * One-line summary for activity lists.
     */
    public static function activitySummary(EventLog $log): string
    {
        $action = strtolower(trim((string)($log->action ?? '')));
        $module = trim((string)($log->module ?? ''));
        $moduleLabel = static::moduleLabel($module);

        if ($action === 'login') {
            return (string)static::actionLabel('login');
        }
        if ($action === 'logout') {
            return (string)static::actionLabel('logout');
        }

        $changes = EventLogChanges::fromRequestData($log->request_data ?? null);
        if ($action === 'delete') {
            return __('Deleted {0}', $moduleLabel);
        }
        if ($action === 'add') {
            return __('Created {0}', $moduleLabel);
        }

        if ($changes !== []) {
            $detail = static::friendlyChangeSummary($changes, $module);
            if ($detail !== '') {
                if ($action === 'add') {
                    return __('Created {0}: {1}', $moduleLabel, $detail);
                }

                return $detail;
            }
        }

        $description = trim((string)($log->description ?? ''));
        if ($description !== '') {
            return $description;
        }

        return __('Updated {0}', $moduleLabel);
    }

    /**
     * @param array<string, array{from?: mixed, to?: mixed}> $changes
     */
    public static function friendlyChangeSummary(array $changes, string $module, int $maxLen = 280): string
    {
        if ($changes === []) {
            return '';
        }
        $parts = [];
        foreach ($changes as $field => $pair) {
            $label = static::fieldLabel($module, (string)$field);
            $from = is_array($pair) ? ($pair['from'] ?? null) : null;
            $to = is_array($pair) ? ($pair['to'] ?? null) : $pair;
            if (EventLogger::isSecretField((string)$field)) {
                $parts[] = $label . ': ' . __('changed');
                continue;
            }
            $parts[] = $label . ': '
                . static::formatFieldValue($module, (string)$field, $from)
                . ' → '
                . static::formatFieldValue($module, (string)$field, $to);
        }
        $summary = implode('; ', $parts);
        if (strlen($summary) > $maxLen) {
            return substr($summary, 0, $maxLen - 1) . '…';
        }

        return $summary;
    }
}
