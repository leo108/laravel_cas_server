<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/25
 * Time: 16:23
 */

namespace Leo108\Cas\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Leo108\Cas\Contracts\Models\UserModel;

/**
 * Class PGTicket
 *
 * @property int          $id
 * @property string       $ticket
 * @property string       $pgt_url
 * @property int          $service_id
 * @property int          $user_id
 * @property list<string> $proxies
 * @property Carbon       $created_at
 * @property Carbon       $expire_at
 * @property UserModel    $user
 * @property Service      $service
 */
class PGTicket extends Model
{
    use TicketTrait;

    protected $table = 'cas_proxy_granting_tickets';

    public $timestamps = false;

    protected $fillable = ['ticket', 'pgt_url', 'proxies', 'expire_at', 'created_at'];

    protected $casts = [
        'expire_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
