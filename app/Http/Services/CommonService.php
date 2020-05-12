<?php
/**
 * Created by PhpStorm.
 * User: debu
 * Date: 7/5/19
 * Time: 4:17 PM
 */

namespace App\Http\Services;


class CommonService
{
    public $repository;

    /**
     * CommonService constructor.
     * @param $repository
     */
    function __construct($repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function create($data)
    {
        return $this->repository->create($data);
    }

    /**
     * @param $where
     * @param $data
     * @return mixed
     */
    public function update($where, $data)
    {
        return $this->repository->update($where, $data);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->repository->delete($id);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getById($id)
    {
        return $this->repository->getById($id);
    }

    /**
     * @param array $relation
     * @return mixed
     */
    public function getAll($relation = [])
    {
        return $this->repository->getAll($relation);
    }

    /**
     * @param $where
     * @param array $relation
     * @return mixed
     */
    public function getWhere($where, $relation = [])
    {
        return $this->repository->getWhere($where, $relation);
    }

    /**
     * @param $select
     * @param $where
     * @param array $relation
     * @param int $paginate
     * @return mixed
     */
    public function selectWhere($select, $where, $relation = [], $paginate = 0)
    {
        return $this->repository->selectWhere($select, $where, $relation, $paginate);
    }
}
