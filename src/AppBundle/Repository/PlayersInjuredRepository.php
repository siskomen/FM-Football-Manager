<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Players;
use AppBundle\Entity\PlayersInjured;
use Doctrine\ORM\EntityManagerInterface;

/**
 * PlayersInjuredRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PlayersInjuredRepository extends \Doctrine\ORM\EntityRepository
{

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, new \Doctrine\ORM\Mapping\ClassMetadata(PlayersInjured::class));
    }


    public function save(PlayersInjured $playersInjured){
        $this->_em->persist($playersInjured);
        $this->_em->flush();
    }



}
