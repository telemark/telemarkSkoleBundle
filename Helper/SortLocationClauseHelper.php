<?php

namespace tfk\telemarkSkoleBundle\Helper;

use eZ\Publish\Core\Repository\LocationService;
use eZ\Publish\API\Repository\Values\Content\Location;

class SortLocationClauseHelper extends LocationService
{
    public function __construct()
    {
    }
 
    public function getSortClauseFromLocation( Location $location )
    {
        return $this->getSortClauseBySortField( $location->sortField,
                                                $location->sortOrder );
    }

}