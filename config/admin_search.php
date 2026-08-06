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
 * 6. `labelsKey`: `entityFieldLabels` kulcs a Search modalhoz (country / setup / …).
 *
 * Az index kereső csak az adott model `fields` mezőiben keres (saját oszlopok).
 * Translate-es mezőknél (Countries `name`, …) a keresés és a
 * rendezés az **UI locale** fordításán fut (`AdminTranslate` / `translationField`),
 * plusz fallback a kanonikus (angol) oszlopra.
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
            'Languages' => [
                'label' => 'Languages',
                'controller' => 'Languages',
                'titleField' => 'name',
                'labelsKey' => 'language',
                'fields' => [
                    'code',
                    'name',
                    'endonim_name',
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
            'EventLogs' => [
                'label' => 'Event logs',
                'controller' => 'EventLogs',
                'titleField' => 'description',
                'labelsKey' => 'event_log',
                'fields' => [
                    'module',
                    'action',
                    'entity',
                    'entity_id',
                    'description',
                    'url',
                    'ip',
                    'actor_role',
                ],
            ],
            /**
             * President prefix CRUD — index `q` search only (not Admin global `/admin/search`).
             */
            'Clubs' => [
                'label' => 'Clubs',
                'controller' => 'Clubs',
                'titleField' => 'name',
                'labelsKey' => 'club',
                'includeInGlobal' => false,
                'fields' => [
                    'name',
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
