<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Competition entry + racing-pipe prices (national member vs non-member) and currency.
 * Countries get official ISO 4217 currency code.
 */
class AddCompetitionFeesAndCountryCurrency extends BaseMigration
{
    public function up(): void
    {
        if ($this->hasTable('countries') && !$this->table('countries')->hasColumn('currency')) {
            $countries = $this->table('countries');
            $after = $countries->hasColumn('logo') ? 'logo' : 'phone_prefix';
            $countries
                ->addColumn('currency', 'string', [
                    'limit' => 3,
                    'null' => false,
                    'default' => 'HUF',
                    'after' => $after,
                    'comment' => 'ISO 4217 official currency',
                ])
                ->update();

            $this->seedCountryCurrencies();
        }

        if (!$this->hasTable('competitions')) {
            return;
        }

        $table = $this->table('competitions');
        $after = $table->hasColumn('tobacco_weight') ? 'tobacco_weight' : null;

        $columns = [
            'currency' => [
                'type' => 'string',
                'limit' => 3,
                'null' => false,
                'default' => 'HUF',
                'comment' => 'ISO 4217; default = country official currency',
            ],
            'entry_fee_member' => [
                'type' => 'decimal',
                'precision' => 12,
                'scale' => 2,
                'null' => false,
                'default' => '0.00',
                'comment' => 'Entry fee if national association fee paid this year',
            ],
            'entry_fee_non_member' => [
                'type' => 'decimal',
                'precision' => 12,
                'scale' => 2,
                'null' => false,
                'default' => '0.00',
                'comment' => 'Entry fee if national association fee not paid this year',
            ],
            'racing_pipe_1_price_member' => [
                'type' => 'decimal',
                'precision' => 12,
                'scale' => 2,
                'null' => false,
                'default' => '0.00',
                'comment' => 'Pipe 1 unit price (national member)',
            ],
            'racing_pipe_1_price_non_member' => [
                'type' => 'decimal',
                'precision' => 12,
                'scale' => 2,
                'null' => false,
                'default' => '0.00',
                'comment' => 'Pipe 1 unit price (non-member)',
            ],
            'racing_pipe_2_price_member' => [
                'type' => 'decimal',
                'precision' => 12,
                'scale' => 2,
                'null' => false,
                'default' => '0.00',
                'comment' => 'Pipe 2 unit price (national member)',
            ],
            'racing_pipe_2_price_non_member' => [
                'type' => 'decimal',
                'precision' => 12,
                'scale' => 2,
                'null' => false,
                'default' => '0.00',
                'comment' => 'Pipe 2 unit price (non-member)',
            ],
            'racing_pipe_3_price_member' => [
                'type' => 'decimal',
                'precision' => 12,
                'scale' => 2,
                'null' => false,
                'default' => '0.00',
                'comment' => 'Pipe 3 unit price (national member)',
            ],
            'racing_pipe_3_price_non_member' => [
                'type' => 'decimal',
                'precision' => 12,
                'scale' => 2,
                'null' => false,
                'default' => '0.00',
                'comment' => 'Pipe 3 unit price (non-member)',
            ],
        ];

        foreach ($columns as $name => $opts) {
            if ($table->hasColumn($name)) {
                continue;
            }
            $col = [
                $opts['type'],
                [
                    'null' => $opts['null'],
                    'default' => $opts['default'],
                    'comment' => $opts['comment'],
                ],
            ];
            if (isset($opts['limit'])) {
                $col[1]['limit'] = $opts['limit'];
            }
            if (isset($opts['precision'])) {
                $col[1]['precision'] = $opts['precision'];
                $col[1]['scale'] = $opts['scale'];
            }
            if ($after !== null) {
                $col[1]['after'] = $after;
            }
            $table->addColumn($name, $col[0], $col[1]);
            $after = $name;
        }
        $table->update();
    }

    public function down(): void
    {
        if ($this->hasTable('competitions')) {
            $table = $this->table('competitions');
            foreach ([
                'racing_pipe_3_price_non_member',
                'racing_pipe_3_price_member',
                'racing_pipe_2_price_non_member',
                'racing_pipe_2_price_member',
                'racing_pipe_1_price_non_member',
                'racing_pipe_1_price_member',
                'entry_fee_non_member',
                'entry_fee_member',
                'currency',
            ] as $column) {
                if ($table->hasColumn($column)) {
                    $table->removeColumn($column);
                }
            }
            $table->update();
        }

        if ($this->hasTable('countries') && $this->table('countries')->hasColumn('currency')) {
            $this->table('countries')->removeColumn('currency')->update();
        }
    }

    protected function seedCountryCurrencies(): void
    {
        $map = [
            'HU' => 'HUF',
            'AT' => 'EUR', 'BE' => 'EUR', 'CY' => 'EUR', 'DE' => 'EUR', 'EE' => 'EUR',
            'ES' => 'EUR', 'FI' => 'EUR', 'FR' => 'EUR', 'GR' => 'EUR', 'HR' => 'EUR',
            'IE' => 'EUR', 'IT' => 'EUR', 'LT' => 'EUR', 'LU' => 'EUR', 'LV' => 'EUR',
            'MT' => 'EUR', 'NL' => 'EUR', 'PT' => 'EUR', 'SI' => 'EUR', 'SK' => 'EUR',
            'PL' => 'PLN', 'CZ' => 'CZK', 'RO' => 'RON', 'BG' => 'BGN', 'RS' => 'RSD',
            'UA' => 'UAH', 'GB' => 'GBP', 'CH' => 'CHF', 'US' => 'USD', 'CA' => 'CAD',
            'SE' => 'SEK', 'NO' => 'NOK', 'DK' => 'DKK', 'TR' => 'TRY', 'RU' => 'RUB',
        ];
        foreach ($map as $iso2 => $currency) {
            $this->execute(sprintf(
                "UPDATE countries SET currency = '%s' WHERE iso2 = '%s'",
                $currency,
                $iso2
            ));
        }
    }
}
