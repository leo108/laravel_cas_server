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

class Ticket extends Model
{
    protected $table = 'cas_ticket';
    public $timestamps = false;
    protected $fillable = ['ticket', 'service_url', 'expire_at', 'created_at'];

    public function isExpired()
    {
        return (new Carbon($this->expire_at))->getTimestamp() < time();
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
