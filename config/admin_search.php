<?php
declare(strict_types=1);

/**
 * Admin szöveges keresés — index (tábla) + globális (header) kereső.
 *
 * Új projekt / új Admin CRUD modul felvételekor (kötelező — doc/uj-projekt.md §2.8):
 * 1. Add hozzá a Table alias-t a `models` tömbhöz.
 * 2. Sorold fel az összes szöveges (string / char / text) oszlopot a `fields` listában.
 * 3. `titleField`: a találatlistában megjelenő fő címke (általában `name`).
 * 4. `label`: msgid a `__()`-hez (angol forrás).
 * 5. `controller`: Admin controller név (URL / redirect).
 * 6. `labelsKey`: `entityFieldLabels` kulcs a Search modalhoz (sample / parent / city / country).
 *
 * Az index kereső csak az adott model `fields` mezőiben keres (saját oszlopok).
 * A header (globális) kereső végigmegy az összes itt felsorolt modelen;
 * `/admin/search`: Google-szerű UI + lapozás (`globalPageLimit` …).
 * Clear search → szűretlen lista + last-visited rekord oldala (AppController).
 *
 * Ne tegyél ide szám / dátum / boolean / FK id mezőket (azok nem „szöveges” keresők).
 * belongsTo dotted mezők (pl. Continents.name): jövőbeli bővítés — egyelőre ne.
 */
return [
    'AdminSearch' => [
        /** URL query param (index + global) */
        'queryParam' => 'q',

        /** Globális találatlista: sor / oldal (`/admin/search`) */
        'globalPageLimit' => 20,

        /** Max. találat / model a globális keresésben (összevonás előtt) */
        'globalLimitPerModel' => 200,

        /** Biztonsági felső korlát az összevont találatlistára */
        'globalMaxResults' => 1000,

        /**
         * @var array<string, array{
         *   label: string,
         *   controller: string,
         *   titleField: string,
         *   labelsKey?: string,
         *   fields: list<string>
         * }>
         */
        'models' => [
            'Samples' => [
                'label' => 'Samples',
                'controller' => 'Samples',
                'titleField' => 'name',
                'labelsKey' => 'sample',
                'fields' => [
                    'name',
                ],
            ],
            'Parents' => [
                'label' => 'Parents',
                'controller' => 'Parents',
                'titleField' => 'name',
                'labelsKey' => 'parent',
                'fields' => [
                    'name',
                ],
            ],
            'Cities' => [
                'label' => 'Cities',
                'controller' => 'Cities',
                'titleField' => 'name',
                'labelsKey' => 'city',
                'fields' => [
                    'name',
                ],
            ],
            'Countries' => [
                'label' => 'Countries',
                'controller' => 'Countries',
                'titleField' => 'name',
                'labelsKey' => 'country',
                'fields' => [
                    'iso2',
                    'name',
                    'locale',
                ],
            ],
            'Setups' => [
                'label' => 'Setups',
                'controller' => 'Setups',
                'titleField' => 'name',
                'labelsKey' => 'setup',
                'fields' => [
                    'name',
                    'slug',
                ],
            ],
            // Continents: nincs külön Admin CRUD index — globális kereséshez később felvehető:
            // 'Continents' => [
            //     'label' => 'Continents',
            //     'controller' => 'Continents',
            //     'titleField' => 'name',
            //     'fields' => ['code', 'name'],
            // ],
        ],
    ],
];
