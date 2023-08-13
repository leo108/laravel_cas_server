<?php
/**
 * Created by PhpStorm.
 * User: chenyihong
 * Date: 16/8/1
 * Time: 14:53
 */

namespace Leo108\Cas\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Leo108\Cas\Contracts\Models\UserModel;

/**
 * Class Ticket
 *
 * @property int          $id
 * @property string       $ticket
 * @property string       $service_url
 * @property int          $service_id
 * @property int          $user_id
 * @property list<string> $proxies
 * @property Carbon       $created_at
 * @property Carbon       $expire_at
 * @property UserModel    $user
 * @property Service      $service
 */
class Ticket extends Model
{
    use TicketTrait;

    protected $table = 'cas_tickets';

    public $timestamps = false;

    protected $fillable = ['ticket', 'service_url', 'proxies', 'expire_at', 'created_at'];

    protected $casts = [
        'expire_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function isProxy(): bool
    {
        return count($this->proxies) > 0;
    }
}
