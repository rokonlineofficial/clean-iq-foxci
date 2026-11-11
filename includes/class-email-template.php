<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Branded HTML emails (customer confirmation + admin notification).
 * Table-based layout with inline CSS so it renders in Gmail, Outlook and mobile apps.
 *
 * Brand: #2B275A navy, #00BFA5 teal, #EB1E63 pink, #9DEF31 lime, + white.
 */
class CBC_Email_Template {

    const NAVY = '#2B275A';
    const TEAL = '#00BFA5';
    const PINK = '#EB1E63';
    const LIME = '#9DEF31';
    const MUTE = '#6B6890';
    const SOFT = '#F4F3F9';
    const LINE = '#E3E1EE';

    private static function font() {
        return "'ITC Avant Garde Gothic','Century Gothic','URW Gothic',Questrial,Arial,Helvetica,sans-serif";
    }

    /* ------------------------------------------------------------------
     * Formatting helpers
     * ------------------------------------------------------------------ */

    private static function money( $amount ) {
        return '$' . number_format( (float) $amount, 2 );
    }

    private static function pretty_date( $date ) {
        $dt = DateTime::createFromFormat( 'Y-m-d', $date, new DateTimeZone( 'UTC' ) );
        return $dt ? $dt->format( 'l, F j, Y' ) : $date;
    }

    private static function pretty_time( $time ) {
        $dt = DateTime::createFromFormat( 'H:i', $time, new DateTimeZone( 'UTC' ) );
        return $dt ? $dt->format( 'g:i A' ) : $time;
    }

    private static function plural( $n, $one, $many ) {
        return (int) $n . ' ' . ( 1 === (int) $n ? $one : $many );
    }

    private static function extras_list( $data, $extras ) {
        if ( ! is_array( $extras ) ) {
            $extras = ( ! empty( $data['selected_extras'] ) && 'None' !== $data['selected_extras'] ) ? array_map( 'trim', explode( ', ', $data['selected_extras'] ) ) : [];
        }
        return $extras;
    }

    /* ------------------------------------------------------------------
     * Building blocks
     * ------------------------------------------------------------------ */

    private static function section_title( $text ) {
        return '<tr><td style="padding:28px 0 8px 0;font-family:' . self::font() . ';font-size:12px;font-weight:bold;letter-spacing:1.6px;text-transform:uppercase;color:' . self::NAVY . ';border-bottom:2px solid ' . self::TEAL . ';">' . esc_html( $text ) . '</td></tr>';
    }

    /** One label/value row. $value_html must already be escaped / safe HTML. */
    private static function row( $label, $value_html ) {
        return '<tr><td style="padding:12px 0;border-bottom:1px solid ' . self::LINE . ';">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr>'
            . '<td class="cbc-stack" width="38%" valign="top" style="font-family:' . self::font() . ';font-size:12px;letter-spacing:0.8px;text-transform:uppercase;color:' . self::MUTE . ';padding-right:12px;">' . esc_html( $label ) . '</td>'
            . '<td class="cbc-stack" valign="top" style="font-family:' . self::font() . ';font-size:15px;line-height:1.5;font-weight:bold;color:' . self::NAVY . ';">' . $value_html . '</td>'
            . '</tr></table></td></tr>';
    }

    private static function pills( $names, $included = [] ) {
        if ( empty( $names ) ) {
            return '<span style="font-weight:normal;color:' . self::MUTE . ';">' . esc_html__( 'None', 'cleaning-booking-calculator' ) . '</span>';
        }
        $html = '';
        foreach ( $names as $name ) {
            $is_included = in_array( $name, $included, true );
            $html       .= '<span style="display:inline-block;margin:0 6px 6px 0;padding:5px 12px;border-radius:14px;font-size:12px;font-weight:bold;'
                . ( $is_included
                    ? 'background-color:' . self::TEAL . ';color:' . self::NAVY . ';'
                    : 'background-color:' . self::SOFT . ';color:' . self::NAVY . ';border:1px solid ' . self::LINE . ';' )
                . '">' . esc_html( $name ) . ( $is_included ? ' &middot; ' . esc_html__( 'Included', 'cleaning-booking-calculator' ) : '' ) . '</span>';
        }
        return $html;
    }

    private static function total_block( $amount ) {
        return '<tr><td style="padding:28px 0 0 0;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:' . self::TEAL . ';border-radius:8px;"><tr>'
            . '<td bgcolor="' . self::TEAL . '" style="padding:22px 26px;border-radius:8px;font-family:' . self::font() . ';">'
            . '<div style="font-size:12px;font-weight:bold;letter-spacing:1.6px;text-transform:uppercase;color:' . self::NAVY . ';">' . esc_html__( 'Estimated Total', 'cleaning-booking-calculator' ) . '</div>'
            . '<div style="font-size:36px;line-height:1.2;font-weight:bold;color:' . self::NAVY . ';padding-top:4px;">' . esc_html( self::money( $amount ) ) . '</div>'
            . '</td></tr></table></td></tr>';
    }

    private static function brand_strip() {
        $cell = function ( $color ) {
            return '<td width="25%" height="5" bgcolor="' . $color . '" style="height:5px;line-height:5px;font-size:1px;background-color:' . $color . ';">&nbsp;</td>';
        };
        return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr>'
            . $cell( self::NAVY ) . $cell( self::TEAL ) . $cell( self::PINK ) . $cell( self::LIME )
            . '</tr></table>';
    }

    /** Full email shell. $inner_rows is a string of <tr> rows. */
    private static function wrap( $preheader, $badge_html, $title, $inner_rows ) {
        $site = get_bloginfo( 'name' );
        $logo = esc_url( get_option( 'cbc_email_logo_url', '' ) );

        $brand = $logo
            ? '<img src="' . $logo . '" alt="' . esc_attr( $site ) . '" height="44" style="display:block;border:0;height:44px;max-width:220px;width:auto;">'
            : '<span style="font-family:' . self::font() . ';font-size:24px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;color:#ffffff;">' . esc_html( $site ) . '</span>';

        $html  = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        $html .= '<title>' . esc_html( $title ) . '</title>';
        $html .= '<style>@media only screen and (max-width:620px){.cbc-wrap{width:100%!important}.cbc-pad{padding-left:20px!important;padding-right:20px!important}.cbc-stack{display:block!important;width:100%!important;padding:0 0 4px 0!important}}</style>';
        $html .= '</head><body style="margin:0;padding:0;background-color:' . self::SOFT . ';">';
        $html .= '<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:' . self::SOFT . ';">' . esc_html( $preheader ) . '</div>';
        $html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="' . self::SOFT . '" style="background-color:' . self::SOFT . ';"><tr><td align="center" style="padding:28px 12px;">';
        $html .= '<table role="presentation" class="cbc-wrap" width="600" cellspacing="0" cellpadding="0" border="0" style="width:600px;max-width:600px;background-color:#ffffff;border-radius:10px;overflow:hidden;">';

        // Header
        $html .= '<tr><td class="cbc-pad" bgcolor="' . self::NAVY . '" style="background-color:' . self::NAVY . ';padding:30px 36px;">' . $brand . '</td></tr>';
        $html .= '<tr><td height="5" bgcolor="' . self::TEAL . '" style="height:5px;line-height:5px;font-size:1px;background-color:' . self::TEAL . ';">&nbsp;</td></tr>';

        // Body
        $html .= '<tr><td class="cbc-pad" style="padding:34px 36px 40px 36px;">';
        $html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">';
        $html .= '<tr><td style="padding-bottom:6px;">' . $badge_html . '</td></tr>';
        $html .= '<tr><td style="font-family:' . self::font() . ';font-size:26px;line-height:1.25;font-weight:bold;color:' . self::NAVY . ';padding-bottom:10px;">' . esc_html( $title ) . '</td></tr>';
        $html .= $inner_rows;
        $html .= '</table></td></tr>';

        // Footer
        $html .= '<tr><td>' . self::brand_strip() . '</td></tr>';
        $html .= '<tr><td class="cbc-pad" bgcolor="' . self::NAVY . '" align="center" style="background-color:' . self::NAVY . ';padding:24px 36px;font-family:' . self::font() . ';font-size:12px;line-height:1.7;color:#ffffff;">';
        $html .= '<strong style="letter-spacing:1.5px;text-transform:uppercase;">' . esc_html( $site ) . '</strong><br>';
        $html .= '<a href="' . esc_url( home_url( '/' ) ) . '" style="color:' . self::TEAL . ';text-decoration:none;">' . esc_html( preg_replace( '#^https?://#', '', untrailingslashit( home_url( '/' ) ) ) ) . '</a>';
        $html .= '</td></tr>';

        $html .= '</table></td></tr></table></body></html>';
        return $html;
    }

    private static function badge( $text, $bg, $fg ) {
        return '<span style="display:inline-block;padding:4px 12px;border-radius:12px;background-color:' . $bg . ';color:' . $fg . ';font-family:' . self::font() . ';font-size:11px;font-weight:bold;letter-spacing:1.4px;text-transform:uppercase;">' . esc_html( $text ) . '</span>';
    }

    private static function paragraph( $html ) {
        return '<tr><td style="font-family:' . self::font() . ';font-size:15px;line-height:1.7;color:' . self::NAVY . ';padding-bottom:4px;">' . $html . '</td></tr>';
    }

    /** Shared "details" rows (service, schedule, property, extras). */
    private static function booking_rows( $data, $extras ) {
        $cfg      = CBC_Pricing::get_config();
        $included = isset( $cfg['services'][ $data['service_type'] ]['included'] ) ? $cfg['services'][ $data['service_type'] ]['included'] : [];
        $extras   = self::extras_list( $data, $extras );

        $rows  = self::section_title( __( 'Appointment', 'cleaning-booking-calculator' ) );
        $rows .= self::row( __( 'Service', 'cleaning-booking-calculator' ), esc_html( $data['service_type'] ) );
        $rows .= self::row( __( 'Date', 'cleaning-booking-calculator' ), esc_html( self::pretty_date( $data['appointment_date'] ) ) );
        $rows .= self::row( __( 'Time', 'cleaning-booking-calculator' ), esc_html( self::pretty_time( $data['appointment_time'] ) ) );
        $rows .= self::row( __( 'Address', 'cleaning-booking-calculator' ), nl2br( esc_html( $data['address'] ) ) );

        $rows .= self::section_title( __( 'Your Home', 'cleaning-booking-calculator' ) );
        $rows .= self::row( __( 'Size', 'cleaning-booking-calculator' ), esc_html( number_format( (int) $data['sqft'] ) . ' sq. ft' ) );
        $rows .= self::row( __( 'Bedrooms', 'cleaning-booking-calculator' ), esc_html( (int) $data['bedrooms'] ) );
        $rows .= self::row(
            __( 'Bathrooms', 'cleaning-booking-calculator' ),
            esc_html( self::plural( $data['bathrooms'], 'full', 'full' ) . ( (int) $data['half_baths'] > 0 ? ' + ' . self::plural( $data['half_baths'], 'half', 'half' ) : '' ) )
        );
        $rows .= self::row( __( 'Kitchens', 'cleaning-booking-calculator' ), esc_html( (int) $data['kitchens'] ) );

        $rows .= self::section_title( __( 'Extras & Laundry', 'cleaning-booking-calculator' ) );
        $rows .= self::row( __( 'Extras', 'cleaning-booking-calculator' ), self::pills( $extras, $included ) );
        $laundry = ( empty( $data['laundry_service'] ) || 'None' === $data['laundry_service'] ) ? __( 'None', 'cleaning-booking-calculator' ) : $data['laundry_service'];
        $rows   .= self::row( __( 'Laundry', 'cleaning-booking-calculator' ), esc_html( $laundry ) );

        return $rows;
    }

    /* ------------------------------------------------------------------
     * Customer confirmation
     * ------------------------------------------------------------------ */

    public static function customer( $data, $entry_id, $extras = null ) {
        $first = strtok( $data['full_name'], ' ' );

        $rows  = self::paragraph( sprintf(
            /* translators: %s: customer first name */
            esc_html__( 'Hi %s, thank you for choosing us! We have received your request and will confirm your appointment shortly. Here is a summary of what you sent us.', 'cleaning-booking-calculator' ),
            '<strong>' . esc_html( $first ) . '</strong>'
        ) );
        $rows .= self::total_block( $data['total_amount'] );
        $rows .= self::booking_rows( $data, $extras );

        if ( ! empty( trim( $data['comments'] ) ) ) {
            $rows .= self::section_title( __( 'Your Notes', 'cleaning-booking-calculator' ) );
            $rows .= self::row( __( 'Instructions', 'cleaning-booking-calculator' ), nl2br( esc_html( $data['comments'] ) ) );
        }

        $rows .= self::paragraph( '<div style="padding-top:26px;font-size:13px;line-height:1.6;color:' . self::MUTE . ';">'
            . esc_html__( 'This total is an estimate based on the details you provided. We will confirm the final price when we confirm your appointment.', 'cleaning-booking-calculator' )
            . '</div>' );

        return self::wrap(
            sprintf( __( 'We received your cleaning request (#%d).', 'cleaning-booking-calculator' ), $entry_id ),
            self::badge( sprintf( __( 'Request #%d', 'cleaning-booking-calculator' ), $entry_id ), self::TEAL, self::NAVY ),
            __( 'We received your cleaning request!', 'cleaning-booking-calculator' ),
            $rows
        );
    }

    /* ------------------------------------------------------------------
     * Admin notification
     * ------------------------------------------------------------------ */

    public static function admin( $data, $entry_id, $extras = null ) {
        $rows  = self::paragraph( esc_html__( 'A customer just submitted the booking form. Details below.', 'cleaning-booking-calculator' ) );
        $rows .= self::total_block( $data['total_amount'] );

        $rows .= self::section_title( __( 'Customer', 'cleaning-booking-calculator' ) );
        $rows .= self::row( __( 'Name', 'cleaning-booking-calculator' ), esc_html( $data['full_name'] ) );
        $rows .= self::row( __( 'Email', 'cleaning-booking-calculator' ), '<a href="mailto:' . esc_attr( $data['email'] ) . '" style="color:' . self::NAVY . ';text-decoration:underline;">' . esc_html( $data['email'] ) . '</a>' );
        $rows .= self::row( __( 'Phone', 'cleaning-booking-calculator' ), '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $data['phone'] ) ) . '" style="color:' . self::NAVY . ';text-decoration:underline;">' . esc_html( $data['phone'] ) . '</a>' );

        $rows .= self::booking_rows( $data, $extras );

        $rows .= self::section_title( __( 'Special Instructions', 'cleaning-booking-calculator' ) );
        $notes = ! empty( trim( $data['comments'] ) )
            ? nl2br( esc_html( $data['comments'] ) )
            : '<span style="font-weight:normal;color:' . self::MUTE . ';">' . esc_html__( 'None', 'cleaning-booking-calculator' ) . '</span>';
        $rows .= self::row( __( 'Comments', 'cleaning-booking-calculator' ), $notes );

        $rows .= '<tr><td align="center" style="padding:32px 0 0 0;">'
            . '<a href="' . esc_url( admin_url( 'admin.php?page=cbc-entries' ) ) . '" style="display:inline-block;padding:14px 34px;border-radius:6px;background-color:' . self::TEAL . ';color:' . self::NAVY . ';font-family:' . self::font() . ';font-size:14px;font-weight:bold;letter-spacing:1.2px;text-transform:uppercase;text-decoration:none;">'
            . esc_html__( 'View in Dashboard', 'cleaning-booking-calculator' ) . '</a></td></tr>';

        return self::wrap(
            sprintf( __( 'New booking #%1$d from %2$s', 'cleaning-booking-calculator' ), $entry_id, $data['full_name'] ),
            self::badge( __( 'New request', 'cleaning-booking-calculator' ), self::PINK, '#ffffff' ) . ' ' . self::badge( '#' . $entry_id, self::NAVY, '#ffffff' ),
            __( 'New Cleaning Booking', 'cleaning-booking-calculator' ),
            $rows
        );
    }
}
