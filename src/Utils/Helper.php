<?php

namespace Shipay\WcShipayPayment\Utils;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class to provide WordPress shortcuts.
 */
class Helper
{
    /**
     * "Queue" WordPress admin notices.
     *
     * @since 1.1.0
     * @var array $notices
     */
    protected static $notices = [];

    /**
     * CSS class for a success notice.
     *
     * @since 1.1.0
     * @var string ADMIN_NOTICE_SUCCESS
     */
    const ADMIN_NOTICE_SUCCESS = 'notice-success';

    /**
     * CSS class for an error notice.
     *
     * @since 1.1.0
     * @var string ADMIN_NOTICE_ERROR
     */
    const ADMIN_NOTICE_ERROR = 'notice-error';

    /**
     * CSS class for an info notice.
     *
     * @since 1.1.0
     * @var string ADMIN_NOTICE_INFO
     */
    const ADMIN_NOTICE_INFO = 'notice-info';

    /**
     * CSS class for a warning notice.
     *
     * @since 1.1.0
     * @var string ADMIN_NOTICE_WARNING
     */
    const ADMIN_NOTICE_WARNING = 'notice-warning';

    /**
     * Add an action to the "queue of actions".
     *
     * @since 1.1.0
     * @param string $hook The name of the WordPress action that is being registered.
     * @param object|string $component A reference to the instance of the object on which the action is defined.
     * @param string $callback The name of the function definition on the $component.
     * @param int $priority Optional. The priority at which the function should be fired. Default is 10.
     * @param int $accepted_args Optional. The number of arguments that should be passed to the $callback. Default is 1.
     * @return void
     */
    public static function add_action(
        string $hook,
               $component,
        string $callback,
        int $priority = 10,
        int $accepted_args = 1
    ) {
        $hook = [
            'hook' => $hook,
            'callback' => [ $component, $callback ],
            'priority' => $priority,
            'accepted_args' => $accepted_args
        ];

        add_action( $hook['hook'], $hook['callback'], $hook['priority'], $hook['accepted_args'] );
    }

    /**
     * Add a filter to the "queue of filters".
     *
     * @since 1.1.0
     * @param string $hook The name of the WordPress filter that is being registered.
     * @param object|string $component A reference to the instance of the object on which the filter is defined.
     * @param string $callback The name of the function definition on the $component.
     * @param int $priority Optional. The priority at which the function should be fired. Default is 10.
     * @param int $accepted_args Optional. The number of arguments that should be passed to the $callback. Default is 1.
     * @return void
     */
    public static function add_filter(
        string $hook,
               $component,
        string $callback,
        int $priority = 10,
        int $accepted_args = 1
    ) {
        $hook = [
            'hook' => $hook,
            'callback' => [ $component, $callback ],
            'priority' => $priority,
            'accepted_args' => $accepted_args
        ];

        add_filter( $hook['hook'], $hook['callback'], $hook['priority'], $hook['accepted_args'] );
    }

    /**
     * Add a notice to the "queue of notices".
     *
     * @since 1.1.0
     * @param string $message The text message (HTML is OK).
     * @param string $type Display class type (severity).
     * @param bool $is_dismissible Whether the message should be dismissible.
     * @return void
     */
    public static function add_admin_notice(
        string $message,
        string $type = self::ADMIN_NOTICE_INFO,
        bool $is_dismissible = true
    ) {
        self::$notices[] = [
            'message' => $message,
            'class' => $type,
            'is_dismissible' => (bool) $is_dismissible
        ];
    }

    /**
     * Display all notices.
     *
     * @since 1.1.0
     * @return void
     */
    public static function display_notices() {
        foreach ( self::$notices as $notice ) {
            $dismissible = $notice['is_dismissible'] ? 'is-dismissible' : '';
            ?>
            <div class="notice <?php echo esc_attr( $notice['class'] ); ?> <?php echo esc_attr( $dismissible ); ?>">
                <p>
                    <?php echo wp_kses_post( $notice['message'] ); ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Check if WordPress is processing an AJAX call.
     *
     * @since 1.1.0
     * @return bool
     */
    public static function is_doing_ajax(): bool {
        // Return WordPress native function if exists
        if ( function_exists( 'wp_doing_ajax' ) )
            return wp_doing_ajax();

        // Check for ajax constant variable
        return ( defined( 'DOING_AJAX' ) && DOING_AJAX );
    }

    /**
     * Check if "I am" in the Admin Panel, not doing AJAX call.
     *
     * @since 1.1.0
     * @return bool
     */
    public static function is_pure_admin(): bool {
        return ( is_admin() && ! self::is_doing_ajax() );
    }

    /**
     * Check if WP_DEBUG is active.
     *
     * @since 1.1.0
     * @return bool
     */
    public static function is_debugging(): bool {
        return ( defined( 'WP_DEBUG' ) && WP_DEBUG );
    }

    /**
     * Get the postfix for assets files - ".min" or empty
     * ".min" if in production mode.
     *
     * @since 1.1.0
     * @return string
     */
    public static function minify(): string {
        return ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
    }

    /**
     * Check whether the string is a JSON or not.
     *
     * @since 1.1.0
     * @param string $string String to test if it's json.
     * @return string
     */
    public static function is_json( string $string ): bool {
        return is_string( $string ) && is_array( json_decode( $string, true ) ) && ( json_last_error() === JSON_ERROR_NONE ) ? true : false;
    }

    /**
     * @param $additional_time
     * @param $additional_type
     * @param $date_format
     * @return string
     */
    public static function get_date( $additional_time = 0, $additional_type = 'seconds', $date_format = 'Y-m-d H:i:s' )
    {
        return gmdate( $date_format,
            strtotime( '+' . $additional_time . ' ' . $additional_type, current_time( 'timestamp' ) )
        );
    }

    /**
     * Only numbers.
     *
     * @param  string|int $string String to convert.
     *
     * @return string|int
     */
    public function only_numbers( $string ) {
        return preg_replace( '([^0-9])', '', $string );
    }

    public function get_diff_betwen_dates($start_date, $end_date)
    {
        $start = new \DateTime($start_date, new \DateTimeZone('UTC'));

        if ($start->format('Y-m-d H:i:s') > $end_date) {
            return 0;
        }

        $end = new \DateTime($end_date, new \DateTimeZone('UTC'));

        $interval = $start->diff($end);
        $seconds = ($interval->days * 24 * 60 * 60) +
            ($interval->h * 60 * 60) +
            ($interval->i * 60) +
            $interval->s;

        return $seconds;
    }
}