<?php
/**
 * Created by PhpStorm.
 * User: chenyihong
 * Date: 16/8/1
 * Time: 15:17
 */

namespace Leo108\Cas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ServiceHost
 *
 * @property int $service_id
 * @property Service $service
 */
class ServiceHost extends Model
{
    protected $table = 'cas_service_hosts';

    public $timestamps = false;

    protected $fillable = ['host'];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
