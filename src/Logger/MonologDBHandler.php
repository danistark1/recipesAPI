<?php

namespace App\Logger;
use App\Entity\RecipesLoggerEntity;use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use App\Utils\RecipiesDateTime;
use App\Kernel;

class MonologDBHandler extends AbstractProcessingHandler {
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function __construct(EntityManagerInterface $em, $level = Logger::API, $bubble = true) {
        $this->em = $em;
        parent::__construct($level, $bubble);
    }

    /**
     * Called when writing to our database
     *
     * @param array $record
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function write(array $record): void {
        // Check if debugging is enabled.
  //      if (!empty($debug) && $debug == 1) {
            //if( 'doctrine' == $record['channel'] ) {
            // TODO Log level should be configurable
//            if ((int)$record['level'] === Logger::INFO || (int)$record['level'] === Logger::DEBUG) {
//                return;
//            }
        $this->em->getConnection()->beginTransaction();

            try {
                $logEntry = new RecipesLoggerEntity();
                $logEntry->setMessage($record['message']);
                $logEntry->setLevel($record['level']);
                $logEntry->setLevelName($record['level_name']);
                $logDateTime = RecipiesDateTime::dateNow();
                $logEntry->setInsertDateTime($logDateTime);

                if (is_array($record['extra'])) {
                    $logEntry->setExtra($record['extra']);
                } else {
                    $logEntry->setExtra([]);
                }

                if (is_array($record['context'])) {
                    $logEntry->setContext($record['context']);
                } else {
                    $logEntry->setContext([]);
                }
                // clear the manager from any other managed objects.
                $this->em->clear();
                $this->em->persist($logEntry);
                $this->em->flush();
                $this->em->getConnection()->commit();
            } catch (ORMInvalidArgumentException | ORMException $e) {

            }


//        // Ensure the doctrine channel is ignored (unless its greater than a warning error), otherwise you will create an infinite loop, as doctrine like to log.. a lot..
//        if( 'doctrine' == $record['channel'] ) {
//
//            if( (int)$record['level'] >= Logger::WARNING ) {
//                error_log($record['message']);
//            }
//
//            return;
//        }
//        // Only log errors greater than a warning
//        // TODO - you could ideally add this into configuration variable
//        if( (int)$record['level'] >= Logger::NOTICE ) {
//
//            try
//            {
//                // Logs are inserted as separate SQL statements, separate to the current transactions that may exist within the entity manager.
//                $em = $this->_container->get('doctrine')->getManager();
//                $conn = $em->getConnection();
//
//                $created = date('Y-m-d H:i:s');
//
//                $serverData = ""; //$record['extra']['server_data'];
//                $referer = "";
//                if (isset($_SERVER['HTTP_REFERER'])){
//                    $referer= $_SERVER['HTTP_REFERER'];
//                }
//
//                $stmt = $em->getConnection()->prepare('INSERT INTO system_log(log, level, server_data, modified, created)
//                                    VALUES(' . $conn->quote($record['message']) . ', \'' . $record['level'] . '\', ' . $conn->quote($referer) . ', \'' . $created . '\', \'' . $created . '\');');
//                $stmt->execute();
//
//            } catch( \Exception $e ) {
//
//                // Fallback to just writing to php error logs if something really bad happens
//                error_log($record['message']);
//                error_log($e->getMessage());
//            }











































    }

}
