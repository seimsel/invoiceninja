<?php
/**
 * Invoice Ninja (https://invoiceninja.com)
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2019. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\Models;

use App\Utils\Traits\MakesHash;
use Illuminate\Database\Eloquent\Model;

class Task extends BaseModel
{
    use MakesHash;
    
    protected $fillable = [
		'client_id',
        'invoice_id',
        'custom_value1',
        'custom_value2',
        'description',
        'is_running',
        'time_log',
	];

    protected $appends = ['task_id'];

    public function getRouteKeyName()
    {
        return 'task_id';
    }

    public function getTaskIdAttribute()
    {
        return $this->encodePrimaryKey($this->id);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

}
