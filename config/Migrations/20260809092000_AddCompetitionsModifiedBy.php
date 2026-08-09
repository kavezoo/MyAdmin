<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * competitions.modified_by — last editor (Users.id UUID); create copies user_id.
 */
class AddCompetitionsModifiedBy extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('competitions')) {
            return;
        }

        $table = $this->table('competitions');
        if (!$table->hasColumn('modified_by')) {
            $table
                ->addColumn('modified_by', 'uuid', [
                    'null' => true,
                    'default' => null,
                    'after' => 'user_id',
                    'comment' => 'Last editor Users.id (create = user_id)',
                ])
                ->addIndex(['modified_by'], ['name' => 'modified_by'])
                ->update();
        }

        $this->execute(
            'UPDATE `competitions` SET `modified_by` = `user_id` WHERE `modified_by` IS NULL OR `modified_by` = \'\''
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('competitions')) {
            return;
        }

        $table = $this->table('competitions');
        if ($table->hasColumn('modified_by')) {
            $table->removeColumn('modified_by')->update();
        }
    }
}
