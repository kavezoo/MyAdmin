<?php
declare(strict_types=1);

namespace App\Utility;

/**
 * Ensures App auth templates exist under templates/ (FTP/partial deploys often omit them).
 *
 * Source of truth for runtime fallback copies: resources/auth_templates/
 * Keep in sync with templates/Users/* and templates/layout/login.php.
 */
final class AuthTemplates
{
    /**
     * Copy missing auth templates from resources/auth_templates → templates/.
     */
    public static function ensureDeployed(): void
    {
        $sourceRoot = RESOURCES . 'auth_templates' . DIRECTORY_SEPARATOR;
        if (!is_dir($sourceRoot)) {
            return;
        }

        $map = [
            'Users' => ROOT . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'Users',
            'layout' => ROOT . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'layout',
        ];

        foreach ($map as $subdir => $destDir) {
            $srcDir = $sourceRoot . $subdir;
            if (!is_dir($srcDir)) {
                continue;
            }
            if (!is_dir($destDir) && !@mkdir($destDir, 0755, true) && !is_dir($destDir)) {
                continue;
            }
            foreach (glob($srcDir . DIRECTORY_SEPARATOR . '*.php') ?: [] as $srcFile) {
                $destFile = $destDir . DIRECTORY_SEPARATOR . basename($srcFile);
                if (is_file($destFile)) {
                    continue;
                }
                @copy($srcFile, $destFile);
            }
        }
    }
}
