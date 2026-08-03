<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper\PaginatorHelper as BasePaginatorHelper;

/**
 * Admin paginator URLs always keep `page` (including page=1) for bookmarkability
 * and so session restore cannot override an intentional jump to the first page.
 */
class PaginatorHelper extends BasePaginatorHelper
{
    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $url
     * @return array<string, mixed>
     */
    public function generateUrlParams(array $options = [], array $url = []): array
    {
        $forcedPage = array_key_exists('page', $options) ? $options['page'] : null;
        $params = parent::generateUrlParams($options, $url);

        if ($forcedPage === 1 || $forcedPage === '1') {
            $params['?'] ??= [];
            $params['?']['page'] = 1;
        }

        return $params;
    }
}
