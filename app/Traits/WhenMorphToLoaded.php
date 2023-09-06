<?php

namespace App\Traits;

//https://laracasts.com/discuss/channels/eloquent/api-resource-and-polymorphic-relationships
trait WhenMorphToLoaded
{
    public function whenMorphToLoaded($name, $map)
    {
        return $this->whenLoaded($name, function () use ($name, $map) {
            $morphType = $name . '_type';
            $morphAlias = $this->resource->$morphType;
//            dd($morphAlias);
//            $morphClass = Relation::getMorphedModel($morphAlias);
//            dd($morphClass);
//            if (empty($morphClass)) {
//                return null;
//            }
            $morphResourceClass = $map[$morphAlias];

            return new $morphResourceClass($this->resource->$name);
        });
    }
}