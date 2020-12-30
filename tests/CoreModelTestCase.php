<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use Symfony\Component\Yaml\Yaml;

class CoreModelTestCase extends ModelTestCase
{
    /**
     * Returns a .yml dataset under this name
     * @return array
     */
    public function getDataSet(): array
    {
        $datasetFile = implode(DIRECTORY_SEPARATOR, [__DIR__, 'datasets', $this->dataset . '.yml']);
        $dataset = Yaml::parseFile($datasetFile);

        return $dataset;
    }
}
