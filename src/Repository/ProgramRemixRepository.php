<?php

namespace App\Repository;

use App\Entity\Extension;
use App\Entity\ProgramRemixRelation;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Common\Persistence\ManagerRegistry;


/**
 * Class ProgramRemixRepository
 * @package App\Repository
 */
class ProgramRemixRepository extends ServiceEntityRepository
{
  /**
   * @param ManagerRegistry $managerRegistry
   */
  public function __construct(ManagerRegistry $managerRegistry)
  {
    parent::__construct($managerRegistry, ProgramRemixRelation::class);
  }

  /**
   * @param int[] $descendant_program_ids
   *
   * @return ProgramRemixRelation[]
   */
  public function getAncestorRelations(array $descendant_program_ids)
  {
    $qb = $this->createQueryBuilder('r');

    return $qb
      ->select('r')
      ->where('r.descendant_id IN (:descendant_ids)')
      ->setParameter('descendant_ids', $descendant_program_ids)
      ->distinct()
      ->getQuery()
      ->getResult();
  }

  /**
   * @param int[] $descendant_program_ids
   *
   * @return int[]
   */
  public function getAncestorIds(array $descendant_program_ids)
  {
    $parents_catrobat_ancestor_relations = $this->getAncestorRelations($descendant_program_ids);

    return array_unique(array_map(function (ProgramRemixRelation $relation) {
      return $relation->getAncestorId();
    }, $parents_catrobat_ancestor_relations));
  }

  /**
   * @param int[] $descendant_program_ids
   *
   * @return ProgramRemixRelation[]
   */
  public function getParentAncestorRelations(array $descendant_program_ids)
  {
    $qb = $this->createQueryBuilder('r');

    return $qb
      ->select('r')
      ->where('r.descendant_id IN (:descendant_ids)')
      ->andWhere($qb->expr()->eq('r.depth', $qb->expr()->literal(1)))
      ->setParameter('descendant_ids', $descendant_program_ids)
      ->distinct()
      ->getQuery()
      ->getResult();
  }

  /**
   * @param int[] $ancestor_program_ids_to_exclude
   * @param int[] $descendant_program_ids
   *
   * @return ProgramRemixRelation[]
   */
  public function getDirectAndIndirectDescendantRelations(
    array $ancestor_program_ids_to_exclude, array $descendant_program_ids
  )
  {
    $qb = $this->createQueryBuilder('r');

    return $qb
      ->select('r')
      ->where('r.ancestor_id NOT IN (:ancestor_program_ids_to_exclude)')
      ->andWhere('r.descendant_id IN (:descendant_program_ids)')
      ->setParameter('ancestor_program_ids_to_exclude', $ancestor_program_ids_to_exclude)
      ->setParameter('descendant_program_ids', $descendant_program_ids)
      ->distinct()
      ->getQuery()
      ->getResult();
  }

  /**
   * @param int[] $ancestor_program_ids_to_exclude
   * @param int[] $descendant_program_ids
   *
   * @return int[]
   */
  public function getDirectAndIndirectDescendantIds(
    array $ancestor_program_ids_to_exclude, array $descendant_program_ids
  )
  {
    $direct_and_indirect_descendant_relations = $this
      ->getDirectAndIndirectDescendantRelations($ancestor_program_ids_to_exclude, $descendant_program_ids);

    return array_unique(array_map(function (ProgramRemixRelation $relation) {
      return $relation->getAncestorId();
    }, $direct_and_indirect_descendant_relations));
  }

  /**
   * @param int[] $program_ids
   *
   * @return int[]
   */
  public function getRootProgramIds(array $program_ids)
  {
    $qb = $this->createQueryBuilder('r');

    $result_data = $qb
      ->select('r.ancestor_id')
      ->innerJoin('App\Entity\Program', 'p', Join::WITH, $qb->expr()->eq('r.ancestor_id', 'p.id'))
      ->where('r.descendant_id IN (:program_ids)')
      ->andWhere($qb->expr()->eq('p.remix_root', $qb->expr()->literal(true)))
      ->setParameter('program_ids', $program_ids)
      ->distinct()
      ->getQuery()
      ->getResult();

    return array_unique(array_map(function ($row) {
      return $row['ancestor_id'];
    }, $result_data));
  }

  /**
   * @param int[] $ancestor_program_ids
   *
   * @return ProgramRemixRelation[]
   */
  public function getDescendantRelations(array $ancestor_program_ids)
  {
    $qb = $this->createQueryBuilder('r');

    return $qb
      ->select('r')
      ->where('r.ancestor_id IN (:ancestor_program_ids)')
      ->setParameter('ancestor_program_ids', $ancestor_program_ids)
      ->distinct()
      ->getQuery()
      ->getResult();
  }

  /**
   * @param int[] $ancestor_program_ids
   *
   * @return int[]
   */
  public function getDescendantIds(array $ancestor_program_ids)
  {
    $catrobat_root_descendant_relations = $this->getDescendantRelations($ancestor_program_ids);

    return array_unique(array_map(function (ProgramRemixRelation $relation) {
      return $relation->getDescendantId();
    }, $catrobat_root_descendant_relations));
  }

  /**
   * @param array $edge_start_program_ids
   * @param array $edge_end_program_ids
   *
   * @return ProgramRemixRelation[]
   */
  public function getDirectEdgeRelationsBetweenProgramIds(array $edge_start_program_ids, array $edge_end_program_ids)
  {
    $qb = $this->createQueryBuilder('r');

    return $qb
      ->select('r')
      ->where('r.ancestor_id IN (:edge_start_program_ids)')
      ->andWhere('r.descendant_id IN (:edge_end_program_ids)')
      ->andWhere($qb->expr()->eq('r.depth', $qb->expr()->literal(1)))
      ->setParameter('edge_start_program_ids', $edge_start_program_ids)
      ->setParameter('edge_end_program_ids', $edge_end_program_ids)
      ->distinct()
      ->getQuery()
      ->getResult();
  }

  /**
   * @param array $ancestor_program_ids
   * @param array $descendant_program_ids
   */
  public function removeRelationsBetweenProgramIds(array $ancestor_program_ids, array $descendant_program_ids)
  {
    $qb = $this->createQueryBuilder('r');

    $qb
      ->delete()
      ->where('r.ancestor_id IN (:ancestor_program_ids)')
      ->andWhere('r.descendant_id IN (:descendant_program_ids)')
      ->setParameter('ancestor_program_ids', $ancestor_program_ids)
      ->setParameter('descendant_program_ids', $descendant_program_ids)
      ->getQuery()
      ->execute();
  }

  /**
   *
   */
  public function removeAllRelations()
  {
    $qb = $this->createQueryBuilder('r');

    $qb
      ->delete()
      ->getQuery()
      ->execute();
  }

  /**
   * @param User $user
   *
   * @return ProgramRemixRelation[]
   */
  public function getUnseenDirectDescendantRelationsOfUser(User $user)
  {
    $qb = $this->createQueryBuilder('r');

    return $qb
      ->select('r')
      ->innerJoin('r.ancestor', 'p', Join::WITH, 'r.ancestor_id = p.id')
      ->innerJoin('r.descendant', 'p2', Join::WITH, 'r.descendant_id = p2.id')
      ->where($qb->expr()->eq('p.user', ':user'))
      ->andWhere($qb->expr()->neq('p2.user', 'p.user'))
      ->andWhere($qb->expr()->eq('r.depth', $qb->expr()->literal(1)))
      ->andWhere($qb->expr()->isNull('r.seen_at'))
      ->orderBy('r.created_at', 'DESC')
      ->setParameter('user', $user)
      ->distinct()
      ->getQuery()
      ->getResult();
  }

  /**
   * @param DateTime $seen_at
   */
  public function markAllUnseenRelationsAsSeen(DateTime $seen_at)
  {
    $qb = $this->createQueryBuilder('r');

    $qb
      ->update()
      ->set('r.seen_at', ':seen_at')
      ->setParameter(':seen_at', $seen_at)
      ->getQuery()
      ->execute();
  }

  /**
   * @param int $program_id
   *
   * @return int
   */
  public function remixCount($program_id)
  {
    $qb = $this->createQueryBuilder('r');

    $result = $qb
      ->select('r')
      ->where($qb->expr()->eq('r.ancestor_id', ':program_id'))
      ->andWhere($qb->expr()->eq('r.depth', $qb->expr()->literal(1)))
      ->setParameter('program_id', $program_id)
      ->distinct()
      ->getQuery()
      ->getResult();

    return count($result);
  }

  /**
   * @param $user_id
   *
   * @return ProgramRemixRelation[]
   */
  public function getDirectParentRelationDataOfUser($user_id)
  {
    $qb = $this->createQueryBuilder('r');

    return $qb
      ->select('r.ancestor_id', 'r.descendant_id')
      ->innerJoin('r.descendant', 'p', Join::WITH, 'r.descendant_id = p.id')
      ->where($qb->expr()->eq('IDENTITY(p.user)', ':user_id'))
      ->andWhere($qb->expr()->eq('r.depth', $qb->expr()->literal(1)))
      ->setParameter('user_id', $user_id)
      ->distinct()
      ->getQuery()
      ->getResult();
  }

  /**
   * @param $user_ids array
   * @param $exclude_user_id
   * @param $exclude_program_ids
   * @param $flavor
   *
   * @return ProgramRemixRelation[]
   */
  public function getDirectParentRelationsOfUsersRemixes($user_ids, $exclude_user_id, $exclude_program_ids, $flavor)
  {
    $qb = $this->createQueryBuilder('r');

    return $qb
      ->select('r')
      ->innerJoin('r.ancestor', 'pa', Join::WITH, 'r.ancestor_id = pa.id')
      ->innerJoin('r.descendant', 'pd', Join::WITH, 'r.descendant_id = pd.id')
      ->where($qb->expr()->in('IDENTITY(pd.user)', ':user_ids'))
      ->andWhere($qb->expr()->neq('IDENTITY(pa.user)', ':exclude_user_id'))
      ->andWhere($qb->expr()->eq('r.depth', $qb->expr()->literal(1)))
      ->andWhere($qb->expr()->notIn('r.ancestor_id', ':exclude_program_ids'))
      ->andWhere($qb->expr()->eq('pa.visible', $qb->expr()->literal(true)))
      ->andWhere($qb->expr()->eq('pa.flavor', ':flavor'))
      ->andWhere($qb->expr()->eq('pa.private', $qb->expr()->literal(false)))
      ->setParameter('user_ids', $user_ids)
      ->setParameter('exclude_user_id', $exclude_user_id)
      ->setParameter('exclude_program_ids', $exclude_program_ids)
      ->setParameter('flavor', $flavor)
      ->distinct()
      ->getQuery()
      ->getResult();
  }
}
