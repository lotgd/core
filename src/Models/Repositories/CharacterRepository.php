<?php

declare(strict_types = 1);

namespace LotGD\Core\Models\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;

use LotGD\Core\Models\Character;

/**
 * Description of CharacterRepository
 */
class CharacterRepository extends EntityRepository
{
    const SKIP_SOFTDELETED = 0;
    const INCLUDE_SOFTDELETED = 1;
    const ONLY_SOFTDELETED = 2;
    
    protected function modifyQuery(QueryBuilder $queryBuilder, int $level)
    {
        switch ($level) {
            case self::SKIP_SOFTDELETED:
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->isNull('c.deletedAt'),
                        $queryBuilder->expr()->gt('c.deletedAt', 'CURRENT_TIMESTAMP()')
                    )
                );
                break;
            
            case self::ONLY_SOFTDELETED:
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->lte('c.deletedAt', 'CURRENT_TIMESTAMP()')
                );
                break;
        }
    }
    
    public function find($id, int $level = self::SKIP_SOFTDELETED)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select("c")
            ->from(Character::class, "c")
            ->where($queryBuilder->expr()->eq("c.id", ":id"))
            ->setParameter("id", $id);
        
        $this->modifyQuery($queryBuilder, $level);
        
        try {
            return $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }
    
    public function findAll(int $level = self::SKIP_SOFTDELETED)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select("c")
            ->from(Character::class, "c");
        
        $this->modifyQuery($queryBuilder, $level);
        
        return $queryBuilder->getQuery()->getResult();
    }
}
