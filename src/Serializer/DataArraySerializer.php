<?php

namespace JoBins\LaravelRepository\Serializer;

use League\Fractal\Serializer\ArraySerializer;

/**
 * Class DataArraySerializer
 *
 * @package JoBins\LaravelRepository\Serializer
 */
class DataArraySerializer extends ArraySerializer
{
    /**
     * Serialize a collection.
     *
     * @param string|null $resourceKey
     * @param array       $data
     *
     * @return array
     */
    public function collection(?string $resourceKey, array $data): array
    {
        if ( $resourceKey ) {
            return [$resourceKey => $data];
        }

        return $data;
    }

    /**
     * Serialize an item.
     *
     * @param string|null $resourceKey
     * @param array       $data
     *
     * @return array
     */
    public function item(?string $resourceKey, array $data): array
    {
        return $data;
    }

    /**
     * Serialize null resource.
     *
     * @return array|null
     */
    public function null(): ?array
    {
        return [];
    }
}
