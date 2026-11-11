<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$cbc_cfg          = CBC_Pricing::get_config();
$cbc_state        = CBC_Form_Handler::get_error_state();
$cbc_errors       = $cbc_state ? $cbc_state['errors'] : [];
$cbc_old_json     = $cbc_state ? wp_json_encode( $cbc_state['old'] ) : '';
$cbc_success      = isset( $_GET['cbc_status'] ) && 'success' === sanitize_key( wp_unslash( $_GET['cbc_status'] ) );
$cbc_services_url = CBC_Admin_Settings::get_services_url();
$cbc_terms_url    = CBC_Admin_Settings::get_terms_url();
?>

<div class="cbc-form-wrapper" id="cbc-form-top">
    <?php if ( $cbc_success ) : ?>
        <div class="cbc-alert-success">
            <?php esc_html_e( 'Thank you! Your cleaning quote and booking request has been submitted successfully.', 'cleaning-booking-calculator' ); ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $cbc_errors ) ) : ?>
        <div class="cbc-alert-error" role="alert">
            <strong><?php esc_html_e( 'Please fix the following and submit again:', 'cleaning-booking-calculator' ); ?></strong>
            <ul>
                <?php foreach ( $cbc_errors as $cbc_error ) : ?>
                    <li><?php echo esc_html( $cbc_error ); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form id="cbc-quote-calculator-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-cbc-old="<?php echo esc_attr( $cbc_old_json ); ?>">
        <input type="hidden" name="action" value="cbc_submit_form">

        <!-- Honeypot (hidden from real visitors) -->
        <div class="cbc-hp" aria-hidden="true">
            <label for="cbc_website"><?php esc_html_e( 'Leave this field empty', 'cleaning-booking-calculator' ); ?></label>
            <input type="text" name="cbc_website" id="cbc_website" value="" tabindex="-1" autocomplete="off">
        </div>

        <!-- SECTION 1: Service Type -->
        <div class="cbc-section">
            <div class="cbc-field-group">
                <label for="cbc_service_type"><?php esc_html_e( 'WHAT TYPE OF SERVICE DO YOU NEED? (Required)', 'cleaning-booking-calculator' ); ?></label>
                <select name="service_type" id="cbc_service_type" required>
                    <option value="" disabled selected><?php esc_html_e( 'Select option', 'cleaning-booking-calculator' ); ?></option>
                    <?php foreach ( $cbc_cfg['services'] as $cbc_service_name => $cbc_service ) : ?>
                        <option value="<?php echo esc_attr( $cbc_service_name ); ?>"><?php echo esc_html( $cbc_service['label'] ); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="cbc-service-desc-text">
                    <?php esc_html_e( 'CLICK', 'cleaning-booking-calculator' ); ?>
                    <a href="<?php echo esc_url( $cbc_services_url ); ?>" class="cbc-inline-link" target="_blank" rel="noopener"><?php esc_html_e( 'HERE', 'cleaning-booking-calculator' ); ?></a>
                    <?php esc_html_e( 'FOR DETAILED SERVICE DESCRIPTION', 'cleaning-booking-calculator' ); ?>
                </div>
            </div>
        </div>

        <!-- SECTION 2: Home Details -->
        <div class="cbc-section">
            <div class="cbc-section-header">
                <h3 class="cbc-section-title"><?php esc_html_e( 'TELL US MORE ABOUT YOUR HOME', 'cleaning-booking-calculator' ); ?></h3>
            </div>

            <div class="cbc-grid cbc-grid-3">
                <div class="cbc-field-group">
                    <label for="cbc_bedrooms"><?php esc_html_e( 'HOW MANY BEDROOMS? (Required)', 'cleaning-booking-calculator' ); ?></label>
                    <select name="bedrooms" id="cbc_bedrooms" required>
                        <option value="" disabled selected><?php esc_html_e( 'Select option', 'cleaning-booking-calculator' ); ?></option>
                        <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                            <option value="<?php echo (int) $i; ?>"><?php echo esc_html( sprintf( _n( '%d Bedroom', '%d Bedrooms', $i, 'cleaning-booking-calculator' ), $i ) ); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="cbc-field-group">
                    <label for="cbc_bathrooms"><?php esc_html_e( 'HOW MANY BATHROOMS? (Required)', 'cleaning-booking-calculator' ); ?></label>
                    <select name="bathrooms" id="cbc_bathrooms" required>
                        <option value="" disabled selected><?php esc_html_e( 'Select option', 'cleaning-booking-calculator' ); ?></option>
                        <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                            <option value="<?php echo (int) $i; ?>"><?php echo esc_html( sprintf( _n( '%d Bathroom', '%d Bathrooms', $i, 'cleaning-booking-calculator' ), $i ) ); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="cbc-field-group">
                    <label for="cbc_half_baths"><?php esc_html_e( 'HOW MANY HALF BATHROOMS?', 'cleaning-booking-calculator' ); ?></label>
                    <select name="half_baths" id="cbc_half_baths">
                        <option value="" disabled selected><?php esc_html_e( 'Select option', 'cleaning-booking-calculator' ); ?></option>
                        <option value="0"><?php esc_html_e( 'None', 'cleaning-booking-calculator' ); ?></option>
                        <?php for ( $i = 1; $i <= 3; $i++ ) : ?>
                            <option value="<?php echo (int) $i; ?>"><?php echo esc_html( sprintf( _n( '%d Half Bathroom', '%d Half Bathrooms', $i, 'cleaning-booking-calculator' ), $i ) ); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="cbc-grid cbc-grid-2" style="margin-top: 18px;">
                <div class="cbc-field-group">
                    <label for="cbc_kitchens"><?php esc_html_e( 'HOW MANY KITCHENS? (Required)', 'cleaning-booking-calculator' ); ?></label>
                    <select name="kitchens" id="cbc_kitchens" required>
                        <option value="" disabled selected><?php esc_html_e( 'Select option', 'cleaning-booking-calculator' ); ?></option>
                        <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                            <option value="<?php echo (int) $i; ?>"><?php echo esc_html( sprintf( _n( '%d Kitchen', '%d Kitchens', $i, 'cleaning-booking-calculator' ), $i ) ); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="cbc-field-group">
                    <label for="cbc_sqft"><?php esc_html_e( 'HOW BIG IS YOUR HOME? (Required)', 'cleaning-booking-calculator' ); ?></label>
                    <input type="number" name="sqft" id="cbc_sqft" min="50" max="25000" step="1" placeholder="<?php esc_attr_e( 'sq. Ft', 'cleaning-booking-calculator' ); ?>" required>
                </div>
            </div>
        </div>

        <!-- SECTION 3: Add Extras -->
        <div class="cbc-section">
            <div class="cbc-section-header">
                <h3 class="cbc-section-title"><?php esc_html_e( 'ADD EXTRAS', 'cleaning-booking-calculator' ); ?></h3>
            </div>

            <div class="cbc-checkbox-grid">
                <?php foreach ( $cbc_cfg['extras'] as $cbc_extra_name => $cbc_extra_cents ) :
                    $cbc_slug = sanitize_title( $cbc_extra_name );
                ?>
                    <label class="cbc-checkbox-item" for="extra-<?php echo esc_attr( $cbc_slug ); ?>">
                        <input type="checkbox" name="extras[]" id="extra-<?php echo esc_attr( $cbc_slug ); ?>" value="<?php echo esc_attr( $cbc_extra_name ); ?>">
                        <span class="cbc-checkbox-text"><?php echo esc_html( $cbc_extra_name ); ?></span>
                        <span class="cbc-included-tag" hidden><?php esc_html_e( 'Included', 'cleaning-booking-calculator' ); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- Laundry Sub-Options -->
            <div class="cbc-laundry-group">
                <span class="cbc-laundry-title"><?php esc_html_e( 'Laundry services - towels and bed sheets only', 'cleaning-booking-calculator' ); ?></span>
                <div class="cbc-radio-row">
                    <?php foreach ( $cbc_cfg['laundry'] as $cbc_laundry_name => $cbc_laundry_cents ) : ?>
                        <label class="cbc-radio-item">
                            <input type="radio" name="laundry_service" value="<?php echo esc_attr( $cbc_laundry_name ); ?>">
                            <span><?php echo esc_html( $cbc_laundry_name ); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- SECTION 4: Estimated Total -->
        <div class="cbc-section cbc-total-section">
            <label class="cbc-total-label"><?php esc_html_e( 'ESTIMATED TOTAL', 'cleaning-booking-calculator' ); ?></label>
            <div class="cbc-total-display-input">
                <span id="cbc-total-amount-display">$0.00</span>
            </div>
        </div>

        <!-- SECTION 5: Address & Appointment -->
        <div class="cbc-section">
            <div class="cbc-section-header">
                <h3 class="cbc-section-title"><?php esc_html_e( 'TELL US YOUR ADDRESS', 'cleaning-booking-calculator' ); ?></h3>
            </div>

            <div class="cbc-field-group">
                <label for="cbc_address"><?php esc_html_e( 'Address', 'cleaning-booking-calculator' ); ?></label>
                <input type="text" name="address" id="cbc_address" placeholder="<?php esc_attr_e( 'Address', 'cleaning-booking-calculator' ); ?>" required>
            </div>

            <div class="cbc-notice-banner">
                <?php esc_html_e( 'Please Note: Cleaning appointments can only be scheduled between 9:00 AM and 2:00 PM.', 'cleaning-booking-calculator' ); ?>
            </div>

            <div class="cbc-grid cbc-grid-3 cbc-schedule-row">
                <div class="cbc-field-group cbc-col-comments">
                    <label for="cbc_comments"><?php esc_html_e( 'COMMENTS AND SPECIAL INSTRUCTIONS', 'cleaning-booking-calculator' ); ?></label>
                    <textarea name="comments" id="cbc_comments" rows="3"></textarea>
                </div>
                <div class="cbc-field-group">
                    <label for="cbc_date"><?php esc_html_e( 'DATE (Required)', 'cleaning-booking-calculator' ); ?></label>
                    <input type="date" name="appointment_date" id="cbc_date" min="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" required>
                </div>
                <div class="cbc-field-group">
                    <label for="cbc_time"><?php esc_html_e( 'Time (Required)', 'cleaning-booking-calculator' ); ?></label>
                    <input type="time" name="appointment_time" id="cbc_time" min="09:00" max="14:00" required>
                </div>
            </div>
        </div>

        <!-- SECTION 6: Contact Information -->
        <div class="cbc-section">
            <div class="cbc-section-header">
                <h3 class="cbc-section-title"><?php esc_html_e( 'CONTACT INFORMATIONS', 'cleaning-booking-calculator' ); ?></h3>
            </div>

            <div class="cbc-grid cbc-grid-2">
                <div class="cbc-field-group">
                    <label for="cbc_full_name"><?php esc_html_e( 'Full Name (Required)', 'cleaning-booking-calculator' ); ?></label>
                    <input type="text" name="full_name" id="cbc_full_name" required>
                </div>
                <div class="cbc-field-group">
                    <label for="cbc_phone"><?php esc_html_e( 'Phone Number (Required)', 'cleaning-booking-calculator' ); ?></label>
                    <input type="tel" name="phone" id="cbc_phone" required>
                </div>
            </div>

            <div class="cbc-field-group" style="margin-top: 18px;">
                <label for="cbc_email"><?php esc_html_e( 'Email Address (Required)', 'cleaning-booking-calculator' ); ?></label>
                <input type="email" name="email" id="cbc_email" required>
            </div>

            <div class="cbc-terms-row">
                <label class="cbc-terms-label" for="cbc_terms">
                    <input type="checkbox" name="terms_accepted" id="cbc_terms" value="1" required>
                    <span>
                        <?php esc_html_e( 'I agree to the', 'cleaning-booking-calculator' ); ?>
                        <a href="<?php echo esc_url( $cbc_terms_url ); ?>" target="_blank" rel="noopener" class="cbc-terms-link"><?php esc_html_e( 'Terms and Condition', 'cleaning-booking-calculator' ); ?></a>
                        <?php esc_html_e( '(Required)', 'cleaning-booking-calculator' ); ?>
                    </span>
                </label>
            </div>

            <button type="submit" class="cbc-submit-button">
                <?php esc_html_e( 'COMPLETE REQUEST', 'cleaning-booking-calculator' ); ?>
            </button>
        </div>
    </form>
</div>
