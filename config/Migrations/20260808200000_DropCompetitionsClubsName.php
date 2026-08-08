<?php
declare(strict_types=1);

use Cake\Datasource\ConnectionManager;
use Migrations\BaseMigration;

/**
 * Team display name lives on subclubs only — drop competitions_clubs.name.
 */
class DropCompetitionsClubsName extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('competitions_clubs')) {
            return;
        }

        $conn = ConnectionManager::get('default');
        $this->backfillSubclubsFromTeamNames($conn);

        $table = $this->table('competitions_clubs');
        try {
            $table->removeIndexByName('competitions_clubs_comp_club_name')->update();
        } catch (\Throwable $e) {
            // index may already be gone
        }

        if ($table->hasColumn('name')) {
            $table->removeColumn('name')->update();
        }

        $nullLeft = (int)$conn->execute(
            'SELECT COUNT(*) AS c FROM competitions_clubs WHERE subclub_id IS NULL'
        )->fetch('assoc')['c'];

        if ($nullLeft === 0) {
            $this->execute(
                'ALTER TABLE `competitions_clubs`
                 MODIFY `subclub_id` int(10) unsigned NOT NULL
                 COMMENT \'FK → subclubs.id (team display name)\''
            );
        }

        try {
            $this->table('competitions_clubs')
                ->addIndex(
                    ['competition_id', 'club_id', 'subclub_id'],
                    ['unique' => true, 'name' => 'competitions_clubs_comp_club_subclub']
                )
                ->update();
        } catch (\Throwable $e) {
            // index may already exist
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('competitions_clubs')) {
            return;
        }

        $table = $this->table('competitions_clubs');
        try {
            $table->removeIndexByName('competitions_clubs_comp_club_subclub')->update();
        } catch (\Throwable $e) {
            // ignore
        }

        if (!$table->hasColumn('name')) {
            $table->addColumn('name', 'string', [
                'limit' => 250,
                'null' => false,
                'default' => '',
                'after' => 'competition_id',
                'comment' => 'Alcsapat / team display name',
            ])->update();
        }

        $this->execute(
            'UPDATE competitions_clubs cc
             LEFT JOIN subclubs s ON s.id = cc.subclub_id
             SET cc.name = COALESCE(s.name, \'\')'
        );

        $this->execute(
            'ALTER TABLE `competitions_clubs`
             MODIFY `subclub_id` int(10) unsigned NULL DEFAULT NULL'
        );

        try {
            $this->table('competitions_clubs')
                ->addIndex(
                    ['competition_id', 'club_id', 'name'],
                    ['unique' => true, 'name' => 'competitions_clubs_comp_club_name']
                )
                ->update();
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * @param \Cake\Database\Connection $conn
     */
    protected function backfillSubclubsFromTeamNames($conn): void
    {
        $rows = $conn->execute(
            'SELECT cc.id, cc.club_id, cc.competition_id, cc.name, cc.visible, cc.pos, cc.created, cc.modified,
                    c.user_id AS competition_user_id,
                    cl.club_president_id
             FROM competitions_clubs cc
             LEFT JOIN competitions c ON c.id = cc.competition_id
             LEFT JOIN clubs cl ON cl.id = cc.club_id
             WHERE cc.subclub_id IS NULL OR cc.subclub_id = 0'
        )->fetchAll('assoc');

        foreach ($rows as $row) {
            $userId = trim((string)($row['club_president_id'] ?? ''));
            if ($userId === '') {
                $userId = trim((string)($row['competition_user_id'] ?? ''));
            }
            if ($userId === '') {
                $fallback = $conn->execute(
                    'SELECT id FROM users WHERE club_id = :cid ORDER BY modified DESC LIMIT 1',
                    ['cid' => (int)$row['club_id']]
                )->fetch('assoc');
                $userId = trim((string)($fallback['id'] ?? ''));
            }
            if ($userId === '') {
                continue;
            }

            $name = trim((string)($row['name'] ?? ''));
            if ($name === '') {
                $name = 'Team ' . (int)$row['id'];
            }

            $conn->execute(
                'INSERT INTO subclubs (name, club_id, competition_id, user_id, visible, pos, created, modified)
                 VALUES (:name, :club_id, :competition_id, :user_id, :visible, :pos, :created, :modified)',
                [
                    'name' => $name,
                    'club_id' => (int)$row['club_id'],
                    'competition_id' => (string)$row['competition_id'],
                    'user_id' => $userId,
                    'visible' => (int)($row['visible'] ?? 1),
                    'pos' => (int)($row['pos'] ?? 1000),
                    'created' => (string)($row['created'] ?? date('Y-m-d H:i:s')),
                    'modified' => (string)($row['modified'] ?? date('Y-m-d H:i:s')),
                ]
            );

            $subId = (int)$conn->execute('SELECT LAST_INSERT_ID() AS id')->fetch('assoc')['id'];
            if ($subId > 0) {
                $conn->execute(
                    'UPDATE competitions_clubs SET subclub_id = :sid WHERE id = :id',
                    ['sid' => $subId, 'id' => (int)$row['id']]
                );
            }
        }
    }
}
