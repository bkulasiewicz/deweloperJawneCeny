<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode to display price history for a resource
 * Usage: [jawneceny_price_history number="A1"]
 * Returns HTML with data-attributes or "N/A" on error
 */
class PriceHistoryShortcode {

    /**
     * Handle shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output or "N/A"
     */
    public static function handle($atts) {
        // Parse attributes
        $atts = shortcode_atts([
            'number' => '',  // unit number (nr_lokalu)
        ], $atts, 'jawneceny_price_history');

        // Validate required parameter
        if (empty($atts['number'])) {
            return 'N/A';
        }

        try {
            // Get resource to verify it exists and get its ID
            $resourceUseCase = DIContainer::get(\JawneCeny\GetResourceForSinglePageUseCase::class);
            $resource = $resourceUseCase->execute($atts['number']);

            // Resource not found
            if (!$resource) {
                Logger::info('PriceHistoryShortcode: Resource not found: ' . $atts['number']);
                return 'N/A';
            }

            // Get price history using resource ID
            $historyUseCase = DIContainer::get(\JawneCeny\GetPriceHistoryUseCase::class);
            $history = $historyUseCase->execute($resource->id);

            // No price history found
            if (empty($history)) {
                return '<div class="jawneceny-no-history">Brak historii cen</div>';
            }

            // Render HTML
            return self::renderHTML($history);

        } catch (\Exception $e) {
            Logger::error('PriceHistoryShortcode error: ' . $e->getMessage());
            return 'N/A';
        }
    }

    /**
     * Render price history as HTML with data-attributes
     *
     * @param array $history Array of PresentablePriceHistory objects
     * @return string HTML output
     */
    private static function renderHTML($history) {
        $output = '<div class="jawneceny-price-history">';

        foreach ($history as $item) {
            // Build data attributes
            $data_attrs = sprintf(
                'data-total-price="%s" data-price-m2="%s" data-price-extras="%s" data-date="%s"',
                esc_attr($item->total_price),
                esc_attr($item->price_per_m2 ?? ''),
                esc_attr($item->price_with_extras ?? ''),
                esc_attr($item->price_date)
            );

            // Build item HTML
            $output .= sprintf('<div class="price-history-item" %s>', $data_attrs);
            $output .= sprintf('<span class="price">%s</span>', esc_html($item->total_price));
            $output .= sprintf('<span class="date">%s</span>', esc_html($item->price_date));
            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }
}
