<?php

namespace  Silvioq\ReportBundle\Table;

interface DefinitionLoaderInterface
{
    const COMPLETE = 1;
    const PARTIAL = 2;

    /**
     * @param $table Table
     *
     * @return int
     */
    public function addColumns(Table $table):int;

}
// vim:sw=4 ts=4 sts=4 et
