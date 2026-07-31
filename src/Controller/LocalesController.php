<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Core\Configure;
use Cake\Http\Response;

/**
 * Language / entry redirects.
 */
class LocalesController extends AppController
{
    /**
     * Redirect `/` to `/{lang}/member` using the browser Accept-Language header.
     *
     * @return \Cake\Http\Response
     */
    public function home(): Response
    {
        $languages = Configure::read('App.languages', []);
        $default = (string)Configure::read('App.defaultLanguage', 'hu');
        $lang = isset($languages[$default]) ? $default : (string)array_key_first($languages);

        /** @var list<string> $accepted */
        $accepted = $this->request->acceptLanguage();
        foreach ($accepted as $acceptedLang) {
            $code = strtolower(substr(str_replace('_', '-', $acceptedLang), 0, 2));
            if (isset($languages[$code])) {
                $lang = $code;
                break;
            }
        }

        return $this->redirect([
            'prefix' => 'Member',
            'controller' => 'Dashboard',
            'action' => 'index',
            'lang' => $lang,
        ]);
    }
}
