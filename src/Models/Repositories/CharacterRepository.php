<?php

declare(strict_types=1);

namespace LotGD\Core\Models\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;

use LotGD\Core\Models\Character;

/**
 * Convenience methods to query for characters.
 */
class CharacterRepository extends EntityRepository
{
    const SKIP_SOFTDELETED = 0;
    const INCLUDE_SOFTDELETED = 1;
    const ONLY_SOFTDELETED = 2;

    /**
     * Change the provided query to handle the specified deletion mode.
     */
    protected function modifyQuery(QueryBuilder $queryBuilder, int $deletes)
    {
        switch ($deletes) {
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

    /**
     * Find a character by ID, excluding soft deleted ones.
     * @param mixed $id
     * @param mixed|null $lockMode
     * @param mixed|null $lockVersion
     */
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select("c")
            ->from(Character::class, "c")
            ->where($queryBuilder->expr()->eq("c.id", ":id"))
            ->setParameter("id", $id)
        ;

        $this->modifyQuery($queryBuilder, self::SKIP_SOFTDELETED);

        try {
            return $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * Finds a character id ID, including soft deleted ones.
     * @param $id
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @return mixed|null
     */
    public function findWithSoftDeleted($id)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select("c")
            ->from(Character::class, "c")
            ->where($queryBuilder->expr()->eq("c.id", ":id"))
            ->setParameter("id", $id)
        ;

        $this->modifyQuery($queryBuilder, self::INCLUDE_SOFTDELETED);

        try {
            return $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * Return all characters.
     */
    public function findAll(int $deletes = self::SKIP_SOFTDELETED)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select("c")
            ->from(Character::class, "c")
        ;

        $this->modifyQuery($queryBuilder, $deletes);

        return $queryBuilder->getQuery()->getResult();
    }
}
