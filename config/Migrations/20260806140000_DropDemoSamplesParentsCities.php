<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Remove demo CRUD tables (Samples / Parents / Cities) — UI minták, nem domain.
 */
class DropDemoSamplesParentsCities extends BaseMigration
{
    public function up(): void
    {
        $this->table('cities_samples')->drop()->save();
        $this->table('samples')->drop()->save();
        $this->table('cities')->drop()->save();
        $this->table('parents')->drop()->save();

        $this->execute(
            "DELETE FROM i18n WHERE model IN ('Samples', 'Parents', 'Cities')"
        );
    }

    public function down(): void
    {
        // Demó táblák nem kerülnek vissza — a minta a doc/minta-tanulsagok.md-ben marad.
    }
}
