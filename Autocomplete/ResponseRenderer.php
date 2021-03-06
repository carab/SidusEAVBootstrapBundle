<?php

namespace Sidus\EAVBootstrapBundle\Autocomplete;

use Pagerfanta\Pagerfanta;
use Sidus\EAVBootstrapBundle\Form\Helper\ComputeLabelHelper;
use Sidus\EAVModelBundle\Doctrine\DataLoaderInterface;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Render a list of data ready to be used by Select2
 */
class ResponseRenderer implements ResponseRendererInterface
{
    /** @var DataLoaderInterface */
    protected $dataLoader;

    /** @var ComputeLabelHelper */
    protected $computeLabelHelper;

    /**
     * @param DataLoaderInterface $dataLoader
     * @param ComputeLabelHelper  $computeLabelHelper
     */
    public function __construct(DataLoaderInterface $dataLoader, ComputeLabelHelper $computeLabelHelper)
    {
        $this->dataLoader = $dataLoader;
        $this->computeLabelHelper = $computeLabelHelper;
    }

    /**
     * @param Pagerfanta              $pager
     * @param AttributeInterface|null $attribute
     * @param string                  $choiceLabel
     *
     * @return JsonResponse
     */
    public function renderResponse(Pagerfanta $pager, AttributeInterface $attribute = null, $choiceLabel = null)
    {
        $results = [];
        $isDataLoaded = false;
        foreach ($pager as $data) {
            if ($data instanceof DataInterface) {
                if (!$isDataLoaded) { // Only try to load data when a result is a DataInterface
                    try {
                        $this->dataLoader->load($pager, 2); // Try to load results if array of EAV data
                    } catch (\InvalidArgumentException $e) {
                        // Do nothing, it just means that we have an array of scalar
                    }
                    $isDataLoaded = true;
                }
                $results[] = $this->parseResult($data, $attribute, $choiceLabel);
            } else {
                $results[] = $this->parseScalarResult($data);
            }
        }

        $headers = [
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            'Pragma' => 'private',
            'Expires' => 0,
        ];

        $response = [
            'results' => $results,
            'pagination' => [
                'more' => $pager->hasNextPage(),
            ],
        ];

        return new JsonResponse($response, 200, $headers);
    }

    /**
     * @param DataInterface      $data
     * @param AttributeInterface $attribute
     * @param string             $choiceLabel
     *
     * @return array
     */
    protected function parseResult(DataInterface $data, AttributeInterface $attribute = null, $choiceLabel = null)
    {
        if ($attribute) {
            if ($attribute->getType()->isRelation() || $attribute->getType()->isEmbedded()) {
                // This is not perfect as it does not uses the OptionResolver of the form type to resolve the 'choice_label'
                $label = $this->computeLabelHelper->computeLabel($data, $data->getId(), $attribute->getFormOptions());
            } else {
                $label = $data->get($attribute->getCode());
            }
        } elseif ($choiceLabel) {
            $label = $this->computeLabelHelper->computeLabel($data, $data->getId(), ['choice_label' => $choiceLabel]);
        } else {
            $label = (string) $data;
        }

        return [
            'id' => $data->getId(),
            'text' => $label,
        ];
    }

    /**
     * @param mixed $data
     *
     * @return array
     */
    protected function parseScalarResult($data)
    {
        if (\is_array($data)) {
            $data = reset($data);
        }

        return [
            'id' => (string) $data,
            'text' => (string) $data,
        ];
    }
}
