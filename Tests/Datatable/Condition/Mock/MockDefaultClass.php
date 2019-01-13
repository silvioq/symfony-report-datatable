<?php

namespace  Silvioq\ReportBundle\Tests\Datatable\Condition\Mock;

class MockDefaultClass
{
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
