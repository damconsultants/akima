<?php
declare(strict_types=1);

namespace DamConsultants\Akima\ViewModel\Customer;

use Magento\Framework\View\Element\Block\ArgumentInterface;

class ReviewList implements ArgumentInterface
{
    /**
     * Returns the GraphQL query string for customer reviews,
     * including bynder_multi_img as a custom attribute.
     */
    public function getCustomerReviewsGraphQlQuery(): string
    {
        return <<<QUERY
reviews(
    currentPage: %currentPage%,
    pageSize: %pageSize%
) {
    items {
        product {
            name
            url_key
            sku
            image {
                label
                url
            }
            bynder_multi_img
        }
        created_at
        ratings_breakdown {
            name
            value
        }
        summary
        text
    }
    page_info {
        current_page
        page_size
        total_pages
    }
}
QUERY;
    }
}

