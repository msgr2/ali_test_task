<?php

namespace Database\Factories\Clickhouse;

abstract class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    public function newModel(array $attributes = [])
    {
        $model = new ($this->modelName());

        return $model::make($attributes);
    }

    protected function store(\Illuminate\Support\Collection $results)
    {
        $results->each(function ($model) {
            $model->save();

//            foreach ($model->getRelations() as $name => $items) {
//                if ($items instanceof Enumerable && $items->isEmpty()) {
//                    $model->unsetRelation($name);
//                }
//            }
//
//            $this->createChildren($model);
        });
    }
}
