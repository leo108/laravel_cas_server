<?php

/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/15
 * Time: 11:26
 */
class SerializableModel implements Serializable
{
    public function serialize()
    {
        return 'serialized str';
    }

    public function unserialize($serialized)
    {
        // TODO: Implement unserialize() method.
    }
}
