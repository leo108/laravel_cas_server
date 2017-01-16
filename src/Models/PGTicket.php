<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/25
 * Time: 16:23
 */

namespace Leo108\CAS\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Leo108\CAS\Contracts\Models\UserModel;

/**
 * Class PGTicket
 * @package Leo108\CAS\Models
 *
 * @property integer   $id
 * @property string    $ticket
 * @property string    $pgt_url
 * @property integer   $service_id
 * @property integer   $user_id
 * @property array     $proxies
 * @property Carbon    $created_at
 * @property Carbon    $expire_at
 * @property UserModel $user
 * @property Service   $service
 */
class PGTicket extends Model
{
    protected $table = 'cas_proxy_granting_tickets';
    public $timestamps = false;
    protected $fillable = ['ticket', 'pgt_url', 'proxies', 'expire_at', 'created_at'];
    protected $casts = [
        'expire_at'  => 'datetime',
        'created_at' => 'datetime',
    ];

    public function getProxiesAttribute()
    {
        $ret = json_decode($this->attributes['proxies'], true);
        if (!$ret) {
            return [];
        }

        return $ret;
    }

    public function setProxiesAttribute($value)
    {
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
}
