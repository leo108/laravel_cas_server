<?php
/**
 * Created by PhpStorm.
 * User: chenyihong
 * Date: 16/8/1
 * Time: 15:17
 */

namespace Leo108\CAS\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ServiceHost
 * @package Leo108\CAS\Models
 *
 * @property integer $service_id
 * @property Service $service
 */
class ServiceHost extends Model
{
    protected $table = 'cas_service_hosts';
    public $timestamps = false;
    protected $fillable = ['host'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
