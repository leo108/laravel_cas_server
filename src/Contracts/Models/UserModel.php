<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/9/27
 * Time: 07:26
 */

namespace Leo108\CAS\Contracts\Models;

use Illuminate\Database\Eloquent\Model;

interface UserModel
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return array
     */
    public function getCASAttributes();

    /**
     * @return Model
     */
    public function getEloquentModel();
}
