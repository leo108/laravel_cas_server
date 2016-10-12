<?php
use Illuminate\Database\Eloquent\Model;
use Leo108\CAS\Contracts\Models\UserModel;

/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/9/29
 * Time: 09:56
 */
class User extends Model implements UserModel
{
    protected $table = 'cas_users';
    protected $fillable = ['name'];

    public function getName()
    {
        return $this->name;
    }

    public function getCASAttributes()
    {
        return [
            'real_name' => $this->real_name,
        ];
    }

    public function getEloquentModel()
    {
        return $this;
    }
}
