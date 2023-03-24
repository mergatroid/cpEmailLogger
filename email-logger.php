<?php
/*
Plugin Name: CPEmail Logger
Plugin URI: https://www.cocopine.co.za/email_logger
Description: Logs emails sent by WordPress and sends a test email.
Version: 1.0
Author: Cocopine Web Devs
Author URI: https://www.cocopine.co.za
License: GPL2
*/

 require_once ABSPATH . WPINC . '/class-phpmailer.php';     
 require_once ABSPATH . WPINC . '/class-smtp.php';     


function email_logger_send_test_email() {
    $to = 'youremail@example.com';
    $subject = 'Test Email from WordPress';
    $message = 'This is a test email sent from WordPress.';
    wp_mail( $to, $subject, $message );
}
register_activation_hook( __FILE__, 'email_logger_send_test_email' );


function email_logger_log_email( $phpmailer ) {
    global $wpdb;
    try {
	$table_name = $wpdb->prefix . 'cpemail_logs';
	$to = $phpmailer->getToAddresses();
    $from = $phpmailer->FromName;
	$sender = $phpmailer->From;
    $subject = $phpmailer->Subject;
    $body = $phpmailer->Body;
    $date = date( 'Y-m-d H:i:s' );
    $wpdb->insert(
        $table_name,
        array(
            'sent_from' => $from." <".$sender.">",
			'sent_to' => $to[0][0],
            'subject' => $subject,
            'body' => $body,
            'sent_date' => $date,
        ),
        array(
            '%s',
			'%s',
            '%s',
            '%s',
            '%s',
        )
    );
	}
	catch (Exception $e)
	{
	   echo $e->errorMessage();
	}
}
add_action( 'phpmailer_init', 'email_logger_log_email' );

function email_logger_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cpemail_logs';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
		sent_from varchar(255) NOT NULL,
        sent_to varchar(255) NOT NULL,
        subject varchar(255) NOT NULL,
        body text NOT NULL,
        sent_date datetime NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'email_logger_create_table' );

function email_logger_menu() {
    add_menu_page(
        'Email Logs',
        'Email Logs',
        'manage_options',
        'email-logger',
        'email_logger_display_logs',
        'dashicons-email',
        20
    );

    add_submenu_page(
        'email-logger',
        'Send Test Email',
        'Send Test Email',
        'manage_options',
        'email-logger-test-email',
        'email_logger_send_test_email_page'
    );
}

add_action( 'admin_menu', 'email_logger_menu' );

function email_logger_display_logs() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cpemail_logs';
    
    // Check for search parameter
    $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

    // Set default order and orderby values
    $orderby = isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'sent_date';
    $order = isset( $_GET['order'] ) ? $_GET['order'] : 'desc';
    
	
	$sent_from_orderby_url = add_query_arg( array(
        'orderby' => 'sent_from',
        'order' => $order == 'desc' ? 'asc' : 'desc',
        's' => $search
    ) );
    // Set up orderby links
    $sent_to_orderby_url = add_query_arg( array(
        'orderby' => 'sent_to',
        'order' => $order == 'desc' ? 'asc' : 'desc',
        's' => $search
    ) );
    $subject_orderby_url = add_query_arg( array(
        'orderby' => 'subject',
        'order' => $order == 'desc' ? 'asc' : 'desc',
        's' => $search
    ) );
    $sent_date_orderby_url = add_query_arg( array(
        'orderby' => 'sent_date',
        'order' => $order == 'desc' ? 'asc' : 'desc',
        's' => $search
    ) );

    $logs = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE sent_to LIKE %s OR subject LIKE %s ORDER BY $orderby $order",
            '%' . $wpdb->esc_like( $search ) . '%',
            '%' . $wpdb->esc_like( $search ) . '%'
        )
    );

    ?>
    <div class="wrap">
        <h1>Email Logs</h1>

        <form method="get">
            <input type="hidden" name="page" value="email-logger" />
            <label for="s">Search:</label>
            <input type="text" name="s" value="<?php echo esc_attr( $search ); ?>" />
            <input type="submit" value="Search" class="button" />
        </form>

        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><a href="<?php echo esc_url( $sent_to_orderby_url ); ?>">To</a></th>
					<th><a href="<?php echo esc_url( $sent_from_orderby_url ); ?>">From</a></th>
                    <th><a href="<?php echo esc_url( $subject_orderby_url ); ?>">Subject</a></th>
                    <th>Body</th>
                    <th><a href="<?php echo esc_url( $sent_date_orderby_url ); ?>">Date Sent</a></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $logs as $log ) : ?>
                    <tr>
                        <td><?php echo $log->id; ?></td>
                        <td><?php echo $log->sent_to; ?></td>
						 <td><?php echo $log->sent_from; ?></td>
                        <td><?php echo $log->subject; ?></td>
						<td width="200px"><?php echo $log->body; ?></td>
                        <td><?php echo $log->sent_date; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php

   

}

function email_logger_send_test_email_page() {
    if ( isset( $_POST['send_test_email'] ) ) {
        $to = $_POST['email'];
        $subject = 'Test Email from WordPress';
        $message = 'This is a test email sent from WordPress.';
        wp_mail( $to, $subject, $message );
        echo '<div class="notice notice-success"><p>Test email sent to ' . $to . '</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Send Test Email</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th><label for="email">Email</label></th>
                    <td><input type="email" id="email" name="email" required></td>
                </tr>
            </table>
            <p class="submit"><input type="submit" name="send_test_email" class="button-primary" value="Send Test Email"></p>
        </form>
    </div>
    <?php
}


register_deactivation_hook( __FILE__, 'email_logger_remove_database' );
function email_logger_remove_database() {
     global $wpdb;
     $table_name = $wpdb->prefix . 'cpemail_logs';
     $sql = "DROP TABLE IF EXISTS $table_name";
     $wpdb->query($sql);
     delete_option("email_logger_db_version");
}   
?>