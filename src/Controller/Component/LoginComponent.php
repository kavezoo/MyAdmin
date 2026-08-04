<?php
declare(strict_types=1);

namespace App\Controller\Component;

use Authentication\Authenticator\ResultInterface;
use CakeDC\Users\Controller\Component\LoginComponent as CakeDCLoginComponent;

/**
 * Form login failures: distinct message when `users.enabled` = 0.
 */
class LoginComponent extends CakeDCLoginComponent
{
    /**
     * @inheritDoc
     */
    public function getErrorMessage(?ResultInterface $result = null)
    {
        $email = trim((string)$this->getRequest()->getData('email'));
        if ($email !== '') {
            /** @var \App\Model\Table\UsersTable $users */
            $users = $this->getController()->fetchTable('Users');
            if ($users->isDisabledForLogin($email)) {
                return __('Your account has been disabled. Please contact an administrator.');
            }
        }

        return parent::getErrorMessage($result);
    }
}
