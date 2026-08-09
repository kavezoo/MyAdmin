<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Lunch price/description on competitions; companion count + lunch fee on applicants;
 * racing pipe photo paths on competitions.
 */
class AddCompetitionLunchAndPipeImages extends BaseMigration
{
    public function up(): void
    {
        if ($this->hasTable('competitions')) {
            $table = $this->table('competitions');
            if (!$table->hasColumn('lunch_description')) {
                $table->addColumn('lunch_description', 'string', [
                    'limit' => 500,
                    'null' => false,
                    'default' => '',
                    'after' => 'entry_fee_non_member',
                    'comment' => 'What lunch is served (Translate EAV also)',
                ]);
            }
            if (!$table->hasColumn('lunch_price')) {
                $table->addColumn('lunch_price', 'decimal', [
                    'precision' => 12,
                    'scale' => 2,
                    'null' => false,
                    'default' => '0.00',
                    'after' => 'lunch_description',
                    'comment' => 'Price per lunch (companions / extra lunches)',
                ]);
            }
            for ($i = 1; $i <= 3; $i++) {
                $col = 'racing_pipe_' . $i . '_image';
                if (!$table->hasColumn($col)) {
                    $after = $i === 1 ? 'racing_pipe_3_price_non_member' : ('racing_pipe_' . ($i - 1) . '_image');
                    $table->addColumn($col, 'string', [
                        'limit' => 255,
                        'null' => false,
                        'default' => '',
                        'after' => $after,
                        'comment' => 'Uploaded photo path for racing pipe ' . $i,
                    ]);
                }
            }
            $table->update();
        }

        if ($this->hasTable('competitions_users')) {
            $cu = $this->table('competitions_users');
            if (!$cu->hasColumn('companion_count')) {
                $cu->addColumn('companion_count', 'integer', [
                    'null' => false,
                    'default' => 0,
                    'after' => 'lunch_for_the_attendant',
                    'comment' => 'Number of companions attending',
                ]);
            }
            if (!$cu->hasColumn('lunch_fee')) {
                $cu->addColumn('lunch_fee', 'decimal', [
                    'precision' => 12,
                    'scale' => 2,
                    'null' => false,
                    'default' => '0.00',
                    'after' => 'racing_pipe_3_fee',
                    'comment' => 'lunch_for_the_attendant × competition.lunch_price',
                ]);
            }
            $cu->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('competitions_users')) {
            $cu = $this->table('competitions_users');
            foreach (['lunch_fee', 'companion_count'] as $col) {
                if ($cu->hasColumn($col)) {
                    $cu->removeColumn($col);
                }
            }
            $cu->update();
        }
        if ($this->hasTable('competitions')) {
            $table = $this->table('competitions');
            foreach ([
                'racing_pipe_3_image',
                'racing_pipe_2_image',
                'racing_pipe_1_image',
                'lunch_price',
                'lunch_description',
            ] as $col) {
                if ($table->hasColumn($col)) {
                    $table->removeColumn($col);
                }
            }
            $table->update();
        }
    }
}
