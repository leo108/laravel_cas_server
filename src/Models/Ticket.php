<?php
/**
 * Created by PhpStorm.
 * User: chenyihong
 * Date: 16/8/1
 * Time: 14:53
 */

namespace Leo108\CAS\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Leo108\CAS\Contracts\Models\UserModel;

/**
 * Class Ticket
 * @package Leo108\CAS\Models
 *
 * @property integer   $id
 * @property string    $ticket
 * @property string    $service_url
 * @property integer   $service_id
 * @property integer   $user_id
 * @property array     $proxies
 * @property Carbon    $created_at
 * @property Carbon    $expire_at
 * @property UserModel $user
 * @property Service   $service
 */
class Ticket extends Model
{
    protected $table = 'cas_tickets';
    public $timestamps = false;
    protected $fillable = ['ticket', 'service_url', 'proxies', 'expire_at', 'created_at'];
    protected $casts = [
        'expire_at'  => 'datetime',
        'created_at' => 'datetime',
    ];

    public function getProxiesAttribute()
    {
        return json_decode($this->attributes['proxies'], true);
    }

    public function setProxiesAttribute($value)
    {
        //can not modify an existing record
        if ($this->id) {
            return;
        }
        $this->attributes['proxies'] = json_encode($value);
    }

    public function isExpired()
    {
        return $this->expire_at->getTimestamp() < time();
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function user()
    {
        return $this->belongsTo(config('cas.user_table.model'), 'user_id', config('cas.user_table.id'));
    }

    public function isProxy()
    {
        return !empty($this->proxies);
    }
}
