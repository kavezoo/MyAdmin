<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper\FormHelper as BaseFormHelper;

/**
 * Admin forms use external labels (`label => false` on controls).
 * Required asterisks come from the entity validator via Form context.
 */
class FormHelper extends BaseFormHelper
{
    /**
     * Whether the field is required according to the current form context.
     */
    public function isFieldRequired(string $field): bool
    {
        try {
            return $this->context()->isRequired($field) === true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Red asterisk HTML when the field is required; empty string otherwise.
     */
    public function requiredMark(string $field): string
    {
        if (!$this->isFieldRequired($field)) {
            return '';
        }

        return '<span class="required" title="' . h(__('Required')) . '" aria-hidden="true">*</span>';
    }

    /**
     * External Admin field label with automatic required asterisk.
     *
     * Defaults to horizontal form label classes (`col-form-label`).
     * Pass `'class' => 'form-label'` or `'form-check-label'` when needed.
     * Required mark is a red `*` immediately before the label text (no space).
     *
     * @param array<string, mixed> $options HTML attributes (+ optional `escape`)
     */
    public function adminLabel(string $field, string $text, array $options = []): string
    {
        $escape = $options['escape'] ?? true;
        unset($options['escape']);

        $options += [
            'class' => 'col-sm-3 col-md-2 col-form-label',
        ];

        $body = $this->requiredMark($field) . ($escape ? h($text) : $text);

        return $this->label($field, $body, $options + ['escape' => false]);
    }
}
