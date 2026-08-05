<?php
declare(strict_types=1);

namespace App\Model\Entity;

use CakeDC\Users\Model\Entity\User as PluginUser;

/**
 * App User — DB `avatar` (uploaded profile picture) + CakeDC social avatar fallback.
 */
class User extends PluginUser
{
    /**
     * Uploaded profile picture path (`users.avatar`), then social account avatar.
     */
    protected function _getAvatar(): ?string
    {
        if (array_key_exists('avatar', $this->_fields)) {
            $stored = $this->_fields['avatar'];
            if ($stored !== null && $stored !== '') {
                return (string)$stored;
            }
        }

        if (isset($this->social_accounts[0])) {
            $socialAvatar = $this->social_accounts[0]['avatar'] ?? null;
            if ($socialAvatar !== null && $socialAvatar !== '') {
                return (string)$socialAvatar;
            }
        }

        return null;
    }

    protected function _setAvatar(?string $avatar): ?string
    {
        if ($avatar === null || $avatar === '') {
            return null;
        }

        return $avatar;
    }
}
