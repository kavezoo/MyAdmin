<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Datasource\EntityInterface;
use function Cake\I18n\__;

/**
 * Flatten entity validation errors for Flash toast and form summaries.
 */
class EntityFormErrors
{
    /**
     * @param array<string, string> $fieldLabels
     * @return list<string>
     */
    public static function labeledLines(EntityInterface $entity, array $fieldLabels = []): array
    {
        $lines = [];
        foreach ($entity->getErrors() as $field => $errors) {
            $label = $fieldLabels[$field] ?? (string)$field;
            foreach (static::flattenMessages($errors) as $message) {
                $lines[] = $label . ': ' . $message;
            }
        }

        return $lines;
    }

    /**
     * @param array<string, string> $fieldLabels
     */
    public static function summaryText(
        EntityInterface $entity,
        array $fieldLabels = [],
        string $fallback = '',
    ): string {
        $lines = static::labeledLines($entity, $fieldLabels);
        if ($lines === []) {
            return $fallback;
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<string, string>
     */
    public static function profileFieldLabels(): array
    {
        return [
            'first_name' => (string)__('Name'),
            'phone' => (string)__('Phone'),
            'country_id' => (string)__('Country'),
            'club_id' => (string)__('Club'),
            'avatar' => (string)__('Profile picture'),
        ];
    }

    /**
     * @param array|string $errors
     * @return list<string>
     */
    protected static function flattenMessages(array|string $errors): array
    {
        if (is_string($errors)) {
            return [$errors];
        }

        $messages = [];
        foreach ($errors as $error) {
            if (is_array($error)) {
                foreach (static::flattenMessages($error) as $nested) {
                    $messages[] = $nested;
                }
                continue;
            }
            $messages[] = (string)$error;
        }

        return $messages;
    }
}
