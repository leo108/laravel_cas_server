<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/9/27
 * Time: 07:26
 */

namespace Leo108\Cas\Contracts\Models;

use Illuminate\Database\Eloquent\Model;

interface UserModel
{
    /**
     * Get user's name (should be unique in whole cas system)
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get user's attributes
     *
     * @return array<string,mixed>
     */
    public function getCasAttributes(): array;

    /**
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getEloquentModel(): Model;
}
