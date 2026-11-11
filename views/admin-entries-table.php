<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$per_page     = 15;
$offset       = ( $current_page - 1 ) * $per_page;
$total_items  = CBC_DB_Handler::get_total_count();
$entries      = CBC_DB_Handler::get_entries( $per_page, $offset );
$total_pages  = ceil( $total_items / $per_page );
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Submitted Booking Quotes', 'cleaning-booking-calculator' ); ?></h1>
    <hr class="wp-header-end">

    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
            <tr>
                <th width="4%">ID</th>
                <th width="12%">Client</th>
                <th width="14%">Contact</th>
                <th width="12%">Service</th>
                <th width="12%">Property Layout</th>
                <th width="10%">Date & Time</th>
                <th width="8%">Total</th>
                <th width="18%">Status Actions</th>
                <th width="10%">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( ! empty( $entries ) ) : ?>
                <?php foreach ( $entries as $entry ) : ?>
                    <tr>
                        <td><strong>#<?php echo esc_html( $entry['id'] ); ?></strong></td>
                        <td>
                            <strong><?php echo esc_html( $entry['full_name'] ); ?></strong><br>
                            <small><?php echo esc_html( $entry['address'] ); ?></small>
                        </td>
                        <td>
                            <a href="mailto:<?php echo esc_attr( $entry['email'] ); ?>"><?php echo esc_html( $entry['email'] ); ?></a><br>
                            <a href="tel:<?php echo esc_attr( $entry['phone'] ); ?>"><?php echo esc_html( $entry['phone'] ); ?></a>
                        </td>
                        <td>
                            <strong><?php echo esc_html( $entry['service_type'] ); ?></strong><br>
                            <small><?php echo esc_html( $entry['sqft'] ); ?> sq. ft</small><br>
                            <small><em>Extras: <?php echo esc_html( $entry['selected_extras'] ); ?></em></small>
                        </td>
                        <td>
                            <?php 
                            printf( 
                                '%d Bed | %d Bath<br>%d Half-Bath | %d Kit', 
                                esc_html( $entry['bedrooms'] ), 
                                esc_html( $entry['bathrooms'] ), 
                                esc_html( $entry['half_baths'] ), 
                                esc_html( $entry['kitchens'] ) 
                            ); 
                            ?>
                        </td>
                        <td>
                            <?php echo esc_html( $entry['appointment_date'] ); ?><br>
                            <strong><?php echo esc_html( $entry['appointment_time'] ); ?></strong>
                        </td>
                        <td>
                            <strong style="color:#007cba;">$<?php echo number_format( (float) $entry['total_amount'], 2 ); ?></strong>
                        </td>
                        <td>
                            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex; gap:6px; align-items:center;">
                                <input type="hidden" name="action" value="cbc_update_status">
                                <input type="hidden" name="entry_id" value="<?php echo esc_attr( $entry['id'] ); ?>">
                                <?php wp_nonce_field( 'cbc_update_status_nonce' ); ?>
                                <select name="status" style="font-size:12px; padding:2px 6px; height:28px;">
                                    <option value="Pending" <?php selected( $entry['status'], 'Pending' ); ?>><?php esc_html_e( 'Pending', 'cleaning-booking-calculator' ); ?></option>
                                    <option value="Approved" <?php selected( $entry['status'], 'Approved' ); ?>><?php esc_html_e( 'Approved', 'cleaning-booking-calculator' ); ?></option>
                                    <option value="Rejected" <?php selected( $entry['status'], 'Rejected' ); ?>><?php esc_html_e( 'Rejected/Dislike', 'cleaning-booking-calculator' ); ?></option>
                                </select>
                                <input type="submit" class="button button-small" value="<?php esc_attr_e( 'Update', 'cleaning-booking-calculator' ); ?>">
                            </form>
                        </td>
                        <td>
                            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this booking?', 'cleaning-booking-calculator' ) ); ?>');">
                                <input type="hidden" name="action" value="cbc_delete_entry">
                                <input type="hidden" name="entry_id" value="<?php echo esc_attr( $entry['id'] ); ?>">
                                <?php wp_nonce_field( 'cbc_delete_entry_nonce' ); ?>
                                <input type="submit" class="button button-link-delete" value="<?php esc_attr_e( 'Delete', 'cleaning-booking-calculator' ); ?>">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="9"><?php esc_html_e( 'No quote requests submitted yet.', 'cleaning-booking-calculator' ); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ( $total_pages > 1 ) : ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <?php
                echo paginate_links( [
                    'base'    => add_query_arg( 'paged', '%#%' ),
                    'format'  => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total'   => $total_pages,
                    'current' => $current_page
                ] );
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>