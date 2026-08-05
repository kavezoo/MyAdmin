<?php
declare(strict_types=1);

namespace App\Controller\Concerns;

use App\Auth\MembershipProfile;
use App\Utility\AdminCountry;
use App\Utility\MembershipFee;
use Cake\Datasource\EntityInterface;
use Cake\Http\Response;

/**
 * Shared member-list index helpers (President / Clubpresident Members).
 */
trait PanelMemberListTrait
{
    /**
     * @param list<string> $sortableFields
     * @return array<string, mixed>
     */
    protected function panelMemberPaginateOptions(array $sortableFields): array
    {
        return [
            'limit' => 50,
            'maxLimit' => 200,
            'sortableFields' => $sortableFields,
        ];
    }

    /**
     * @return list<string>
     */
    protected function panelMemberSortableFields(bool $withClub = false): array
    {
        $fields = [
            'Users.id',
            'Users.first_name',
            'Users.email',
            'Users.active',
            'Users.enabled',
            'Users.' . MembershipFee::FIELD_CLUB,
            'Users.' . MembershipFee::FIELD_NATIONAL,
            'Users.created',
            'Users.modified',
        ];
        if ($withClub) {
            $fields[] = 'Clubs.name';
        }

        return $fields;
    }

    /**
     * @param \Cake\Datasource\EntityInterface $member
     * @return array<string, mixed>
     */
    protected function memberRecordPayload(EntityInterface $member): array
    {
        $clubName = '';
        if ($member->get('club') !== null) {
            $club = $member->get('club');
            if (is_object($club) && method_exists($club, 'get')) {
                $clubName = (string)($club->get('name') ?? '');
            }
        }

        $clubFeeDate = $member->get(MembershipFee::FIELD_CLUB);
        $nationalFeeDate = $member->get(MembershipFee::FIELD_NATIONAL);

        return [
            'id' => $member->get('id'),
            'first_name' => MembershipProfile::displayName($member),
            'email' => (string)($member->get('email') ?? ''),
            'phone' => (string)($member->get('phone') ?? ''),
            'role' => (string)($member->get('role') ?? ''),
            'country' => AdminCountry::label((int)($member->get('country_id') ?? 0)),
            'club' => $clubName,
            'active' => (bool)$member->get('active'),
            'enabled' => (int)($member->get('enabled') ?? 0) === 1,
            MembershipFee::FIELD_CLUB => $clubFeeDate
                ? \App\Utility\LocaleDateParser::format($clubFeeDate, 'date')
                : '',
            MembershipFee::FIELD_NATIONAL => $nationalFeeDate
                ? \App\Utility\LocaleDateParser::format($nationalFeeDate, 'date')
                : '',
            'created' => $member->get('created')
                ? \App\Utility\LocaleDateParser::format($member->get('created'), 'datetime_short')
                : '',
            'modified' => $member->get('modified')
                ? \App\Utility\LocaleDateParser::format($member->get('modified'), 'datetime_short')
                : '',
            'can_delete' => false,
        ];
    }

    /**
     * @param \Cake\Datasource\EntityInterface $club
     * @return array<string, mixed>
     */
    protected function clubRecordPayload(EntityInterface $club): array
    {
        return [
            'id' => $club->get('id'),
            'name' => (string)($club->get('name') ?? ''),
            'country' => AdminCountry::label((int)($club->get('country_id') ?? 0)),
            'enabled' => (int)($club->get('enabled') ?? 0) === 1,
            'visible' => (bool)$club->get('visible'),
            'pos' => \App\Utility\LocaleNumberParser::format($club->get('pos'), decimals: 0),
            'created' => $club->get('created')
                ? \App\Utility\LocaleDateParser::format($club->get('created'), 'datetime_short')
                : '',
            'modified' => $club->get('modified')
                ? \App\Utility\LocaleDateParser::format($club->get('modified'), 'datetime_short')
                : '',
            'can_delete' => false,
        ];
    }

    protected function jsonRecordResponse(array $record): Response
    {
        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'record' => $record,
            ], JSON_UNESCAPED_UNICODE));
    }

    protected function jsonRecordNotFound(): Response
    {
        return $this->response
            ->withStatus(404)
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => false,
                'message' => __('Record not found.'),
            ], JSON_UNESCAPED_UNICODE));
    }
}
