<?php
namespace Modules\Shop\Api\Resources;

use Modules\Api\Services\ApiResource;

class DeliveryMethod extends ApiResource
{

    public function mapCommon(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'territoryId' => $this->settings() ? $this->settings()->territory_id : null,
            'territoryName' => $this->settings() ? $this->settings()->territory()->name : '---',
        ];
    }


}
