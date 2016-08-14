<?php
/**
 * Created by PhpStorm.
 * User: chenyihong
 * Date: 16/8/1
 * Time: 15:17
 */

namespace Leo108\CAS\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceHost extends Model
{
    protected $table = 'cas_service_host';
    public $timestamps = false;
    protected $fillable = ['host'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}