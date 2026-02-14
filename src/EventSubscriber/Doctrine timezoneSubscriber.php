<?php

namespace App\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;

#[AsDoctrineListener(event: 'postConnect')]
class DoctrineTimezoneSubscriber
{
    /**
     * Establece la zona horaria de la conexión a la base de datos
     * según la zona horaria configurada en PHP
     *
     * @param mixed $args Doctrine\DBAL\Event\ConnectionEventArgs
     */
    public function postConnect($args): void
    {
        $connection = $args->getConnection();
        
        // Establecer zona horaria de PHP como zona horaria de la conexión
        // Esto asegura que las fechas se guarden en la zona horaria local de PHP
        $timezone = date_default_timezone_get();
        $connection->executeStatement("SET TIME ZONE '$timezone'");
    }
}
