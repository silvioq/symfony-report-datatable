<?php

namespace  Silvioq\ReportBundle\Tests\Datatable\Condition\Mock;

use Silvioq\ReportBundle\Annotation\Datatable;

/**
 * @Datatable\ConditionDefinition(column="a.column", filter="column")
 * @Datatable\ConditionDefinition(column="a.column2", filter="column2", type="eq")
 * @Datatable\ConditionDefinition(column="column4", filter="column4", type="eq")
 * @Datatable\ConditionDataMethod("getArrayData");
 */
class MockClass
{
    /**
     * @Datatable\ConditionDefinition(column="a.column3", filter="column3")
     */
    public function filterMe($queryBuilder)
    {
    }

    private $data;
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function getArrayData(): array
    {
        return $this->data;
    }
}
