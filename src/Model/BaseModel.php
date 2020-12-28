<?php

namespace Jcsp\SocialSdk\Model;


class BaseModel implements \JsonSerializable
{

    /**
     * Json 序列化的时候，将私有属性序列化，并且转成下划线的方式
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        $data = [];
        foreach ($this as $key => $val) {
            if ($val !== null) {
                $data[$key] = strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . '_' . "$2", $val));
            }
        }
        return $data;
    }

}