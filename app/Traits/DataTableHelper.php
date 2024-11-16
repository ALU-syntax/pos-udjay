<?php

namespace App\Traits;

trait DataTableHelper
{

    public function basicActions($row)
    {
        $actions = [];
        // $actions['Detail'] = route(str_replace('/', '.', request()->path()) . '.show', $row->id);
        if (user()->can('update ' . request()->path())) {
            $actions['Edit'] = route(str_replace('/', '/', request()->path()) . '/edit', $row->id);
        }
        if (user()->can('delete ' . request()->path())) {
            $actions['Delete'] = route(str_replace('/', '/', request()->path()) . '/destroy', $row->id);
        }

        return $actions;
    }
}
