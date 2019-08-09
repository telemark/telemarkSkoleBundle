<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code. 
 */
declare(strict_types=1);

namespace tfk\telemarkSkoleBundle\UI\Config;

use EzSystems\EzPlatformAdminUi\UI\Config\ProviderInterface;

final class ImageVariationProviderDecorator implements ProviderInterface
{
    /** @var \EzSystems\EzPlatformAdminUi\UI\Config\ProviderInterface */
    private $innerProvider;

    /**
     * @param \EzSystems\EzPlatformAdminUi\UI\Config\ProviderInterface $innerProvider
     */
    public function __construct(ProviderInterface $innerProvider)
    {
        $this->innerProvider = $innerProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(): array
    {
        $variations = $this->innerProvider->getConfig();

        // Unset unnecessary variations from UI
        unset($variations['ezplatform_admin_ui_profile_picture_user_menu']);
        unset($variations['gallery']);
        unset($variations['reference']);
        unset($variations['folder_full']);
        unset($variations['article_topic_large']);
        unset($variations['article_full_width']);

        // Sort variation from the smallest to the largest
        uasort($variations, function($a, $b) {
            // TODO: Compare size of A and B variation
        });

        return $variations;
    }
}