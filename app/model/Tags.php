<?php

namespace App;

use Kdyby;
use Nette;

/**
 * Class Tags
 * @package App
 */
class Tags extends Nette\Object {

	/** @var \Kdyby\Doctrine\EntityDao */
	private $dao;

	/**
	 * @param Kdyby\Doctrine\EntityDao $dao
	 */
	public function __construct(Kdyby\Doctrine\EntityDao $dao) {
		$this->dao = $dao;
	}

	/**
	 * @param null $entity
	 * @param null $relations
	 * @return array
	 */
	public function save($entity = NULL, $relations = NULL) {
		return $this->dao->save($entity, $relations);
	}

	/**
	 * @param array $criteria
	 * @param array $orderBy
	 * @param null $limit
	 * @param null $offset
	 * @return array
	 */
	public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null) {
		$qb = $this->dao->createQueryBuilder('t')
			->whereCriteria($criteria)
			->innerJoin('t.posts', 'p')
			->addSelect('p'); // This will produce less SQL queries with prefetch.
		if ($orderBy) {
			foreach ($orderBy as $sort => $order) {
				$qb->addOrderBy('t.' . $sort, $order);
			}
		}
		return $qb->getQuery()
			->setMaxResults($limit)
			->setFirstResult($offset)
			->getResult();
	}

	/**
	 * @param array $criteria
	 * @param array $orderBy
	 * @return mixed|null|object
	 */
	public function findOneBy(array $criteria, array $orderBy = null) {
		return $this->dao->findOneBy($criteria, $orderBy);
	}

	/**
	 * @param array $criteria
	 * @return mixed
	 */
	public function countBy(array $criteria = array()) {
		return $this->dao->countBy($criteria);
	}

	/**
	 * @param $entity
	 * @param null $relations
	 * @param bool $flush
	 */
	public function delete($entity, $relations = NULL, $flush = Kdyby\Persistence\ObjectDao::FLUSH) {
		$this->dao->delete($entity, $relations, $flush);
	}

}