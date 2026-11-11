<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CBC_DB_Handler {

    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'cbc_booking_requests';
    }

    public static function create_table() {
        global $wpdb;
        $table_name      = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            service_type varchar(100) NOT NULL,
            bedrooms int(11) NOT NULL,
            bathrooms int(11) NOT NULL,
            half_baths int(11) NOT NULL DEFAULT 0,
            kitchens int(11) NOT NULL DEFAULT 1,
            sqft int(11) NOT NULL DEFAULT 0,
            selected_extras text DEFAULT NULL,
            laundry_service varchar(100) DEFAULT NULL,
            total_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            address text NOT NULL,
            comments text DEFAULT NULL,
            appointment_date varchar(50) NOT NULL,
            appointment_time varchar(50) NOT NULL,
            full_name varchar(255) NOT NULL,
            phone varchar(50) NOT NULL,
            email varchar(255) NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'Pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function insert_entry( $data ) {
        global $wpdb;
        $table_name = self::get_table_name();
        $format     = [
            '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%f',
            '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
        ];

        $inserted = $wpdb->insert( $table_name, $data, $format );
        return $inserted ? $wpdb->insert_id : false;
    }

    public static function get_entries( $per_page = 20, $offset = 0 ) {
        global $wpdb;
        $table = self::get_table_name();
        return $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d", $per_page, $offset ),
            ARRAY_A
        );
    }

    public static function get_total_count() {
        global $wpdb;
        $table = self::get_table_name();
        return (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table}" );
    }

    public static function update_status( $id, $status ) {
        global $wpdb;
        $table = self::get_table_name();
        return $wpdb->update(
            $table,
            [ 'status' => sanitize_text_field( $status ) ],
            [ 'id'     => absint( $id ) ],
            [ '%s' ],
            [ '%d' ]
        );
    }

    public static function delete_entry( $id ) {
        global $wpdb;
        $table = self::get_table_name();
        return $wpdb->delete( $table, [ 'id' => absint( $id ) ], [ '%d' ] );
    }
}