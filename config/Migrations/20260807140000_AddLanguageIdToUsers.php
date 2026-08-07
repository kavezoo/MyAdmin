<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Preferred UI / email language for the user (FK → languages.id).
 * Placed after club_id among the *_id columns.
 */
class AddLanguageIdToUsers extends BaseMigration
{
    public function change(): void
    {
        $this->table('users')
            ->addColumn('language_id', 'integer', [
                'default' => null,
                'null' => true,
                'signed' => true,
                'after' => 'club_id',
                'comment' => 'Preferred language (languages.id); email + UI preference',
            ])
            ->addIndex(['language_id'])
            ->update();
    }
}
