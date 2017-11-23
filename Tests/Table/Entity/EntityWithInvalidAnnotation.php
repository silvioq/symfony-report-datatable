<?php

namespace  Silvioq\ReportBundle\Tests\Table\Entity;
use Doctrine\ORM\Mapping as ORM;

use Silvioq\ReportBundle\Annotation\TableColumn;

/**
 * @ORM\Entity
 */
class EntityWithInvalidAnnotation
{

    /**
     * @TableColumn(label="This name", key=4)
     * @ORM\Column(name="name",type="string",length=60);
     */
    private $name;
    
    /**
     * @ORM\Column(name="dummy",type="string",length=60);
     */
    private $dummy;
    

    /**
     * @TableColumn(order=2)
     */
    public function getAge()
    {
        return 42;
    }
    
    public function getDummy()
    {
        return $dummy;
    }
    
    public function setName($name )
    {
        $this->name = $name;
        return $this;
    }
    
    public function getName()
    {
        return $this->name;
    }
}
