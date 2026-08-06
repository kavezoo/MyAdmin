<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.0.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\View;

use App\Utility\AppBrand;
use Cake\View\View;

/**
 * Application View
 *
 * @link https://book.cakephp.org/5/en/views.html#the-app-view
 */
class AppView extends View
{
    /**
     * @var array<string, mixed>
     */
    protected array $helpers = [
        'Html',
        'Flash',
        'Form',
        'Url',
        'Paginator',
    ];

    /**
     * @return void
     */
    public function initialize(): void
    {
        $this->set('appName', AppBrand::name());
        $this->set('appTitle', AppBrand::title());

        try {
            $this->loadHelper('CakeDC/Users.User');
        } catch (\Throwable) {
            // Plugin unavailable in some CLI contexts.
        }

        if ($this->getRequest()->getParam('prefix') === 'Admin'
            || strcasecmp((string)$this->getLayout(), 'admin') === 0) {
            // Ensure field errors render under the input (red bold in style.css)
            $this->Form->setTemplates([
                'error' => '<div class="error-message" id="{{id}}">{{content}}</div>',
                'errorList' => '<ul class="error-message-list mb-0 ps-3">{{content}}</ul>',
                'errorItem' => '<li>{{text}}</li>',
                'inputContainer' => '{{content}}',
                'inputContainerError' => '{{content}}{{error}}',
                'errorClass' => 'is-invalid',
            ]);
        }
    }

    /**
     * Flash as Simple Notify toast (Admin prefix, admin layout, login layout).
     */
    public function usesFlashToast(): bool
    {
        if (strcasecmp((string)$this->getRequest()->getParam('prefix'), 'Admin') === 0) {
            return true;
        }

        $layout = (string)$this->getLayout();
        if (strcasecmp($layout, 'admin') === 0 || strcasecmp($layout, 'login') === 0) {
            return true;
        }

        return false;
    }
}
