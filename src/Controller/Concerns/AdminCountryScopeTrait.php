<?php
declare(strict_types=1);

namespace App\Controller\Concerns;

use App\Utility\AdminCountry;
use App\Utility\AdminCountryScope;
use Cake\Datasource\EntityInterface;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;

/**
 * Admin index / CRUD country scoping (own country vs superuser working country).
 *
 * @phpstan-require-extends \App\Controller\Admin\AppController
 */
trait AdminCountryScopeTrait
{
    /**
     * Resolve scope, set view vars, canonicalize ?country_id=, persist working country for superuser.
     *
     * @return array{countryId: int, canChange: bool, options: array<int, string>, label: string}|\Cake\Http\Response
     */
    protected function beginAdminCountryScopedIndex(Table $table, string $field = 'country_id'): array|Response
    {
        $scope = AdminCountryScope::scopeForTable($this->request, $table, $field);
        $this->setAdminCountryScopeViewVars($scope);

        $params = $this->request->getQueryParams();
        $queryId = AdminCountryScope::queryCountryId($this->request);
        $needsRedirect = !array_key_exists('country_id', $params) || $queryId !== $scope['countryId'];

        if ($needsRedirect) {
            $params['country_id'] = (string)$scope['countryId'];
            $params['page'] ??= '1';
            $redirect = $this->redirect(['action' => 'index', '?' => $params]);
            if (
                $redirect instanceof Response
                && $scope['canChange']
                && $scope['countryId'] > 0
            ) {
                return AdminCountry::set($scope['countryId'], $this->request, $redirect);
            }

            return $redirect;
        }

        if ($scope['canChange'] && $scope['countryId'] > 0 && AdminCountry::id($this->request) !== $scope['countryId']) {
            $this->request->getSession()->write(AdminCountry::SESSION_KEY, $scope['countryId']);
        }

        return $scope;
    }

    /**
     * @param array{
     *   countryId: int,
     *   canChange: bool,
     *   options: array<int, string>,
     *   label: string
     * } $scope
     */
    protected function setAdminCountryScopeViewVars(array $scope): void
    {
        $countryId = (int)$scope['countryId'];
        $this->set('filterCountryId', $countryId);
        $this->set('filterCountryLabel', (string)$scope['label']);
        $this->set('workingCountryId', $countryId);
        $this->set('workingCountryLabel', (string)$scope['label']);
        $this->set('countryOptions', $scope['options']);
        $this->set('canChangeCountry', (bool)$scope['canChange']);
    }

    /**
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    protected function applyAdminCountryWhere(
        SelectQuery $query,
        Table $table,
        int $countryId,
        string $field = 'country_id',
    ): SelectQuery {
        if ($countryId < 1) {
            return $query->where([$table->aliasField($field) => 0]);
        }

        return $query->where([$table->aliasField($field) => $countryId]);
    }

    /**
     * Soft-deny when a non-superuser opens a record from another country.
     */
    protected function denyIfOutsideAdminCountryScope(
        EntityInterface $entity,
        string $field = 'country_id',
    ): ?Response {
        if (AdminCountryScope::entityAllowed($entity, $this->request, $field)) {
            return null;
        }

        return $this->denyWithFlashWarning(__('You are not allowed to access records from another country.'));
    }

    /**
     * Force country_id on create/update for non-superuser.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function constrainAdminCountryData(array $data, string $field = 'country_id'): array
    {
        if (!AdminCountryScope::canChangeCountry($this->request)) {
            $own = AdminCountryScope::ownCountryId($this->request);
            $data[$field] = $own > 0 ? $own : 0;

            return $data;
        }

        $id = (int)($data[$field] ?? 0);
        if ($id > 0 && !AdminCountry::isValidCountryId($id)) {
            $data[$field] = AdminCountry::id($this->request);
        }

        return $data;
    }
}
