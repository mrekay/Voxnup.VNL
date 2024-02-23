<?php
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
class Database extends \Illuminate\Database\Capsule\Manager
{

    public function __construct(Container $container = null)
    {
        global $cDefinitions;
        parent::__construct($container);

        $this->addConnection($cDefinitions->getDBInfo());
        $this->setEventDispatcher(new Dispatcher(new Container));
        $this->setAsGlobal();
        $this->bootEloquent();
    }


}