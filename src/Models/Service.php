<?php
/**
 * Created by PhpStorm.
 * User: chenyihong
 * Date: 16/8/1
 * Time: 15:06
 */

namespace Leo108\CAS\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'cas_services';
    protected $fillable = ['name', 'enabled'];
    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function hosts()
    {
        return $this->hasMany(ServiceHost::class);
    }
}
