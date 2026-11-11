<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Single source of truth for pricing.
 *
 * All prices live in includes/pricing-config.json (integer cents).
 * The same file feeds the live calculator (JS) and the server-side
 * recalculation, so the quote shown to the customer and the amount saved
 * in the database can never differ.
 *
 * Formula:
 *   base      = sqft x service rate  (minimum base applies below the sqft threshold)
 *   + bathrooms x bathroom price + half baths x half-bath price
 *   + kitchens x kitchen price
 *   + paid extras (extras included with the chosen service are free)
 *   + laundry option
 */
class CBC_Pricing {

    private static $config = null;

    public static function get_config() {
        if ( null === self::$config ) {
            $json         = file_get_contents( CBC_PLUGIN_DIR . 'includes/pricing-config.json' );
            $data         = json_decode( $json, true );
            self::$config = is_array( $data ) ? $data : [];
        }
        return self::$config;
    }

    /**
     * Calculate the total in cents. Returns false if any input is not valid.
     *
     * @param array $args service_type, sqft, bathrooms, half_baths, kitchens, extras[], laundry_service
     */
    public static function calculate_cents( $args ) {
        $cfg = self::get_config();

        $service = isset( $args['service_type'] ) ? $args['service_type'] : '';
        if ( ! isset( $cfg['services'][ $service ] ) ) {
            return false;
        }
        $svc = $cfg['services'][ $service ];

        $sqft       = isset( $args['sqft'] ) ? (int) $args['sqft'] : 0;
        $bathrooms  = isset( $args['bathrooms'] ) ? (int) $args['bathrooms'] : 0;
        $half_baths = isset( $args['half_baths'] ) ? (int) $args['half_baths'] : 0;
        $kitchens   = isset( $args['kitchens'] ) ? (int) $args['kitchens'] : 0;
        $extras     = isset( $args['extras'] ) && is_array( $args['extras'] ) ? array_unique( $args['extras'] ) : [];
        $laundry    = isset( $args['laundry_service'] ) ? $args['laundry_service'] : '';

        $total = $sqft * (int) $svc['rate_cents'];
        if ( $sqft < (int) $cfg['min_base']['below_sqft'] ) {
            $total = max( $total, (int) $cfg['min_base']['cents'] );
        }

        $total += $bathrooms * (int) $cfg['bathroom_cents'];
        $total += $half_baths * (int) $cfg['half_bath_cents'];
        $total += $kitchens * (int) $cfg['kitchen_cents'];

        foreach ( $extras as $name ) {
            if ( ! isset( $cfg['extras'][ $name ] ) ) {
                return false;
            }
            if ( in_array( $name, $svc['included'], true ) ) {
                continue;
            }
            $total += (int) $cfg['extras'][ $name ];
        }

        if ( '' !== $laundry && 'None' !== $laundry ) {
            if ( ! isset( $cfg['laundry'][ $laundry ] ) ) {
                return false;
            }
            $total += (int) $cfg['laundry'][ $laundry ];
        }

        return (int) $total;
    }
}
