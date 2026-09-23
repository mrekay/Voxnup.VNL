<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Veritabanı Bağlantısı
 *  Illuminate Capsule'ü .env'deki bağlantıyla kurar.
 *  Authors: Voxnup Authors
 * ============================================================
 */

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

class Database extends \Illuminate\Database\Capsule\Manager
{

    /* ?Container: PHP 8.4 örtük null'lanabilir tipi (Container $c = null)
       E_DEPRECATED ile bildiriyordu. */
    public function __construct(?Container $container = null)
    {
        global $cDefinitions;
        parent::__construct($container);

        $this->addConnection($cDefinitions->getDBInfo());
        $this->setEventDispatcher(new Dispatcher(new Container));
        $this->setAsGlobal();
        $this->bootEloquent();
    }


}
