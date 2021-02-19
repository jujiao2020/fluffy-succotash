<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Client;


class ClientProxy
{

    private $client;

    public function __construct($client)
    {
        $this->client = $client;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \ReflectionException
     * @throws \Exception
     */
    function __call($name, $arguments)
    {
        $ref = new \ReflectionClass($this->client);
        if (!$ref->hasMethod($name)) {
            throw new \ReflectionException("No found method '{$name}''");
        }
        $method = $ref->getMethod($name);
        $flag = $method->isPublic() && !$method->isAbstract() && !$method->isStatic();
        if (!$flag) {
            throw new \ReflectionException("No found method '{$name}'");
        }

        try {
            return $method->invoke($this->client, ...$arguments);
        } catch (\Exception $ex) {
            $errMsg = "发生异常: {$ex}";
            if ($ref->hasMethod('writeLog')) {
                $ref->getMethod('writeLog')->invoke($this->client, "error", $errMsg, $name);
            }
            throw $ex;
        }
    }

}