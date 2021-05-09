<?php


namespace App;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Class RecipesPaginator
 *
 * @package App
 */
class RecipesPaginator {

    /** @var array Paginated results */
    private $paginatedResults = [];

    /**
     * RecipesPaginator constructor.
     *
     * @param int $page
     * @param int $pageSize
     * @param QueryBuilder $qb
     */
    public function __construct(int $page, QueryBuilder $qb, int $pageSize = 6) {
        $this->constructPagination($page, $qb, $pageSize);
    }

    /**
     * Construct paginator.
     *
     * @param int $page
     * @param QueryBuilder $qb
     * @param int $pageSize
     */
    private function constructPagination($page, $qb, $pageSize) {
        // load doctrine Paginator
        $paginator = new Paginator($qb);
        // you can get total items
        $totalItems = count($paginator);
        // get total pages
        $pagesCount = ceil($totalItems / $pageSize);
        // now get one page's items:
        $paginator
            ->getQuery()
            ->setFirstResult($pageSize * ($page-1)) // set the offset
            ->setMaxResults($pageSize); // set the limit

        $results = [];
        foreach ($paginator as $pageItem) {
            // do stuff with results...
            $results[] = $pageItem;
        }
        $paginatedResults = [
            'results' => $results,
            'totalItems' => $totalItems,
            'pagesCount' => $pagesCount
        ];
        $this->paginatedResults = $paginatedResults;
    }

    /**
     * Get paginated results.
     *
     * @return array
     */
    public function getPaginatedResult(): array {
        return $this->paginatedResults;
    }
}
