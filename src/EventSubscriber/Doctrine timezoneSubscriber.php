<?php

namespace App\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postConnect)]
class DoctrineTimezoneSubscriber
{
    public function postConnect(LoadClassMetadataEventArgs $args): void
    {
        $connection = $args->getConnection();
        
        // Establecer zona horaria de PHP como zona horaria de la conexión
        // Esto asegura que las fechas se guarden en la zona horaria local de PHP
        $timezone = date_default_timezone_get();
        $connection->executeStatement("SET TIME ZONE '$timezone'");
    }
}
