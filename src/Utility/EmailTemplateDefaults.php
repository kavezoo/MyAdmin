<?php
declare(strict_types=1);

namespace App\Utility;

/**
 * Default email template bodies for seed / fallback content.
 *
 * Placeholders match MembershipMailer vars: {applicantName}, {clubName}, …
 *
 * @phpstan-type TemplateRow array{name: string, subject: string, body_html: string, body_text: string}
 */
final class EmailTemplateDefaults
{
    /**
     * Locales seeded into `email_templates`.
     *
     * @return list<string>
     */
    public static function locales(): array
    {
        return ['en_GB', 'hu_HU', 'de_DE', 'fr_FR', 'it_IT', 'sk_SK'];
    }

    /**
     * @return array<string, array<string, TemplateRow>> slug => locale => row
     */
    public static function all(): array
    {
        $out = [];
        foreach (EmailTemplateSlugs::options() as $slug => $_label) {
            foreach (static::locales() as $locale) {
                $out[$slug][$locale] = static::forSlugLocale($slug, $locale);
            }
        }

        return $out;
    }

    /**
     * @return TemplateRow
     */
    public static function forSlugLocale(string $slug, string $locale): array
    {
        return match ($slug) {
            EmailTemplateSlugs::MEMBERSHIP_APPLICATION => static::membershipApplication($locale),
            EmailTemplateSlugs::MEMBERSHIP_APPROVED => static::membershipApproved($locale),
            EmailTemplateSlugs::CLUB_NATIONAL_FEE_RECORDED => static::clubNationalFee($locale),
            EmailTemplateSlugs::MEMBER_PROFILE_UPDATED => static::memberProfileUpdated($locale),
            default => [
                'name' => $slug,
                'subject' => $slug,
                'body_html' => '<p></p>',
                'body_text' => '',
            ],
        };
    }

    /**
     * @return TemplateRow
     */
    protected static function membershipApplication(string $locale): array
    {
        $t = static::t($locale);

        $name = $t['Membership application (to club president)'];
        $subject = str_replace('{0}', '{applicantName}', $t['New membership application: {0}']);
        $hello = str_replace('{0}', '{presidentName}', $t['Hello{0},']);
        // Ensure a space before the name placeholder when greeting is "Hello{0},"
        if (!str_contains($hello, ' {presidentName}') && str_contains($hello, '{presidentName}')) {
            $hello = str_replace('{presidentName}', ' {presidentName}', $hello);
        }
        $intro = str_replace('{0}', '{clubName}', $t['A new user has completed their profile and applied for membership in {0}.']);
        $openList = $t['Open members list'];
        $please = $t['Please review the application and approve membership if appropriate.'];

        $bodyHtml = '<p>' . h($hello) . '</p>'
            . '<p>' . h($intro) . '</p>'
            . '<ul>'
            . '<li>' . h($t['Name']) . ': <strong>{applicantName}</strong></li>'
            . '<li>' . h($t['Email']) . ': <strong>{applicantEmail}</strong></li>'
            . '<li>' . h($t['Club']) . ': <strong>{clubName}</strong></li>'
            . '</ul>'
            . '<p><a href="{listUrl}">' . h($openList) . '</a></p>'
            . '<p>' . h($please) . '</p>';

        $bodyText = $hello . "\n\n"
            . $intro . "\n\n"
            . $t['Name'] . ": {applicantName}\n"
            . $t['Email'] . ": {applicantEmail}\n"
            . $t['Club'] . ": {clubName}\n\n"
            . $openList . ": {listUrl}\n\n"
            . $please;

        return [
            'name' => $name,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
        ];
    }

    /**
     * @return TemplateRow
     */
    protected static function membershipApproved(string $locale): array
    {
        $t = static::t($locale);

        $name = $t['Membership approved (to member)'];
        $subject = $t['Your membership has been approved'];
        $hello = str_replace('{0}', '{memberName}', $t['Hello{0},']);
        if (!str_contains($hello, ' {memberName}') && str_contains($hello, '{memberName}')) {
            $hello = str_replace('{memberName}', ' {memberName}', $hello);
        }
        $approved = str_replace('{0}', '{clubName}', $t['The club president has approved your membership in {0}.']);
        $full = $t['You are now a full member and can sign in to the site.'];
        $signIn = $t['Sign in'];

        $bodyHtml = '<p>' . h($hello) . '</p>'
            . '<p>' . h($approved) . '</p>'
            . '<p>' . h($full) . '</p>'
            . '<p><a href="{loginUrl}">' . h($signIn) . '</a></p>';

        $bodyText = $hello . "\n\n"
            . $approved . "\n\n"
            . $full . "\n\n"
            . $signIn . ': {loginUrl}';

        return [
            'name' => $name,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
        ];
    }

    /**
     * @return TemplateRow
     */
    protected static function memberProfileUpdated(string $locale): array
    {
        $t = static::t($locale);

        $name = $t['Member profile updated (to member)'];
        $subject = $t['Your membership profile has been updated'];
        $hello = str_replace('{0}', '{memberName}', $t['Hello{0},']);
        if (!str_contains($hello, ' {memberName}') && str_contains($hello, '{memberName}')) {
            $hello = str_replace('{memberName}', ' {memberName}', $hello);
        }
        $intro = $t['Your membership profile details have been changed by an officer.'];
        $clubLine = str_replace('{0}', '{clubName}', $t['Club: {0}']);
        $signIn = $t['Sign in'];
        $please = $t['If you did not expect this change, please contact your club or national leadership.'];

        $bodyHtml = '<p>' . h($hello) . '</p>'
            . '<p>' . h($intro) . '</p>'
            . '<p>' . h($clubLine) . '</p>'
            . '<p><a href="{loginUrl}">' . h($signIn) . '</a></p>'
            . '<p>' . h($please) . '</p>';

        $bodyText = $hello . "\n\n"
            . $intro . "\n\n"
            . $clubLine . "\n\n"
            . $signIn . ': {loginUrl}' . "\n\n"
            . $please;

        return [
            'name' => $name,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
        ];
    }

    /**
     * @return TemplateRow
     */
    protected static function clubNationalFee(string $locale): array
    {
        $t = static::t($locale);

        $name = $t['Club national fee recorded (to club president)'];
        $subject = str_replace('{0}', '{clubName}', $t['Club annual membership fee recorded for {0}']);
        $hello = str_replace('{0}', '{presidentName}', $t['Hello{0},']);
        if (!str_contains($hello, ' {presidentName}') && str_contains($hello, '{presidentName}')) {
            $hello = str_replace('{presidentName}', ' {presidentName}', $hello);
        }
        $confirm = str_replace('{0}', '{clubName}', $t['We confirm that the annual membership fee payment for club {0} has been received by the leadership and has been booked.']);
        $payment = $t['The payment date is {0}. Your club\'s annual membership fee for {1} is settled, and membership is valid toward {2}.'];
        $payment = str_replace(['{0}', '{1}', '{2}'], ['{paymentDateFormatted}', '{membershipYear}', '{associationName}'], $payment);
        $thanks = $t['Thank you.'];

        $bodyHtml = '<p>' . h($hello) . '</p>'
            . '<p>' . h($confirm) . '</p>'
            . '<p>' . h($payment) . '</p>'
            . '<p>' . h($thanks) . '</p>';

        $bodyText = $hello . "\n\n"
            . $confirm . "\n\n"
            . $payment . "\n\n"
            . $thanks;

        return [
            'name' => $name,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function t(string $locale): array
    {
        static $cache = [];
        if (isset($cache[$locale])) {
            return $cache[$locale];
        }

        $all = static::translations();
        $cache[$locale] = $all[$locale] ?? $all['en_GB'];

        return $cache[$locale];
    }

    /**
     * Escape for HTML body (seed content is trusted static text).
     */
    protected static function h(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected static function translations(): array
    {
        $en = [
            'New membership application: {0}' => 'New membership application: {0}',
            'Your membership has been approved' => 'Your membership has been approved',
            'Club annual membership fee recorded for {0}' => 'Club annual membership fee recorded for {0}',
            'Hello{0},' => 'Hello{0},',
            'A new user has completed their profile and applied for membership in {0}.' => 'A new user has completed their profile and applied for membership in {0}.',
            'Name' => 'Name',
            'Email' => 'Email',
            'Club' => 'Club',
            'Open members list' => 'Open members list',
            'Please review the application and approve membership if appropriate.' => 'Please review the application and approve membership if appropriate.',
            'The club president has approved your membership in {0}.' => 'The club president has approved your membership in {0}.',
            'You are now a full member and can sign in to the site.' => 'You are now a full member and can sign in to the site.',
            'Sign in' => 'Sign in',
            'We confirm that the annual membership fee payment for club {0} has been received by the leadership and has been booked.' => 'We confirm that the annual membership fee payment for club {0} has been received by the leadership and has been booked.',
            'The payment date is {0}. Your club\'s annual membership fee for {1} is settled, and membership is valid toward {2}.' => 'The payment date is {0}. Your club\'s annual membership fee for {1} is settled, and membership is valid toward {2}.',
            'Thank you.' => 'Thank you.',
            'your club' => 'your club',
            'Membership application (to club president)' => 'Membership application (to club president)',
            'Membership approved (to member)' => 'Membership approved (to member)',
            'Club national fee recorded (to club president)' => 'Club national fee recorded (to club president)',
            'Member profile updated (to member)' => 'Member profile updated (to member)',
            'Your membership profile has been updated' => 'Your membership profile has been updated',
            'Your membership profile details have been changed by an officer.' => 'Your membership profile details have been changed by an officer.',
            'Club: {0}' => 'Club: {0}',
            'If you did not expect this change, please contact your club or national leadership.' => 'If you did not expect this change, please contact your club or national leadership.',
        ];

        $hu = [
            'New membership application: {0}' => 'Új tagsági jelentkezés: {0}',
            'Your membership has been approved' => 'A tagságodat engedélyezték',
            'Club annual membership fee recorded for {0}' => 'Klub éves tagdíj rögzítve: {0}',
            'Hello{0},' => 'Helló{0},',
            'A new user has completed their profile and applied for membership in {0}.' => 'Egy új felhasználó kitöltötte a profilját, és tagságot kér a(z) {0} klubba.',
            'Name' => 'Név',
            'Email' => 'E-mail',
            'Club' => 'Klub',
            'Open members list' => 'Taglista megnyitása',
            'Please review the application and approve membership if appropriate.' => 'Nézd át a jelentkezést, és ha megfelelő, engedélyezd a tagságot.',
            'The club president has approved your membership in {0}.' => 'A klubelnök engedélyezte a tagságodat a(z) {0} klubban.',
            'You are now a full member and can sign in to the site.' => 'Mostantól teljes jogú tag vagy, és bejelentkezhetsz az oldalra.',
            'Sign in' => 'Bejelentkezés',
            'We confirm that the annual membership fee payment for club {0} has been received by the leadership and has been booked.' => 'Visszaigazoljuk, hogy a(z) {0} klub éves tagdíjbefizetése megérkezett a vezetőséghez, és lekönyveltük.',
            'The payment date is {0}. Your club\'s annual membership fee for {1} is settled, and membership is valid toward {2}.' => 'A befizetés dátuma: {0}. A klub éves tagdíja {1}-re rendezve van, tagsága érvényes {2} felé.',
            'Thank you.' => 'Köszönjük.',
            'your club' => 'klubod',
            'Membership application (to club president)' => 'Tagság jelentkezés (klubelnöknek)',
            'Membership approved (to member)' => 'Tagság jóváhagyva (tagnak)',
            'Club national fee recorded (to club president)' => 'Klub országos tagdíj rögzítve (klubelnöknek)',
            'Member profile updated (to member)' => 'Tag adatlap módosítva (tagnak)',
            'Your membership profile has been updated' => 'A tagsági adatlapodat frissítették',
            'Your membership profile details have been changed by an officer.' => 'A tagsági adatlapod adatait egy tisztségviselő módosította.',
            'Club: {0}' => 'Klub: {0}',
            'If you did not expect this change, please contact your club or national leadership.' => 'Ha nem számítottál erre a változásra, lépj kapcsolatba a kluboddal vagy az országos vezetőséggel.',
        ];

        $de = [
            'New membership application: {0}' => 'Neue Mitgliedschaftsbewerbung: {0}',
            'Your membership has been approved' => 'Ihre Mitgliedschaft wurde genehmigt',
            'Club annual membership fee recorded for {0}' => 'Jährlicher Clubbeitrag erfasst für {0}',
            'Hello{0},' => 'Hallo{0},',
            'A new user has completed their profile and applied for membership in {0}.' => 'Ein neuer Benutzer hat sein Profil vervollständigt und eine Mitgliedschaft in {0} beantragt.',
            'Name' => 'Name',
            'Email' => 'E-Mail',
            'Club' => 'Club',
            'Open members list' => 'Mitgliederliste öffnen',
            'Please review the application and approve membership if appropriate.' => 'Bitte prüfen Sie die Bewerbung und genehmigen Sie die Mitgliedschaft gegebenenfalls.',
            'The club president has approved your membership in {0}.' => 'Der Clubpräsident hat Ihre Mitgliedschaft in {0} genehmigt.',
            'You are now a full member and can sign in to the site.' => 'Sie sind jetzt vollständiges Mitglied und können sich auf der Website anmelden.',
            'Sign in' => 'Anmelden',
            'We confirm that the annual membership fee payment for club {0} has been received by the leadership and has been booked.' => 'Wir bestätigen, dass die jährliche Mitgliedsbeitragszahlung des Clubs {0} bei der Leitung eingegangen und verbucht wurde.',
            'The payment date is {0}. Your club\'s annual membership fee for {1} is settled, and membership is valid toward {2}.' => 'Das Zahlungsdatum ist {0}. Der jährliche Mitgliedsbeitrag Ihres Clubs für {1} ist beglichen, und die Mitgliedschaft gilt gegenüber {2}.',
            'Thank you.' => 'Vielen Dank.',
            'your club' => 'Ihren Club',
            'Membership application (to club president)' => 'Mitgliedschaftsbewerbung (an Clubpräsident)',
            'Membership approved (to member)' => 'Mitgliedschaft genehmigt (an Mitglied)',
            'Club national fee recorded (to club president)' => 'Nationaler Clubbeitrag erfasst (an Clubpräsident)',
            'Member profile updated (to member)' => 'Mitgliederprofil aktualisiert (an Mitglied)',
            'Your membership profile has been updated' => 'Ihr Mitgliedschaftsprofil wurde aktualisiert',
            'Your membership profile details have been changed by an officer.' => 'Ihre Mitgliedschaftsprofildaten wurden von einem Amtsträger geändert.',
            'Club: {0}' => 'Club: {0}',
            'If you did not expect this change, please contact your club or national leadership.' => 'Wenn Sie diese Änderung nicht erwartet haben, wenden Sie sich bitte an Ihren Club oder die nationale Leitung.',
        ];

        $fr = [
            'New membership application: {0}' => 'Nouvelle demande d’adhésion : {0}',
            'Your membership has been approved' => 'Votre adhésion a été approuvée',
            'Club annual membership fee recorded for {0}' => 'Cotisation annuelle du club enregistrée pour {0}',
            'Hello{0},' => 'Bonjour{0},',
            'A new user has completed their profile and applied for membership in {0}.' => 'Un nouvel utilisateur a complété son profil et a demandé l’adhésion à {0}.',
            'Name' => 'Nom',
            'Email' => 'E-mail',
            'Club' => 'Club',
            'Open members list' => 'Ouvrir la liste des membres',
            'Please review the application and approve membership if appropriate.' => 'Veuillez examiner la candidature et approuver l’adhésion le cas échéant.',
            'The club president has approved your membership in {0}.' => 'Le président de club a approuvé votre adhésion à {0}.',
            'You are now a full member and can sign in to the site.' => 'Vous êtes maintenant membre à part entière et pouvez vous connecter au site.',
            'Sign in' => 'Se connecter',
            'We confirm that the annual membership fee payment for club {0} has been received by the leadership and has been booked.' => 'Nous confirmons que le paiement de la cotisation annuelle du club {0} a été reçu par la direction et a été comptabilisé.',
            'The payment date is {0}. Your club\'s annual membership fee for {1} is settled, and membership is valid toward {2}.' => 'La date de paiement est {0}. La cotisation annuelle de votre club pour {1} est réglée, et l’adhésion est valable envers {2}.',
            'Thank you.' => 'Merci.',
            'your club' => 'votre club',
            'Membership application (to club president)' => 'Demande d’adhésion (au président de club)',
            'Membership approved (to member)' => 'Adhésion approuvée (au membre)',
            'Club national fee recorded (to club president)' => 'Cotisation nationale du club enregistrée (au président de club)',
            'Member profile updated (to member)' => 'Profil membre mis à jour (au membre)',
            'Your membership profile has been updated' => 'Votre profil d’adhésion a été mis à jour',
            'Your membership profile details have been changed by an officer.' => 'Les détails de votre profil d’adhésion ont été modifiés par un responsable.',
            'Club: {0}' => 'Club : {0}',
            'If you did not expect this change, please contact your club or national leadership.' => 'Si vous ne vous attendiez pas à ce changement, veuillez contacter votre club ou la direction nationale.',
        ];

        $it = [
            'New membership application: {0}' => 'Nuova richiesta di iscrizione: {0}',
            'Your membership has been approved' => 'La tua iscrizione è stata approvata',
            'Club annual membership fee recorded for {0}' => 'Quota associativa annuale del club registrata per {0}',
            'Hello{0},' => 'Ciao{0},',
            'A new user has completed their profile and applied for membership in {0}.' => 'Un nuovo utente ha completato il profilo e ha richiesto l’iscrizione a {0}.',
            'Name' => 'Nome',
            'Email' => 'Email',
            'Club' => 'Club',
            'Open members list' => 'Apri elenco membri',
            'Please review the application and approve membership if appropriate.' => 'Esamina la candidatura e approva l’iscrizione se opportuno.',
            'The club president has approved your membership in {0}.' => 'Il presidente del club ha approvato la tua iscrizione a {0}.',
            'You are now a full member and can sign in to the site.' => 'Ora sei un membro a pieno titolo e puoi accedere al sito.',
            'Sign in' => 'Accedi',
            'We confirm that the annual membership fee payment for club {0} has been received by the leadership and has been booked.' => 'Confermiamo che il pagamento della quota associativa annuale del club {0} è stato ricevuto dalla dirigenza ed è stato contabilizzato.',
            'The payment date is {0}. Your club\'s annual membership fee for {1} is settled, and membership is valid toward {2}.' => 'La data di pagamento è {0}. La quota associativa annuale del tuo club per {1} è saldata e l’iscrizione è valida verso {2}.',
            'Thank you.' => 'Grazie.',
            'your club' => 'il tuo club',
            'Membership application (to club president)' => 'Richiesta di iscrizione (al presidente del club)',
            'Membership approved (to member)' => 'Iscrizione approvata (al membro)',
            'Club national fee recorded (to club president)' => 'Quota nazionale del club registrata (al presidente del club)',
            'Member profile updated (to member)' => 'Profilo membro aggiornato (al membro)',
            'Your membership profile has been updated' => 'Il tuo profilo di iscrizione è stato aggiornato',
            'Your membership profile details have been changed by an officer.' => 'I dettagli del tuo profilo di iscrizione sono stati modificati da un responsabile.',
            'Club: {0}' => 'Club: {0}',
            'If you did not expect this change, please contact your club or national leadership.' => 'Se non ti aspettavi questa modifica, contatta il tuo club o la dirigenza nazionale.',
        ];

        $sk = [
            'New membership application: {0}' => 'Nová žiadosť o členstvo: {0}',
            'Your membership has been approved' => 'Vaše členstvo bolo schválené',
            'Club annual membership fee recorded for {0}' => 'Ročný členský poplatok klubu zaznamenaný pre {0}',
            'Hello{0},' => 'Dobrý deň{0},',
            'A new user has completed their profile and applied for membership in {0}.' => 'Nový používateľ doplnil profil a požiadal o členstvo v klube {0}.',
            'Name' => 'Meno',
            'Email' => 'E-mail',
            'Club' => 'Klub',
            'Open members list' => 'Otvoriť zoznam členov',
            'Please review the application and approve membership if appropriate.' => 'Skontrolujte žiadosť a v prípade potreby schváľte členstvo.',
            'The club president has approved your membership in {0}.' => 'Prezident klubu schválil vaše členstvo v klube {0}.',
            'You are now a full member and can sign in to the site.' => 'Teraz ste plnohodnotným členom a môžete sa prihlásiť na stránku.',
            'Sign in' => 'Prihlásiť sa',
            'We confirm that the annual membership fee payment for club {0} has been received by the leadership and has been booked.' => 'Potvrdzujeme, že platba ročného členského poplatku klubu {0} bola vedením prijatá a zaúčtovaná.',
            'The payment date is {0}. Your club\'s annual membership fee for {1} is settled, and membership is valid toward {2}.' => 'Dátum platby je {0}. Ročný členský poplatok vášho klubu za {1} je uhradený a členstvo platí voči {2}.',
            'Thank you.' => 'Ďakujeme.',
            'your club' => 'váš klub',
            'Membership application (to club president)' => 'Žiadosť o členstvo (prezidentovi klubu)',
            'Membership approved (to member)' => 'Členstvo schválené (členovi)',
            'Club national fee recorded (to club president)' => 'Národný poplatok klubu zaznamenaný (prezidentovi klubu)',
            'Member profile updated (to member)' => 'Profil člena aktualizovaný (členovi)',
            'Your membership profile has been updated' => 'Váš členský profil bol aktualizovaný',
            'Your membership profile details have been changed by an officer.' => 'Údaje vášho členského profilu zmenil funkcionár.',
            'Club: {0}' => 'Klub: {0}',
            'If you did not expect this change, please contact your club or national leadership.' => 'Ak ste túto zmenu neočakávali, kontaktujte svoj klub alebo národné vedenie.',
        ];

        return [
            'en_GB' => $en,
            'hu_HU' => $hu,
            'de_DE' => $de,
            'fr_FR' => $fr,
            'it_IT' => $it,
            'sk_SK' => $sk,
        ];
    }
}
