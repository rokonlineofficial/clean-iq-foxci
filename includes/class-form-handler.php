<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CBC_Form_Handler {

    const RATE_LIMIT_MAX    = 10;   // valid submissions per IP...
    const RATE_LIMIT_WINDOW = HOUR_IN_SECONDS; // ...per hour.

    public static function init() {
        add_action( 'admin_post_nopriv_cbc_submit_form', [ __CLASS__, 'process_submission' ] );
        add_action( 'admin_post_cbc_submit_form', [ __CLASS__, 'process_submission' ] );
    }

    /* ------------------------------------------------------------------
     * Small input helpers
     * ------------------------------------------------------------------ */

    private static function post_text( $key ) {
        return ( isset( $_POST[ $key ] ) && is_scalar( $_POST[ $key ] ) )
            ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) )
            : '';
    }

    private static function post_textarea( $key ) {
        return ( isset( $_POST[ $key ] ) && is_scalar( $_POST[ $key ] ) )
            ? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) )
            : '';
    }

    private static function post_int( $key, $default = 0 ) {
        return ( isset( $_POST[ $key ] ) && is_scalar( $_POST[ $key ] ) && '' !== $_POST[ $key ] )
            ? absint( wp_unslash( $_POST[ $key ] ) )
            : $default;
    }

    private static function client_ip() {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : 'unknown';
    }

    /* ------------------------------------------------------------------
     * Submission
     * ------------------------------------------------------------------ */

    public static function process_submission() {

        // Honeypot: real visitors never see or fill this field. Bots get a fake "success".
        if ( ! empty( $_POST['cbc_website'] ) ) {
            self::redirect_success();
        }

        $cfg    = CBC_Pricing::get_config();
        $errors = [];

        // ---- Read & sanitize -------------------------------------------------
        $service_type     = self::post_text( 'service_type' );
        $bedrooms         = self::post_int( 'bedrooms' );
        $bathrooms        = self::post_int( 'bathrooms' );
        $half_baths       = self::post_int( 'half_baths', 0 );
        $kitchens         = self::post_int( 'kitchens' );
        $sqft             = self::post_int( 'sqft' );
        $laundry_service  = self::post_text( 'laundry_service' );
        $address          = self::post_textarea( 'address' );
        $comments         = self::post_textarea( 'comments' );
        $appointment_date = self::post_text( 'appointment_date' );
        $appointment_time = self::post_text( 'appointment_time' );
        $full_name        = self::post_text( 'full_name' );
        $phone            = self::post_text( 'phone' );
        $email            = ( isset( $_POST['email'] ) && is_scalar( $_POST['email'] ) ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

        $extras = [];
        if ( isset( $_POST['extras'] ) && is_array( $_POST['extras'] ) ) {
            $extras = array_values( array_unique( array_map( 'sanitize_text_field', wp_unslash( $_POST['extras'] ) ) ) );
        }
        if ( '' === $laundry_service ) {
            $laundry_service = 'None';
        }

        // What we hand back to the form if something is wrong.
        $old = [
            'service_type'     => $service_type,
            'bedrooms'         => $bedrooms ? (string) $bedrooms : '',
            'bathrooms'        => $bathrooms ? (string) $bathrooms : '',
            'half_baths'       => isset( $_POST['half_baths'] ) && '' !== $_POST['half_baths'] ? (string) $half_baths : '',
            'kitchens'         => $kitchens ? (string) $kitchens : '',
            'sqft'             => $sqft ? (string) $sqft : '',
            'extras'           => $extras,
            'laundry_service'  => 'None' === $laundry_service ? '' : $laundry_service,
            'address'          => $address,
            'comments'         => $comments,
            'appointment_date' => $appointment_date,
            'appointment_time' => $appointment_time,
            'full_name'        => $full_name,
            'phone'            => $phone,
            'email'            => $email,
        ];

        // ---- Validate (never trust the browser) ------------------------------
        if ( ! isset( $cfg['services'][ $service_type ] ) ) {
            $errors[] = __( 'Please select a service type.', 'cleaning-booking-calculator' );
        }
        if ( $bedrooms < 1 || $bedrooms > 5 ) {
            $errors[] = __( 'Please select the number of bedrooms.', 'cleaning-booking-calculator' );
        }
        if ( $bathrooms < 1 || $bathrooms > 5 ) {
            $errors[] = __( 'Please select the number of bathrooms.', 'cleaning-booking-calculator' );
        }
        if ( $half_baths > 3 ) {
            $errors[] = __( 'Please select a valid number of half bathrooms.', 'cleaning-booking-calculator' );
        }
        if ( $kitchens < 1 || $kitchens > 5 ) {
            $errors[] = __( 'Please select the number of kitchens.', 'cleaning-booking-calculator' );
        }
        if ( $sqft < 50 || $sqft > 25000 ) {
            $errors[] = __( 'Please enter your home size (between 50 and 25,000 sq. ft.).', 'cleaning-booking-calculator' );
        }
        foreach ( $extras as $extra ) {
            if ( ! isset( $cfg['extras'][ $extra ] ) ) {
                $errors[] = __( 'One of the selected extras is not valid.', 'cleaning-booking-calculator' );
                break;
            }
        }
        if ( 'None' !== $laundry_service && ! isset( $cfg['laundry'][ $laundry_service ] ) ) {
            $errors[] = __( 'The selected laundry option is not valid.', 'cleaning-booking-calculator' );
        }
        if ( '' === trim( $address ) ) {
            $errors[] = __( 'Please enter your address.', 'cleaning-booking-calculator' );
        }

        if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $appointment_date, $m ) && checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
            if ( $appointment_date < current_time( 'Y-m-d' ) ) {
                $errors[] = __( 'Please choose today or a future date.', 'cleaning-booking-calculator' );
            }
        } else {
            $errors[] = __( 'Please choose a valid date.', 'cleaning-booking-calculator' );
        }

        if ( ! preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $appointment_time ) || $appointment_time < '09:00' || $appointment_time > '14:00' ) {
            $errors[] = __( 'Cleaning appointments can only be scheduled between 9:00 AM and 2:00 PM.', 'cleaning-booking-calculator' );
        }

        if ( strlen( $full_name ) < 2 || strlen( $full_name ) > 255 ) {
            $errors[] = __( 'Please enter your full name.', 'cleaning-booking-calculator' );
        }
        if ( preg_match_all( '/\d/', $phone ) < 7 || strlen( $phone ) > 50 ) {
            $errors[] = __( 'Please enter a valid phone number.', 'cleaning-booking-calculator' );
        }
        if ( ! is_email( $email ) ) {
            $errors[] = __( 'Please enter a valid email address.', 'cleaning-booking-calculator' );
        }
        if ( empty( $_POST['terms_accepted'] ) ) {
            $errors[] = __( 'You must agree to the Terms and Conditions.', 'cleaning-booking-calculator' );
        }

        // ---- Re-calculate the price on the server -----------------------------
        $total_cents = false;
        if ( empty( $errors ) ) {
            $total_cents = CBC_Pricing::calculate_cents( [
                'service_type'    => $service_type,
                'sqft'            => $sqft,
                'bathrooms'       => $bathrooms,
                'half_baths'      => $half_baths,
                'kitchens'        => $kitchens,
                'extras'          => $extras,
                'laundry_service' => $laundry_service,
            ] );
            if ( false === $total_cents ) {
                $errors[] = __( 'We could not calculate your quote. Please review your selections.', 'cleaning-booking-calculator' );
            }
        }

        // ---- Rate limit (valid submissions only) ------------------------------
        if ( empty( $errors ) ) {
            $rl_key   = 'cbc_rl_' . md5( self::client_ip() );
            $attempts = (int) get_transient( $rl_key );
            if ( $attempts >= self::RATE_LIMIT_MAX ) {
                $errors[] = __( 'Too many requests from your connection. Please try again later or call us.', 'cleaning-booking-calculator' );
            }
        }

        if ( ! empty( $errors ) ) {
            self::redirect_with_errors( $errors, $old );
        }

        set_transient( $rl_key, $attempts + 1, self::RATE_LIMIT_WINDOW );

        // ---- Save --------------------------------------------------------------
        $entry_data = [
            'service_type'     => $service_type,
            'bedrooms'         => $bedrooms,
            'bathrooms'        => $bathrooms,
            'half_baths'       => $half_baths,
            'kitchens'         => $kitchens,
            'sqft'             => $sqft,
            'selected_extras'  => ! empty( $extras ) ? implode( ', ', $extras ) : 'None',
            'laundry_service'  => $laundry_service,
            'total_amount'     => round( $total_cents / 100, 2 ),
            'address'          => $address,
            'comments'         => $comments,
            'appointment_date' => $appointment_date,
            'appointment_time' => $appointment_time,
            'full_name'        => $full_name,
            'phone'            => $phone,
            'email'            => $email,
            'status'           => 'Pending',
            'created_at'       => current_time( 'mysql' ),
        ];

        $insert_id = CBC_DB_Handler::insert_entry( $entry_data );

        if ( ! $insert_id ) {
            self::redirect_with_errors(
                [ __( 'Sorry, we could not save your request. Please try again or contact us directly.', 'cleaning-booking-calculator' ) ],
                $old
            );
        }

        self::send_emails( $entry_data, $insert_id, $extras );
        self::redirect_success();
    }

    /* ------------------------------------------------------------------
     * Redirect helpers
     * ------------------------------------------------------------------ */

    private static function back_url() {
        $referer = wp_get_referer();
        $url     = $referer ? $referer : home_url( '/' );
        return remove_query_arg( [ 'cbc_status', 'cbc_t' ], $url );
    }

    private static function redirect_success() {
        $redirect_url = get_option( 'cbc_redirect_url' );
        if ( ! empty( $redirect_url ) ) {
            wp_redirect( esc_url_raw( $redirect_url ) ); // phpcs:ignore WordPress.Security.SafeRedirect -- admin-configured URL.
            exit;
        }
        wp_safe_redirect( add_query_arg( 'cbc_status', 'success', self::back_url() ) . '#cbc-form-top' );
        exit;
    }

    private static function redirect_with_errors( $errors, $old ) {
        $token = wp_generate_password( 12, false, false );
        set_transient( 'cbc_form_' . $token, [ 'errors' => $errors, 'old' => $old ], 10 * MINUTE_IN_SECONDS );

        $url = add_query_arg( [ 'cbc_status' => 'error', 'cbc_t' => $token ], self::back_url() );
        wp_safe_redirect( $url . '#cbc-form-top' );
        exit;
    }

    /**
     * Used by the form view: returns [ 'errors' => [], 'old' => [] ] after a failed submit, else null.
     */
    public static function get_error_state() {
        if ( ! isset( $_GET['cbc_status'], $_GET['cbc_t'] ) || 'error' !== sanitize_key( wp_unslash( $_GET['cbc_status'] ) ) ) {
            return null;
        }
        $state = get_transient( 'cbc_form_' . sanitize_key( wp_unslash( $_GET['cbc_t'] ) ) );
        if ( ! is_array( $state ) || empty( $state['errors'] ) ) {
            return null;
        }
        return [
            'errors' => (array) $state['errors'],
            'old'    => isset( $state['old'] ) ? (array) $state['old'] : [],
        ];
    }

    /* ------------------------------------------------------------------
     * Emails
     * ------------------------------------------------------------------ */

    private static function send_emails( $data, $entry_id, $extras = [] ) {
        $admin_email = get_option( 'cbc_admin_email', get_option( 'admin_email' ) );
        if ( empty( $admin_email ) ) {
            $admin_email = get_option( 'admin_email' );
        }

        $safe_name     = trim( preg_replace( '/[^\p{L}\p{N} .\'-]/u', '', $data['full_name'] ) );
        $admin_headers = [ 'Content-Type: text/html; charset=UTF-8' ];
        if ( is_email( $data['email'] ) ) {
            $admin_headers[] = 'Reply-To: ' . ( $safe_name ? $safe_name . ' ' : '' ) . '<' . $data['email'] . '>';
        }
        $customer_headers = [ 'Content-Type: text/html; charset=UTF-8', 'Reply-To: ' . $admin_email ];

        // Admin notification
        $admin_subject = sprintf( __( 'New Booking Request #%1$d - %2$s', 'cleaning-booking-calculator' ), $entry_id, $data['full_name'] );
        wp_mail( $admin_email, $admin_subject, CBC_Email_Template::admin( $data, $entry_id, $extras ), $admin_headers );

        // Customer confirmation
        if ( is_email( $data['email'] ) ) {
            $customer_subject = __( 'We received your cleaning request!', 'cleaning-booking-calculator' );
            wp_mail( $data['email'], $customer_subject, CBC_Email_Template::customer( $data, $entry_id, $extras ), $customer_headers );
        }
    }
}
