<?php
declare(strict_types=1);

namespace App\Controller\Concerns;

use App\Utility\CompetitionPipeImage;
use Cake\Http\Exception\BadRequestException;

/**
 * After competition save: store optional racing_pipe_N_image_file uploads.
 */
trait StoresCompetitionPipeImagesTrait
{
    /**
     * @param \App\Model\Entity\Competition $competition
     */
    protected function storeCompetitionPipeImages($competition): void
    {
        $id = (string)($competition->id ?? '');
        if ($id === '') {
            return;
        }
        $table = $this->fetchTable('Competitions');
        for ($i = 1; $i <= 3; $i++) {
            $file = $this->getRequest()->getUploadedFile('racing_pipe_' . $i . '_image_file');
            if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            try {
                $path = CompetitionPipeImage::store($id, $i, $file);
                $field = CompetitionPipeImage::fieldName($i);
                $competition->set($field, $path);
                $table->save($competition, [
                    'fields' => [$field],
                    'accessibleFields' => [$field => true],
                    'checkRules' => false,
                ]);
            } catch (BadRequestException $e) {
                $this->Flash->warning($e->getMessage());
            } catch (\Throwable) {
                $this->Flash->warning(__('The racing pipe photo could not be saved.'));
            }
        }
    }
}
