<?php
/**
 * Field validation error under the control (Admin).
 *
 * @var \App\View\AppView $this
 * @var string $field Field name (dot path OK, e.g. cities._ids)
 */
$field = $field ?? '';
if ($field === '') {
	return;
}
echo $this->Form->error($field);
