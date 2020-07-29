<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Client;


use Jcsp\SocialSdk\Contract\AuthorizationInterface;
use Jcsp\SocialSdk\Contract\ShareInterface;
use Jcsp\SocialSdk\Contract\UserInterface;

class ClientProxy
{

    /** @var AbstractClient|AuthorizationInterface|UserInterface|ShareInterface */
    private $client;

    public function __construct(AbstractClient $client)
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
            $errMsg = "发生异常: {$ex->getMessage()}\n" . var_export($ex->getTraceAsString(), true);
            $this->client->writeLog("error", $errMsg, $name);
            throw $ex;
        }
    }

}