<?php

namespace Leo108\Cas\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Leo108\Cas\Contracts\Models\UserModel;

class User extends Model implements UserModel
{
    protected $fillable = ['first_name', 'last_name', 'email'];

    public function getName(): string
    {
        return $this->email;
    }

    public function getCasAttributes(): array
    {
        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
        ];
    }

    public function getEloquentModel(): Model
    {
        return $this;
    }
}
