<?php

namespace App\Graphs\Application;

use App\Data\Graphing\DataSeries;
use App\Data\Graphing\GraphOptions;

class SneckResultsGraph extends ApplicationGraph
{
    public function getSeries(): array
    {
        $metric = $this->metric();
        $series = [];
        foreach (['ok', 'warning', 'critical', 'unknown', 'errored'] as $field) {
            $series[$field] = new DataSeries($metric, $field, __("graph.application.sneck.$field"));
        }

        return $series;
    }

    protected function options(): GraphOptions
    {
        // counts of checks, so no decimals
        return new GraphOptions(legendTitle: __('graph.application.sneck.results'), palette: 'psychedelic', precision: 0);
    }
}
