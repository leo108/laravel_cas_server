<?php
/**
 * Created by PhpStorm.
 * User: chenyihong
 * Date: 16/8/1
 * Time: 15:06
 */

namespace Leo108\Cas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Service
 *
 * @property string  $name
 * @property bool $allow_proxy
 * @property bool $enabled
 */
class Service extends Model
{
    protected $table = 'cas_services';

    protected $fillable = ['name', 'enabled', 'allow_proxy'];

    protected $casts = [
        'enabled' => 'boolean',
        'allow_proxy' => 'boolean',
    ];

    public function hosts(): HasMany
    {
        return $this->hasMany(ServiceHost::class);
    }
}
