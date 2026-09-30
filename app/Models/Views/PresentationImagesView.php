<?php

namespace App\Models\Views;

use Illuminate\Support\Carbon;
use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $id
 * @property int $presentation_id
 * @property int $team_id
 * @property string $presentation_name
 * @property string $name
 * @property string $file_name
 * @property string|null $mime_type
 * @property int $size
 * @property string $disk
 * @property Carbon|null $created_at
 */
class PresentationImagesView extends ReadOnlyModel
{
    protected $table = 'presentation_images_view';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
